<?php

namespace App\Controller;

use App\Entity\Protocol;
use App\Form\ProtocoluploadType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
