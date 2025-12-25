<?php

namespace App\Formatter;

use App\Service\StatusLogger;

class FormatterContext
{
    private ?StatusLogger $statusLogger = null;
    private ?string $statusToken = null;

    public function __construct(private iterable $strategies)
    {
    }

    public function addStrategy(FormatterStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function withStatus(StatusLogger $logger, string $token): self
    {
        $this->statusLogger = $logger;
        $this->statusToken = $token;
        return $this;
    }

    public function handle($modality_and_mime, $serialized_payload, $format)
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canFormat($modality_and_mime, $format)) {
                if ($this->statusLogger && $this->statusToken) {
                    $this->statusLogger->append($this->statusToken, 'Formatter selected: ' . get_class($strategy) . ' for format ' . $format);
                }
                $result = $strategy->format($serialized_payload, $format);
                if ($this->statusLogger && $this->statusToken) {
                    $this->statusLogger->append($this->statusToken, 'Formatter finished');
                }
                return $result;
            }
        }

        if ($this->statusLogger && $this->statusToken) {
            $this->statusLogger->append($this->statusToken, 'No matching formatter strategy found for format ' . $format);
        }
        throw new \LogicException('Could not find a Formatter for ' . ($modality_and_mime->geraet ?? '?') . ' and ' . ($modality_and_mime->mimetype ?? '?'));
    }
}
