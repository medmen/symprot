<?php

namespace App\Controller;

use App\Entity\Protocol;
use App\Form\ProtocoluploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Config;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ConfigController extends AbstractController
{
    /**
     * @Route("/config", name="config")
     */
    public function index(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, NotifierInterface $notifier, SessionInterface $session): Response
    {
        // creates a protocol object and initialize
        $config = new Config();
        // always set default values
        $config->setDebug(false);
        $config->setLimitPages(0);
        $config->setOutputFormat('dokuwiki');
        $config->setStripUnits(true);

        $errors = array();

        $form = $this->createForm(ConfigType::class, $config);

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
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($protocol);
            $entityManager->flush();

            $notifier->send(new Notification('Upload erfolgreich!', ['browser']));

            $this->addFlash(
                'success', 'Upload erfolgreich!'
            );

            return $this->redirectToRoute('process_upload', ['id' => $protocol->getId()]);
        }


        return $this->render('config/index.html.twig', [
            'controller_name' => 'ConfigController',
        ]);

    }
}
