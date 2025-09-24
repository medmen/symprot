<?php

namespace App\Controller;

use App\Entity\Protocol;
use App\Form\ProtocoluploadType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProtomuncherController extends AbstractController
{
    #[Route(path: '/', name: 'index')]
    public function index(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, NotifierInterface $notifier, SessionInterface $session): Response
    {
        // creates a protocol object and initialize
        $protocol = new Protocol();
        $errors = [];

        $form = $this->createForm(ProtocoluploadType::class, $protocol);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $protocol = $form->getData();

            $errors = $validator->validate($protocol);
            if (count($errors) > 0) {
                return $this->render('protomuncher/upload.html.twig', [
                    'form' => $form->createView(),
                    'protocol' => $protocol,
                    'errors' => $errors,
                    'controller_name' => 'ProtomuncherController',
                ]);
            }

            // Before persisting, ensure the upload destination exists and is writable
            $vichMappings = $this->getParameter('vich_uploader.mappings');
            $uploadDestination = $vichMappings['protocolFile']['upload_destination'] ?? null;

            $fs = new Filesystem();
            $preUploadErrors = [];
            if (!$uploadDestination) {
                $preUploadErrors[] = 'Upload destination for files is not configured.';
            } else {
                // Attempt to create the directory if it does not exist
                if (!$fs->exists($uploadDestination)) {
                    try {
                        $fs->mkdir($uploadDestination, 0775);
                    } catch (\Throwable $e) {
                        $preUploadErrors[] = 'Cannot create upload directory: '.$uploadDestination.' ('.$e->getMessage().')';
                    }
                }
                // Check writability
                if (!$preUploadErrors && !is_writable($uploadDestination)) {
                    $preUploadErrors[] = 'Upload directory is not writable: '.$uploadDestination.'. Please adjust permissions (e.g., chown/chmod) for the web server user.';
                }
            }

            if ($preUploadErrors) {
                return $this->render('protomuncher/upload.html.twig', [
                    'form' => $form->createView(),
                    'protocol' => $protocol,
                    'errors' => $preUploadErrors,
                    'controller_name' => 'ProtomuncherController',
                ]);
            }

            // ... perform some action, such as saving the task to the database
            // for example, if Task is a Doctrine entity, save it!
            $entityManager->persist($protocol);
            $entityManager->flush();

            $notifier->send(new Notification('Upload erfolgreich!', ['browser']));

            $this->addFlash(
                'success', 'Upload erfolgreich!'
            );

            return $this->redirectToRoute('process_upload', ['id' => $protocol->getId()]);
        }

        $notifier->send(new Notification('Bitte eine Datei hochladen!', ['browser']));

        return $this->render('protomuncher/upload.html.twig', [
            'form' => $form->createView(),
            'protocol' => $protocol,
            'errors' => $errors,
            'controller_name' => 'ConferenceController',
        ]);
    }
}
