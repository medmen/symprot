<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

class ProtocolController extends AbstractController
{
    /**
     * @Route("/process_upload", name="process_upload")
     */
    public function index(): Response
    {
        $notifier->send(new Notification('Thank you for the feedback; your comment will be posted after moderation.', ['browser']));

        return $this->render('protocol/index.html.twig', [
            'controller_name' => 'ProtocolController',
        ]);
    }
}
