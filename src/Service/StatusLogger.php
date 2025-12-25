<?php

namespace App\Service;

class StatusLogger
{
    private string $statusDir;

    public function __construct(private string $projectDir)
    {
        $this->statusDir = rtrim($projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'status';
        if (!is_dir($this->statusDir)) {
            @mkdir($this->statusDir, 0775, true);
        }
    }

    public function getFilePath(string $token): string
    {
        $token = preg_replace('/[^a-zA-Z0-9_-]/', '_', $token);
        return $this->statusDir . DIRECTORY_SEPARATOR . $token . '.log';
    }

    public function init(string $token, array $meta = []): void
    {
        $path = $this->getFilePath($token);
        $header = '[START] ' . date('c') . "\n";
        if ($meta) {
            $header .= '[META] ' . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n";
        }
        file_put_contents($path, $header);
    }

    public function append(string $token, string $message): void
    {
        $path = $this->getFilePath($token);
        $line = '[' . date('H:i:s') . '] ' . $message . "\n";
        file_put_contents($path, $line, FILE_APPEND);
    }

    public function complete(string $token, bool $ok = true, ?string $message = null): void
    {
        $path = $this->getFilePath($token);
        $line = '[END ' . ($ok ? 'OK' : 'FAIL') . '] ' . date('c');
        if ($message) {
            $line .= ' - ' . $message;
        }
        $line .= "\n";
        file_put_contents($path, $line, FILE_APPEND);
    }

    public function read(string $token): array
    {
        $path = $this->getFilePath($token);
        $lines = [];
        $done = false;
        if (is_file($path)) {
            $content = file($path, FILE_IGNORE_NEW_LINES);
            $lines = $content === false ? [] : $content;
            foreach (array_slice($lines, -3) as $l) {
                if (str_starts_with($l, '[END ')) {
                    $done = true;
                    break;
                }
            }
        }
        return ['lines' => $lines, 'done' => $done];
    }

    public function clear(string $token): void
    {
        $path = $this->getFilePath($token);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
