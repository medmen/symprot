<?php

namespace App\Strategy;

class ConverterContext
{
    public function __construct(private iterable $strategies)
    {
    }

    public function addStrategy(StrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    /**
     * Handle conversion by delegating to the matching strategy.
     *
     * @param mixed $data
     * @param callable|null $onProgress optional callback receiving integer percent (0-100)
     */
    public function handle($data, ?callable $onProgress = null)
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canProcess($data)) {
                return $strategy->process($data, $onProgress);
            }
        }

        // return false;
        throw new \LogicException('Could not find a converter for this data');
    }
}
