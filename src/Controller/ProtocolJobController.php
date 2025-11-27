<?php

namespace App\Controller;

use App\Service\JobManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProtocolJobController extends AbstractController
{
    public function __construct(private readonly JobManager $jobs)
    {
    }

    #[Route(path: '/process_status/{id}', name: 'process_status', methods: ['GET'])]
    public function status(string $id): JsonResponse
    {
        $status = $this->jobs->status($id);
        if (!$status) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }
        return new JsonResponse([
            'id' => $status['id'] ?? $id,
            'status' => $status['status'] ?? 'unknown',
            'percent' => $status['percent'] ?? 0,
            'message' => $status['message'] ?? '',
            'error' => $status['error'] ?? null,
        ]);
    }

    #[Route(path: '/process_output/{id}', name: 'process_output', methods: ['GET'])]
    public function output(string $id): Response
    {
        $dir = $this->jobs->dir($id);
        $out = $this->jobs->outputPath($id);
        if (!is_file($out)) {
            return new Response('Ausgabe noch nicht verfügbar.', 202, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        $html = (string) file_get_contents($out);
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
