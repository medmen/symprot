<?php

namespace App\Strategy;

use App\Service\StatusLogger;

class ConverterContext
{
    private ?StatusLogger $statusLogger = null;
    private ?string $statusToken = null;

    public function __construct(private iterable $strategies)
    {
    }

    public function addStrategy(StrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function withStatus(StatusLogger $logger, string $token): self
    {
        $clone = clone $this;
        $clone->statusLogger = $logger;
        $clone->statusToken = $token;
        return $clone;
    }

    public function __clone()
    {
        // strategies is an iterable; shallow copy is fine.
    }

    public function handle($data)
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canProcess($data)) {
                if ($this->statusLogger && $this->statusToken) {
                    $this->statusLogger->append($this->statusToken, 'Converter selected: ' . get_class($strategy));
                }
                $result = $strategy->process($data);
                if ($this->statusLogger && $this->statusToken) {
                    $this->statusLogger->append($this->statusToken, 'Converter finished');
                }
                return $result;
            }
        }

        if ($this->statusLogger && $this->statusToken) {
            $this->statusLogger->append($this->statusToken, 'No matching converter strategy found');
        }
        throw new \LogicException('Could not find a converter for this data');
    }
}
