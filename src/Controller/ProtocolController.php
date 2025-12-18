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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProtocolController extends AbstractController
{
    private string $uploadDir;
    private string $format;

    public function __construct(
        private ConverterContext $convertercontext,
        private FormatterContext $formattercontext,
        private LoggerInterface $logger,
        private string $projectDir,
        private JobManager $jobManager
    ) {
        // $projectDir wird via services.yaml bind ($projectDir: '%kernel.project_dir%') injiziert
    }

    // Process by file path (no DB, no Vich)
    #[Route(path: '/process_upload', name: 'process_upload', methods: ['GET'])]
    public function index(Request $request, SessionInterface $session): Response
    {
        $this->logger->debug('in ProtocolController::index function got called.');

        // Use configured uploads directory as absolute path
        $this->uploadDir = (string) $this->getParameter('app.uploads_dir');
        $this->format = $request->query->get('format') ?? 'html';

        $path = (string) ($request->query->get('path') ?? '');
        $geraet = (string) ($request->query->get('geraet') ?? '');

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
            $this->logger->error('in ProtocolController::index no file was found.');
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

        $jobId = $this->jobManager->createJob($payload);

        // Start background Symfony command
        try {
            $this->logger->debug('in ProtocolController::index starting background process for job: '.$jobId);

            $console = $this->projectDir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console';

            // Resolve PHP CLI binary reliably
            $phpBinaryFinder = new PhpExecutableFinder();
            $php = $phpBinaryFinder->find();
            if (!$php) {
                throw new \RuntimeException('Kein PHP-CLI Binary gefunden. Setzen Sie die Umgebungsvariable PHP_PATH.');
            }

            $this->logger->debug('in ProtocolController::index running bg-job with runtime: '.$php.' '.$console.' app:process-protocol-job '.$jobId, []);

            $process = new Process([$php, $console, 'app:process-protocol-job', $jobId], $this->projectDir);
            $process->setTimeout(null); // allow long-running job, internal watchdog handles timeout

            $this->logger->debug('About to start background process', [
                'cmd' => [$php, $console, 'app:process-protocol-job', $jobId],
                'cwd' => $this->projectDir,
            ]);

            $process->start();

            usleep(50_000); // 50ms
            $this->logger->debug('Process start() returned', [
                'running' => $process->isRunning(),
                'pid' => method_exists($process, 'getPid') ? $process->getPid() : null,
            ]);

            // Nudge status so UI doesn't stay at 0% if worker startup is slow
            try {
                $pid = method_exists($process, 'getPid') ? (int) $process->getPid() : 0;
                $this->jobManager->update($jobId, 1, 'Hintergrundprozess gestartet' . ($pid ? ' (PID: ' . $pid . ')' : ''));
            } catch (\Throwable $ignore) {}
        } catch (\Throwable $e) {
            try {
                $this->logger->error('ProtocolController: failed to start background process', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'console' => $console ?? null,
                ]);
            } catch (\Throwable $ignore) {}

            try {
                $this->jobManager->fail($jobId, 'Hintergrundprozess konnte nicht gestartet werden: ' . $e->getMessage());
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
            'errors' => $errors ?? [],
            'output' => '',
            'jobId' => $jobId,
            'controller_name' => 'ProtocolController',
        ]);
    }
}
