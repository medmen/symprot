<?php

namespace App\Strategy;

class ConverterContext
{
    private iterable $strategies = [];

    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    public function addStrategy(StrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function handle($data)
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canProcess($data)) {
                return $strategy->process($data);
            }
        }

        // return false;
        throw new \LogicException('Could not find a converter for this data');
    }
}
