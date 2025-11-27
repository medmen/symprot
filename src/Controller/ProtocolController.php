<?php

namespace App\Controller;

use App\Formatter\FormatterContext;
use App\Service\JobManager;
use App\Strategy\ConverterContext;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class ProtocolController extends AbstractController
{
    private string $projectDir;
    private string $uploadDir;
    private string $format;

    public function __construct(private ConverterContext $convertercontext, private FormatterContext $formattercontext, private LoggerInterface $logger, KernelInterface $kernel)
    {
        $this->projectDir = $kernel->getProjectDir();
    }

    // Process by file path (no DB, no Vich)
    #[Route(path: '/process_upload', name: 'process_upload', methods: ['GET'])]
    public function index(Request $request, SessionInterface $session): Response
    {
        // Use configured uploads directory as absolute path
        $this->uploadDir = (string) $this->getParameter('app.uploads_dir');
        $this->format = $request->query->get('format') ?? 'html';

        $path = (string) ($request->query->get('path') ?? '');
        $geraet = (string) ($request->query->get('geraet') ?? '');

        $fullfilepath = '';
        $mime = '';
        $filetype = '';

        $fullfilepath = rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);

        if (is_file($fullfilepath)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = (string) ($finfo->file($fullfilepath) ?: 'application/octet-stream');
            $filetype = ($mime && str_contains($mime, '/')) ? ucfirst(explode('/', $mime)[1]) : pathinfo($fullfilepath, PATHINFO_EXTENSION);
        }

        // diagnostics
        try {
            $this->logger->info('ProtocolController: input resolved', [
                'query_path' => $path,
                'absolute_path' => $fullfilepath,
                'is_file' => $fullfilepath ? is_file($fullfilepath) : null,
                'is_readable' => $fullfilepath ? is_readable($fullfilepath) : null,
                'mime' => $mime,
            ]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        if (!$fullfilepath) {
            $errors = ['No file was uploaded or upload failed - please ask the Admin to check permissions for file upload!'];

            return $this->render('protocol/index.html.twig', [
                'geraet' => $geraet,
                'protocol' => ($path ? basename($path) : ''),
                'errors' => $errors,
                'output' => 'Umwandlung fehlgeschlagen, erwartete Datei nicht gefunden: '.($fullfilepath ?: '(leer)'),
                'controller_name' => 'ProtocolController',
            ]);
        }

        if (!is_file($fullfilepath)) {
            // Small retry/backoff in case filesystem is slow to finalize the move
            $attempts = 0;
            $maxAttempts = 5; // ~500ms total (5 x 100ms)
            while ($attempts < $maxAttempts && !is_file($fullfilepath)) {
                usleep(100_000); // 100ms
                clearstatcache(true, $fullfilepath);
                $attempts++;
            }

            if (!is_file($fullfilepath)) {
                $errors = ['No file was uploaded or upload failed - please ask the Admin to check permissions for file upload!'];

                try {
                    $this->logger->error('ProtocolController: file not found after retries', [
                        'absolute_path' => $fullfilepath,
                        'attempts' => $attempts,
                        'dir_exists' => is_dir(dirname($fullfilepath)),
                        'dir_writable' => is_writable(dirname($fullfilepath)),
                    ]);
                } catch (\Throwable $ignore) {}

                return $this->render('protocol/index.html.twig', [
                    'geraet' => $geraet,
                    'protocol' => ($path ? basename($path) : ''),
                    'errors' => $errors,
                    'output' => 'Umwandlung fehlgeschlagen, erwartete Datei nicht gefunden: '.($fullfilepath ?: '(leer)'),
                    'controller_name' => 'ProtocolController',
                ]);
            }
        }

        // Build job payload and start background processing
        $payload = [
            'geraet' => $geraet,
            'mimetype' => $mime,
            'filename' => ($path ? basename($path) : ''),
            'format' => $this->format,
        ];

        // Create job
        $jobManager = $this->container->get(JobManager::class);
        $jobId = $jobManager->createJob($payload);

        // Start background Symfony command
        try {
            $console = $this->projectDir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console';
            $process = new Process([PHP_BINARY, $console, 'app:process-protocol-job', $jobId]);
            $process->disableOutput();
            $process->start();
        } catch (\Throwable $e) {
            try {
                $this->logger->error('ProtocolController: failed to start background process', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            } catch (\Throwable $ignore) {}
            $errors[] = 'Hintergrundprozess konnte nicht gestartet werden: '.$e->getMessage();
        }

        $this->addFlash(
            'success',
            'Die Datei wurde hochgeladen. Sie wird nun im Hintergrund analysiert und umgewandelt. Fortschritt wird unten angezeigt.',
        );

        return $this->render('protocol/index.html.twig', [
            'geraet' => $geraet,
            'protocol' => ($path ? basename($path) : ''),
            'errors' => [ 'filetype' => $filetype ],
            'output' => '',
            'jobId' => $jobId,
            'controller_name' => 'ProtocolController',
        ]);
    }
}
