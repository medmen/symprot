<?php

namespace App\Controller;

use App\Entity\Protocol;
use App\Formatter\FormatterContext;
use App\Strategy\ConverterContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProtocolController extends AbstractController
{
    private $kernel;

    private $format;

    public function __construct(private ConverterContext $convertercontext, private FormatterContext $formattercontext, KernelInterface $kernel)
    {
        $this->kernel = $kernel->getProjectDir();
    }

    #[Route(path: '/process_upload/{id}', name: 'process_upload', methods: ['GET'])]
    public function index(int $id, ConverterContext $converterContext, FormatterContext $formattercontext, NotifierInterface $notifier, EntityManagerInterface $entityManager): Response
    {
        $request = Request::createFromGlobals();
        $this->format = $request->query->get('format') ?? 'html'; // make sure we have a default

        $protocol = $entityManager
            ->getRepository(Protocol::class)
            ->find($id);

        $geraet = ucfirst($protocol->getGeraet()->getGeraetName());
        $filetype = ucfirst(explode('/', $protocol->getProtocolMimeType())[1]);
        $uploadDir = $this->getParameter('vich_uploader.mappings')['protocolFile']['uri_prefix'];
        $filepath = $uploadDir.'/'.$protocol->getProtocolName();
        $fullfilepath = $this->kernel.'/public'.$filepath;

        // make very sure file exists
        if (false == file_exists($fullfilepath)) {
            $errors[] = 'No file was uploaded or upload failed - please ask the Admin to check permissions for file upload!';

            return $this->render('protocol/index.html.twig', [
                'geraet' => $geraet,
                'protocol' => $protocol->getProtocolName(),
                'errors' => $errors,
                'output' => 'Umwandlung fehlgeschlagen, '.$fullfilepath.' enthält keine Datei',
                'controller_name' => 'ProtocolController',
            ]);
        }

        // define new plain object for transport of necessary values
        $data = new \stdClass();
        $data->geraet = $geraet;
        $data->mimetype = $protocol->getProtocolMimeType();
        $data->filepath = $filepath;

        $serialized_and_parsed_data = $this->convertercontext->handle($data);
        // can we store this in session?

        $notifier->send(new Notification(
            '<h2> Die Datei wurde hochgeladen.</h2>
                    <p>Sie wird nun im Hintergrund analysiert und umgewandelt. <br>
                    Die Ausgabe erfolgt im Fenster unten. <br>
                    </p>',
            ['browser']));

        $errors = [
            'filetype' => $filetype,
        ];

        $formatted_data = $this->formattercontext->handle($data, $serialized_and_parsed_data, $this->format);

        // Clean up the uploaded file after processing
        // unlink($fullfilepath);

        return $this->render('protocol/index.html.twig', [
            'geraet' => $geraet,
            'protocol' => $protocol,
            'errors' => $errors,
            'output' => $formatted_data,
            'controller_name' => 'ProtocolController',
        ]);
    }
}
