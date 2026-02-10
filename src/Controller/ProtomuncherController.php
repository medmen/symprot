<?php

namespace App\Controller;

use App\Form\ProtocoluploadType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
// use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProtomuncherController extends AbstractController
{

    public function __construct(
        private LoggerInterface $logger)
    {
        // $projectDir wird via services.yaml bind ($projectDir: '%kernel.project_dir%') injiziert
    }

    #[Route(path: '/', name: 'index')]
    public function index(Request $request, NotifierInterface $notifier, SessionInterface $session): Response
    {
        $errors = [];
        $this->logger->debug('ProtomuncherController: function index starts');
        $form = $this->createForm(ProtocoluploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->debug('ProtomuncherController: got a submitted form');

            // Initialize status logging for the upload process
            $token = bin2hex(random_bytes(12));
            if (!$session->isStarted()) {
                $session->start();
            }
            $session->set('status_token', $token);

            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('protocolFile')->getData();
            $geraet = (string) ($form->get('geraet')->getData() ?? '');

            if (!$uploadedFile) {
                $errors[] = 'Keine Datei hochgeladen.';
            } else {
                $this->logger->debug('ProtomuncherController: start file processing with ', ['filename' => $uploadedFile->getClientOriginalName(), 'geraet' => $geraet]);

                // Save to /tmp with a unique name
                $fs = new Filesystem();
                $targetDir = $this->getParameter('app.uploads_dir');
                if (!$fs->exists($targetDir)) {
                    $this->logger->debug('ProtomuncherController: in file processing creating uploads dir ', ['targetDir' => $targetDir]);
                    try { $fs->mkdir($targetDir, 0775); } catch (\Throwable $e) {
                        $errors[] = 'Upload-Verzeichnis konnte nicht erstellt werden: '.$e->getMessage();
                        $this->logger->debug('ProtomuncherController: in file processing creating uploads dir failed', ['targetDir' => $targetDir]);
                    }
                }

                if (!$errors && !is_writable($targetDir)) {
                    $errors[] = 'Upload-Verzeichnis ist nicht schreibbar: '.$targetDir;
                    $this->logger->debug('ProtomuncherController: in file processing creating uploads dir is not writable', ['targetDir' => $targetDir]);
                }

                if (!$errors) {
                    $safeName = bin2hex(random_bytes(8)).'__'.preg_replace('~[^A-Za-z0-9._-]+~', '_', (string) $uploadedFile->getClientOriginalName());
                    try {
                        $uploadedFile->move($targetDir, $safeName);
                        $this->logger->debug('ProtomuncherController: in file processing moved file to ', ['targetDir' => $targetDir, 'safeName' => $safeName]);

                    } catch (\Throwable $e) {
                        $errors[] = 'Upload fehlgeschlagen: '.$e->getMessage();
                        $this->logger->debug('ProtomuncherController: in file processing moving the file failed ', ['targetDir' => $targetDir, 'safeName' => $safeName]);
                    }

                    if (!$errors) {
                        $this->logger->debug('ProtomuncherController: in file processing upload successful');

                        // $notifier->send(new Notification('Upload erfolgreich!', ['browser']));
                        $this->addFlash('success', 'Upload erfolgreich!');

                        // Build processing URL with file path (URL-encoded) and optional geraet
                        $params = ['path' => $safeName];
                        if ($geraet !== '') { $params['geraet'] = $geraet; }
                        $processUrl = $this->generateUrl('process_upload', $params);
                        $this->logger->debug('ProtomuncherController: in file processing genereate url for redirect', ['processUrl' => $processUrl]);

                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse([
                                'status' => 'ok',
                                'processUrl' => $processUrl,
                                'path' => $safeName,
                                'token' => $token,
                            ], 200);
                        }

                        return $this->redirect($processUrl);
                    }
                }
            }

            if ($errors) {
                $this->logger->debug('ProtomuncherController: in file processing, handle errors and show error form');
            }

            return $this->render('protomuncher/upload.html.twig', [
                'form' => $form->createView(),
                'protocol' => null,
                'errors' => $errors,
                'controller_name' => 'ProtomuncherController',
            ]);
        }

        // $notifier->send(new Notification('Bitte eine Datei hochladen!', ['browser']));

        return $this->render('protomuncher/upload.html.twig', [
            'form' => $form->createView(),
            'protocol' => null,
            'errors' => $errors,
            'controller_name' => 'ProtomuncherController',
        ]);
    }
}
