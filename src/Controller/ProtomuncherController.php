<?php

namespace App\Controller;

use App\Form\ProtocoluploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProtomuncherController extends AbstractController
{
    #[Route(path: '/', name: 'index')]
    public function index(Request $request, NotifierInterface $notifier, SessionInterface $session): Response
    {
        $errors = [];

        $form = $this->createForm(ProtocoluploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('protocolFile')->getData();
            $geraet = (string) ($form->get('geraet')->getData() ?? '');

            if (!$uploadedFile) {
                $errors[] = 'Keine Datei hochgeladen.';
            } else {
                // Save to /tmp with a unique name
                $fs = new Filesystem();
                $targetDir = $this->getParameter('app.uploads_dir');
                if (!$fs->exists($targetDir)) {
                    try { $fs->mkdir($targetDir, 0775); } catch (\Throwable $e) {
                        $errors[] = 'Upload-Verzeichnis konnte nicht erstellt werden: '.$e->getMessage();
                    }
                }

                if (!$errors && !is_writable($targetDir)) {
                    $errors[] = 'Upload-Verzeichnis ist nicht schreibbar: '.$targetDir;
                }

                if (!$errors) {
                    $safeName = bin2hex(random_bytes(8)).'__'.preg_replace('~[^A-Za-z0-9._-]+~', '_', (string) $uploadedFile->getClientOriginalName());
                    try {
                        $uploadedFile->move($targetDir, $safeName);
                    } catch (\Throwable $e) {
                        $errors[] = 'Upload fehlgeschlagen: '.$e->getMessage();
                    }

                    if (!$errors) {
                        $notifier->send(new Notification('Upload erfolgreich!', ['browser']));
                        $this->addFlash('success', 'Upload erfolgreich!');

                        // Build processing URL with file path (URL-encoded) and optional geraet
                        $params = ['path' => $safeName];
                        if ($geraet !== '') { $params['geraet'] = $geraet; }
                        $processUrl = $this->generateUrl('process_upload', $params);

                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse([
                                'status' => 'ok',
                                'processUrl' => $processUrl,
                                'path' => $safeName,
                            ], 200);
                        }

                        return $this->redirect($processUrl);
                    }
                }
            }

            return $this->render('protomuncher/upload.html.twig', [
                'form' => $form->createView(),
                'protocol' => null,
                'errors' => $errors,
                'controller_name' => 'ProtomuncherController',
            ]);
        }

        $notifier->send(new Notification('Bitte eine Datei hochladen!', ['browser']));

        return $this->render('protomuncher/upload.html.twig', [
            'form' => $form->createView(),
            'protocol' => null,
            'errors' => $errors,
            'controller_name' => 'ConferenceController',
        ]);
    }
}
