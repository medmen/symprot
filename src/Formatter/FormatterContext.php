<?php
namespace App\Formatter;

use App\Formatter\FormatterStrategyInterface;
use Symfony\Component\Mime\Exception\LogicException;

class FormatterContext
{
    private iterable $strategies = [];

    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    public function addStrategy(FormatterStrategyInterface $strategy)
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