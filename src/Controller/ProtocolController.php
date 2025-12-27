<?php

namespace App\Controller;

use App\Formatter\FormatterContext;
use App\Strategy\ConverterContext;
use App\Service\StatusLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProtocolController extends AbstractController
{
    private string $uploadDir;
    private string $format;

    public function __construct(
        private ConverterContext $convertercontext,
        private FormatterContext $formattercontext,
        private LoggerInterface $logger,
        private StatusLogger $statusLogger,
        KernelInterface $kernel)
    {
        // $projectDir wird via services.yaml bind ($projectDir: '%kernel.project_dir%') injiziert
    }

    // Process by file path (no DB, no Vich)
    #[Route(path: '/process_upload', name: 'process_upload', methods: ['GET'])]
    public function index(Request $request, SessionInterface $session): Response
    {
        // Use configured uploads directory as absolute path
        $this->uploadDir = (string) $this->getParameter('app.uploads_dir');
        $this->format = $request->query->get('format') ?? 'html';

        // Create a new status token for this request and init the status log
        if (!$session->isStarted()) {
            $session->start();
        }
        $token = bin2hex(random_bytes(12));
        $session->set('status_token', $token);

        $path = (string) ($request->query->get('path') ?? '');
        $geraet = (string) ($request->query->get('geraet') ?? '');
        $mime = '';
        $filetype = '';

        // initialize status file
        $this->statusLogger->init($token, [
            'path' => $path,
            'geraet' => $geraet,
            'format' => $this->format,
        ]);
        $this->statusLogger->append($token, 'Request received and initialized');

        // If no path is provided, try to re-apply a formatter on existing session data
        if ($path === '') {
            if (!$session->isStarted()) {
                $session->start();
            }
            $serialized_and_parsed_data = $session->get('serialized_and_parsed_data');
            $modality_and_mime = $session->get('modality_and_mime');
            $last_filename = (string) ($session->get('last_protocol_filename') ?? '');
            $last_filetype = (string) ($session->get('last_filetype') ?? '');

            if (!$serialized_and_parsed_data && !$modality_and_mime) {
                return $this->redirectToRoute('index');
            }

            if ($serialized_and_parsed_data && $modality_and_mime) {
                try {
                    $this->statusLogger->append($token, 'Re-formatting existing session data');
                    $formatted_data = $this->formattercontext->withStatus($this->statusLogger, $token)->handle($modality_and_mime, $serialized_and_parsed_data, $this->format);
                    $this->statusLogger->complete($token, true, 'Re-formatting done');

                    return $this->render('protocol/index.html.twig', [
                        'geraet' => $modality_and_mime->geraet ?? $geraet,
                        'protocol' => $last_filename,
                        'errors' => [ 'filetype' => $last_filetype ],
                        'output' => $formatted_data,
                        'controller_name' => 'ProtocolController',
                        'status_token' => $token,
                        'status_url' => $this->generateUrl('status_poll', ['token' => $token]),
                    ]);
                } catch (\Throwable $e) {
                    try {
                        $this->logger->error('ProtocolController: re-formatting failed', [
                            'exception' => get_class($e),
                            'message' => $e->getMessage(),
                        ]);
                    } catch (\Throwable $ignore) {}
                    $this->statusLogger->append($token, 'Re-formatting failed: ' . $e->getMessage());
                    $this->statusLogger->complete($token, false, 'Re-formatting error');

                    return $this->render('protocol/index.html.twig', [
                        'geraet' => $modality_and_mime->geraet ?? $geraet,
                        'protocol' => $last_filename,
                        'errors' => ['Reformatting failed: '.$e->getMessage()],
                        'output' => 'Fehler bei der Neuformatierung: '.$e->getMessage(),
                        'controller_name' => 'ProtocolController',
                        'status_token' => $token,
                        'status_url' => $this->generateUrl('status_poll', ['token' => $token]),
                    ]);
                }
            }
            // If we get here, no session data available
            return $this->render('protocol/index.html.twig', [
                'geraet' => $geraet,
                'protocol' => '',
                'errors' => ['Kein gespeichertes Protokoll vorhanden. Bitte zuerst eine Datei verarbeiten.'],
                'output' => '',
                'controller_name' => 'ProtocolController',
                'status_token' => $token,
                'status_url' => $this->generateUrl('status_poll', ['token' => $token]),
            ]);
        }

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

        if (!is_file($fullfilepath)) {
            // Small retry/backoff in case filesystem is slow to finalize the move
            $attempts = 0;
            $maxAttempts = 5; // ~500ms total (5 x 100ms)
            while ($attempts < $maxAttempts && !is_file($fullfilepath)) {
                usleep(100000); // 100ms
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

                // progress log
                $this->statusLogger->append($token, 'File not found: ' . ($fullfilepath ?: '(leer)'));
                $this->statusLogger->complete($token, false, 'Upload missing');

                return $this->render('protocol/index.html.twig', [
                    'geraet' => $geraet,
                    'protocol' => ($path ? basename($path) : ''),
                    'errors' => $errors,
                    'output' => 'Umwandlung fehlgeschlagen, erwartete Datei nicht gefunden: '.($fullfilepath ?: '(leer)'),
                    'controller_name' => 'ProtocolController',
                    'status_token' => $token,
                    'status_url' => $this->generateUrl('status_poll', ['token' => $token]),
                ]);
            }
        }
        $this->statusLogger->append($token, 'Upload located: ' . basename($fullfilepath));

        // define new plain object for transport of necessary values
        $modality_and_mime = new \stdClass();
        $modality_and_mime->geraet = $geraet;
        $modality_and_mime->mimetype = $mime;
        // Only transmit the uploaded file name; strategies will resolve full path via appUploadsDir
        $modality_and_mime->filename = ($path ? basename($path) : '');

        $deleteAfterSuccess = false;
        $formatted_data = '';
        $errors = [ 'filetype' => $filetype ];

        try {
            $this->statusLogger->append($token, 'Starting conversion');
            $serialized_and_parsed_data = $this->convertercontext->withStatus($this->statusLogger, $token)->handle($modality_and_mime);

            if (!$session->isStarted()) {
                $session->start();
            }
            $session->set('serialized_and_parsed_data', $serialized_and_parsed_data);
            // Persist modality/mime and some meta so we can re-apply other formatters without reconversion
            $session->set('modality_and_mime', $modality_and_mime);
            $session->set('last_protocol_filename', ($path ? basename($path) : ''));
            $session->set('last_filetype', $filetype);

            $this->addFlash(
                'success',
                'Die Datei wurde hochgeladen. Sie wird nun im Hintergrund analysiert und umgewandelt. Die Ausgabe erfolgt im Fenster unten.',
            );

            $this->statusLogger->append($token, 'Starting formatting');
            $formatted_data = $this->formattercontext->withStatus($this->statusLogger, $token)->handle($modality_and_mime, $serialized_and_parsed_data, $this->format);

            $this->statusLogger->complete($token, true, 'Processing finished');

            // if both converter and formatter completed, we can delete afterwards
            $deleteAfterSuccess = true;
        } catch (\Throwable $e) {
            try {
                $this->logger->error('ProtocolController: processing failed', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            } catch (\Throwable $ignore) {}
            $this->statusLogger->append($token, 'Processing failed: ' . $e->getMessage());
            $this->statusLogger->complete($token, false, 'Processing error');
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
            'status_token' => $token,
            'status_url' => $this->generateUrl('status_poll', ['token' => $token]),
        ]);
    }

    #[Route(path: '/status/{token}', name: 'status_poll', methods: ['GET'])]
    public function pollStatus(string $token, SessionInterface $session): JsonResponse
    {
        if (!$session->isStarted()) {
            $session->start();
        }
        $expected = (string) ($session->get('status_token') ?? '');
        if (!$expected || !hash_equals($expected, $token)) {
            return new JsonResponse(['error' => 'invalid token'], 403);
        }
        $data = $this->statusLogger->read($token);
        return new JsonResponse($data);
    }
}
