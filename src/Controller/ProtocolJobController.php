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

        // Watchdog: mark as failed only if truly stuck at start
        try {
            $now = time();
            $createdAtTs = isset($status['createdAt']) ? strtotime((string)$status['createdAt']) : null;
            $startedAtTs = isset($status['startedAt']) ? strtotime((string)$status['startedAt']) : null;
            $st = (string)($status['status'] ?? 'queued');
            $pct = (int)($status['percent'] ?? 0);

            // Case 1: never started (still queued) for > 60s since creation
            if ($st === 'queued' && $createdAtTs && ($now - $createdAtTs) > 60) {
                $this->jobs->fail($id, 'Hintergrundprozess konnte nicht gestartet werden (Start-Timeout).');
                $status = $this->jobs->status($id) ?? $status;
            }
            // Case 2: started but still <5% for a long time (e.g. cold start) -> be more lenient (5 minutes)
            elseif ($st !== 'failed' && $pct < 5 && $startedAtTs && ($now - $startedAtTs) > 300) {
                $this->jobs->fail($id, 'Hintergrundprozess hängt beim Start (Timeout nach 5 Minuten).');
                $status = $this->jobs->status($id) ?? $status;
            }
        } catch (\Throwable $ignore) {}

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
