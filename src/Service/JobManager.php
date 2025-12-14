<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class JobManager
{
    private string $jobsRoot;
    private Filesystem $fs;

    public function __construct(string $projectDir)
    {
        $this->fs = new Filesystem();
        $this->jobsRoot = rtrim($projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'procjobs';
        if (!$this->fs->exists($this->jobsRoot)) {
            try { $this->fs->mkdir($this->jobsRoot, 0775); } catch (\Throwable $e) {}
        }
    }

    public function createJob(array $payload): string
    {
        $id = bin2hex(random_bytes(12));
        $dir = $this->dir($id);
        $this->fs->mkdir($dir, 0775);
        $this->writeJson($dir . '/payload.json', $payload);
        $this->writeJson($dir . '/status.json', [
            'id' => $id,
            'status' => 'queued',
            'percent' => 0,
            'message' => 'Queued',
            'error' => null,
            'createdAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'startedAt' => null,
            'finishedAt' => null,
        ]);
        return $id;
    }

    public function payload(string $id): array
    {
        return $this->readJson($this->dir($id) . '/payload.json') ?? [];
    }

    public function status(string $id): array
    {
        return $this->readJson($this->dir($id) . '/status.json') ?? [];
    }

    public function update(string $id, int $percent, string $message): void
    {
        $st = $this->status($id);
        if (!$st) { $st = ['id'=>$id]; }
        $st['status'] = 'running';
        $st['percent'] = max(0, min(100, $percent));
        $st['message'] = $message;
        if (empty($st['startedAt'])) { $st['startedAt'] = (new \DateTimeImmutable())->format(DATE_ATOM); }
        $this->writeJson($this->dir($id) . '/status.json', $st);
    }

    public function complete(string $id, string $outputPath, ?array $meta = null): void
    {
        $st = $this->status($id);
        if (!$st) { $st = ['id'=>$id]; }
        $st['status'] = 'done';
        $st['percent'] = 100;
        $st['message'] = 'Fertig';
        $st['finishedAt'] = (new \DateTimeImmutable())->format(DATE_ATOM);
        if ($meta) { $st['meta'] = $meta; }
        $st['output'] = basename($outputPath);
        $this->writeJson($this->dir($id) . '/status.json', $st);
    }

    public function fail(string $id, string $errorMessage): void
    {
        $st = $this->status($id);
        if (!$st) { $st = ['id'=>$id]; }
        $st['status'] = 'failed';
        $st['percent'] = $st['percent'] ?? 0;
        $st['message'] = 'Fehler';
        $st['error'] = $errorMessage;
        $st['finishedAt'] = (new \DateTimeImmutable())->format(DATE_ATOM);
        $this->writeJson($this->dir($id) . '/status.json', $st);
    }

    public function dir(string $id): string
    {
        return $this->jobsRoot . DIRECTORY_SEPARATOR . $id;
    }

    public function outputPath(string $id): string
    {
        return $this->dir($id) . DIRECTORY_SEPARATOR . 'output.html';
    }

    private function writeJson(string $path, array $data): void
    {
        $this->fs->dumpFile($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function readJson(string $path): ?array
    {
        if (!is_file($path)) { return null; }
        $content = file_get_contents($path);
        if ($content === false) { return null; }
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }
}
