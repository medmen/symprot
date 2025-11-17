<?php

namespace App\Controller;

use App\Formatter\FormatterContext;
use App\Strategy\ConverterContext;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
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

        // define new plain object for transport of necessary values
        $data = new \stdClass();
        $data->geraet = $geraet;
        $data->mimetype = $mime;
        // Only transmit the uploaded file name; strategies will resolve full path via appUploadsDir
        $data->filename = ($path ? basename($path) : '');

        $deleteAfterSuccess = false;
        $formatted_data = '';
        $errors = [ 'filetype' => $filetype ];

        try {
            $serialized_and_parsed_data = $this->convertercontext->handle($data);

            if (!$session->isStarted()) {
                $session->start();
            }
            $session->set('serialized_and_parsed_data', $serialized_and_parsed_data);

            $this->addFlash(
                'success',
                'Die Datei wurde hochgeladen. Sie wird nun im Hintergrund analysiert und umgewandelt. Die Ausgabe erfolgt im Fenster unten.',
            );

            $formatted_data = $this->formattercontext->handle($data, $serialized_and_parsed_data, $this->format);

            // if both converter and formatter completed, we can delete afterwards
            $deleteAfterSuccess = true;
        } catch (\Throwable $e) {
            try {
                $this->logger->error('ProtocolController: processing failed', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            } catch (\Throwable $ignore) {}
            $errors[] = 'Verarbeitung fehlgeschlagen: '.$e->getMessage();
            $formatted_data = 'Fehler bei der Verarbeitung: '.$e->getMessage();
        } finally {
            // Delete the uploaded file only on success
            if ($deleteAfterSuccess) {
                try {
                    if ($path && is_file($fullfilepath)) {
                        @unlink($fullfilepath);
                    }
                } catch (\Throwable $e) {
                    // ignore file deletion errors
                }
            }
        }

        return $this->render('protocol/index.html.twig', [
            'geraet' => $geraet,
            'protocol' => ($path ? basename($path) : ''),
            'errors' => $errors,
            'output' => $formatted_data,
            'controller_name' => 'ProtocolController',
        ]);
    }
}
