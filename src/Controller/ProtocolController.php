<?php

namespace App\Controller;

use App\Entity\Protocol;
use App\Formatter\FormatterContext;
use App\Strategy\ConverterContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Storage\StorageInterface;

class ProtocolController extends AbstractController
{
    private $kernel;

    private $format;

    public function __construct(private ConverterContext $convertercontext, private FormatterContext $formattercontext, private StorageInterface $storage, private LoggerInterface $logger, KernelInterface $kernel)
    {
        $this->kernel = $kernel->getProjectDir();
    }

    #[Route(path: '/process_upload/{id}', name: 'process_upload', methods: ['GET'])]
    public function index(Request $request, int $id, ConverterContext $converterContext, FormatterContext $formattercontext, NotifierInterface $notifier, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $this->format = $request->query->get('format') ?? 'html'; // make sure we have a default

        $protocol = $entityManager
            ->getRepository(Protocol::class)
            ->find($id);

        // Handle missing protocol gracefully
        if (!$protocol) {
            return $this->render('protocol/index.html.twig', [
                'geraet' => '',
                'protocol' => null,
                'errors' => ['Protocol not found.'],
                'output' => '',
                'controller_name' => 'ProtocolController',
            ]);
        }

        $geraetEntity = $protocol->getGeraet();
        $geraet = $geraetEntity ? ucfirst((string) $geraetEntity->getGeraetName()) : '';

        $mime = (string) $protocol->getProtocolMimeType();
        $filetype = ($mime && str_contains($mime, '/')) ? ucfirst(explode('/', $mime)[1]) : '';

        // Resolve both public URI and absolute filesystem path using Vich storage
        // Use injected Vich storage to resolve paths
        $filepath = (string) $this->storage->resolveUri($protocol, 'protocolFile'); // public URI
        $fullfilepath = (string) $this->storage->resolvePath($protocol, 'protocolFile'); // absolute filesystem path

        // diagnostics
        try {
            $this->logger->info('ProtocolController: resolved upload paths', [
                'protocol_id' => $protocol->getId(),
                'protocol_name' => $protocol->getProtocolName(),
                'mime' => $mime,
                'public_uri' => $filepath,
                'absolute_path' => $fullfilepath,
                'is_file' => $fullfilepath ? is_file($fullfilepath) : null,
                'is_readable' => $fullfilepath ? is_readable($fullfilepath) : null,
            ]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        // make very sure file exists (retry a few times to handle slow FS)
        if (!$fullfilepath) {
            $errors = ['No file was uploaded or upload failed - please ask the Admin to check permissions for file upload!'];

            return $this->render('protocol/index.html.twig', [
                'geraet' => $geraet,
                'protocol' => $protocol ? $protocol->getProtocolName() : '',
                'errors' => $errors,
                'output' => 'Umwandlung fehlgeschlagen, erwartete Datei nicht gefunden: (leer) | Public URI: '.($filepath ?: '(leer)'),
                'controller_name' => 'ProtocolController',
            ]);
        }

        $attempts = 0;
        while (!is_file($fullfilepath) && $attempts < 10) { // up to ~2s total
            usleep(200000); // 200ms
            $attempts++;
        }

        if (!is_file($fullfilepath)) {
            $errors = ['No file was uploaded or upload failed - please ask the Admin to check permissions for file upload!'];

            return $this->render('protocol/index.html.twig', [
                'geraet' => $geraet,
                'protocol' => $protocol ? $protocol->getProtocolName() : '',
                'errors' => $errors,
                'output' => 'Umwandlung fehlgeschlagen, erwartete Datei nicht gefunden: '.($fullfilepath ?: '(leer)').' | Public URI: '.($filepath ?: '(leer)') . ' | Waited attempts: '.$attempts,
                'controller_name' => 'ProtocolController',
            ]);
        }

        // define new plain object for transport of necessary values
        $data = new \stdClass();
        $data->geraet = $geraet;
        $data->mimetype = $mime;
        $data->filepath = $filepath; // public URI used by formatters/templates
        $data->absoluteFile = $fullfilepath; // pass absolute path downstream for robustness

        $serialized_and_parsed_data = $this->convertercontext->handle($data);

        // store serialized and parsed data in session for later use by formatters
        if (!$session->isStarted()) {
            $session->start();
        }
        $session->set('serialized_and_parsed_data', $serialized_and_parsed_data);

        $this->addFlash(
            'success',
            'Die Datei wurde hochgeladen. Sie wird nun im Hintergrund analysiert und umgewandelt. Die Ausgabe erfolgt im Fenster unten.',
        );

        $errors = [
            'filetype' => $filetype,
        ];

        $formatted_data = $this->formattercontext->handle($data, $serialized_and_parsed_data, $this->format);

        // Clean up the uploaded file after conversion
        try {
            if (is_file($fullfilepath)) {
                @unlink($fullfilepath);
            }
        } catch (\Throwable $e) {
            // ignore file deletion errors but log later if logger available
        }

        return $this->render('protocol/index.html.twig', [
            'geraet' => $geraet,
            'protocol' => $protocol,
            'errors' => $errors,
            'output' => $formatted_data,
            'controller_name' => 'ProtocolController',
        ]);
    }
}
