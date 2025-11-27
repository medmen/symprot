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
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Job ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $payload = $this->jobs->payload($id);
        if (!$payload) {
            $output->writeln('<error>Payload not found</error>');
            return Command::FAILURE;
        }

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

            $serialized = $this->converter->handle($data);
            $this->jobs->update($id, 60, 'Umwandlung abgeschlossen');

            $formatted = $this->formatter->handle($data, $serialized, $format);
            $this->jobs->update($id, 90, 'Formatierung abgeschlossen');

            $outPath = $this->jobs->outputPath($id);
            file_put_contents($outPath, (string) $formatted);
            $this->jobs->complete($id, $outPath, [
                'filename' => $data->filename,
                'format' => $format,
            ]);

            // Delete uploaded file after success
            try {
                if (is_file($fullFile)) { @unlink($fullFile); }
            } catch (\Throwable $ignore) {}

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            try {
                $this->logger->error('Background job failed', [
                    'job' => $id,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            } catch (\Throwable $ignore) {}
            $this->jobs->fail($id, $e->getMessage());
            return Command::FAILURE;
        }
    }
}
