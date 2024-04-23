<?php

namespace App\Controller;

use App\Entity\Config;
use App\Form\ConfigType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigController extends AbstractController
{
    public function __construct(private \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }
    #[Route(path: '/config', name: 'config')]
    public function index(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, NotifierInterface $notifier, SessionInterface $session): Response
    {
        // creates a protocol object and initialize
        $config = new Config();
        // always set default values
        $config->setDebug(false);
        $config->setLimitPages(0);
        $config->setOutputFormat('dokuwiki');
        $config->setStripUnits(true);

        $errors = [];

        $form = $this->createForm(ConfigType::class, $config);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $protocol = $form->getData();

            $errors = $validator->validate($protocol);
            if (count($errors) > 0) {
                return $this->render('config/index.html.twig', [
                    'form' => $form->createView(),
                    'protocol' => $protocol,
                    'errors' => $errors,
                    'controller_name' => 'ConfigController',
                ]);
            }

            // ... perform some action, such as saving the task to the database
            // for example, if Task is a Doctrine entity, save it!
            $entityManager = $this->managerRegistry->getManager();
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
