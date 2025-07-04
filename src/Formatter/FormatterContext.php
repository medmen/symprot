<?php

namespace App\Formatter;

class FormatterContext
{
    public function __construct(private iterable $strategies)
    {
    }

    public function addStrategy(FormatterStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function handle($modality_and_mime, $serialized_payload, $format)
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canFormat($modality_and_mime, $format)) {
                return $strategy->format($serialized_payload, $format);
            }
        }

        // return false;
        throw new \LogicException('Could not find a Formatter for '.$modality_and_mime->geraet.' and '.$modality_and_mime->mimetype);
    }
}
