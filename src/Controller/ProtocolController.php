<?php

namespace App\Controller;

use App\Entity\Protocol;
use App\Entity\Geraet;
use App\Repository\ProtocolRepository;
use App\Repository\GeraetRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

class ProtocolController extends AbstractController
{
    /**
     * @Route("/process_upload/{id}", name="process_upload")
     */
    public function index(int $id): Response
    {
        $protocol = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->find($id);

        $geraet = ucfirst($protocol->getGeraet()->getGeraetName());
        $filetype = ucfirst(explode('/', $protocol->getProtocolMimeType())[1]);

        // $notifier->send(new Notification('Thank you for the feedback; your comment will be posted after moderation.', ['browser']));
        $errors = [
            'oops' => 'ein huhuh',
            'fail' => 'Mist. EIn Fehler',
            'filetype' => $filetype,
        ];

        return $this->render('protocol/index.html.twig', [
            'geraet' => $geraet,
            'protocol' => $protocol,
            'errors' => $errors,
            'controller_name' => 'ProtocolController',
        ]);
    }

    public function index2(Protocol $protocol, NotifierInterface $notifier, GeraetRepository $geraetRepository, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {

        //$geraetname = $geraetRepository->find($protocol->getGeraet());

        $notifier->send(new Notification('Thank you for the feedback; your comment will be posted after moderation.', ['browser']));
        $errors = [
            'oops' => 'ein huhuh',
            'fail' => 'Mist. EIn Fehler',
        ];

        return $this->render('protocol/index.html.twig', [
            'geraet' => $protocol->getGeraet(),
            'protocol' => $protocol,
            'errors' => $errors,
            'controller_name' => 'ProtocolController',
        ]);
    }
}
