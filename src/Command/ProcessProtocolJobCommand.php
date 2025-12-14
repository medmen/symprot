<?php

namespace App\Command;

use App\Formatter\FormatterContext;
use App\Service\JobManager;
use App\Strategy\ConverterContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:process-protocol-job', description: 'Process a protocol conversion job in the background')]
class ProcessProtocolJobCommand extends Command
{
    public function __construct(
        private readonly JobManager $jobs,
        private readonly ConverterContext $converter,
        private readonly FormatterContext $formatter,
        private readonly LoggerInterface $logger,
        private readonly string $appUploadsDir,
        private readonly int $jobMaxSeconds,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Job ID');
        $this->logger->debug('ProcessProtocolJobCommand::configure was called.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->debug('ProcessProtocolJobCommand::execute was called.');
        $output->writeln("worker booting..."); // for debugging

        $id = (string) $input->getArgument('id');
        $payload = $this->jobs->payload($id);
        if (!$payload) {
            $this->logger->error('ProcessProtocolJobCommand::configure payload not found for job with id '.$id.' !');
            $output->writeln('<error>Payload not found</error>');
            return Command::FAILURE;
        }

        $startedAt = time();
        $deadline = $startedAt + max(60, $this->jobMaxSeconds);

        try {
            $this->jobs->update($id, 5, 'Starte Verarbeitung');

            $data = new \stdClass();
            $data->geraet = (string) ($payload['geraet'] ?? '');
            $data->mimetype = (string) ($payload['mimetype'] ?? '');
            $data->filename = (string) ($payload['filename'] ?? '');
            $format = (string) ($payload['format'] ?? 'html');
            $fullFile = rtrim($this->appUploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $data->filename;

            // Safety: ensure file exists before heavy work
            if (!is_file($fullFile)) {
                $this->jobs->fail($id, 'Datei nicht gefunden: ' . $fullFile);
                return Command::FAILURE;
            }

            $this->jobs->update($id, 20, 'Datei eingelesen');

            $lastReported = -1;
            $serialized = $this->converter->handle($data, function (int $percent) use (&$lastReported, $id, $deadline) {
                if (time() > $deadline) {
                    throw new \RuntimeException('Zeitüberschreitung: Verarbeitung länger als erlaubt.');
                }
                // Map converter 0-100% to job 20-60%
                $jobPercent = 20 + (int) floor($percent * 0.4);
                if ($jobPercent > 60) { $jobPercent = 60; }
                if ($jobPercent !== $lastReported) {
                    $lastReported = $jobPercent;
                    try {
                        $this->jobs->update($id, $jobPercent, 'Umwandlung läuft: ' . $percent . '%');
                    } catch (\Throwable $ignore) {}
                }
            });
            if (time() > $deadline) {
                throw new \RuntimeException('Zeitüberschreitung nach der Umwandlung.');
            }
            $this->jobs->update($id, 60, 'Umwandlung abgeschlossen');

            $formatted = $this->formatter->handle($data, $serialized, $format);
            if (time() > $deadline) {
                throw new \RuntimeException('Zeitüberschreitung bei der Formatierung.');
            }
            $this->jobs->update($id, 90, 'Formatierung abgeschlossen');

            $outPath = $this->jobs->outputPath($id);
            file_put_contents($outPath, (string) $formatted);
            $this->jobs->complete($id, $outPath, [
                'filename' => $data->filename,
                'format' => $format,
                'durationSeconds' => time() - $startedAt,
            ]);

            // Delete uploaded file after success
            try {
                // if (is_file($fullFile)) { @unlink($fullFile); }
                $this->jobs->update($id, 99, 'jetzt würde ich das XML löschen');
            } catch (\Throwable $ignore) {}

            $this->logger->info('ProcessProtocolJobCommand::execute was run successfully for job ' . $id . '!');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            try {
                $this->logger->error('Background job failed', [
                    'job' => $id,
                    'exception' => get_class($e),
                    'message' => $msg,
                ]);
            } catch (\Throwable $ignore) {}
            $this->jobs->fail($id, $msg);
            return Command::FAILURE;
        }
    }
}
