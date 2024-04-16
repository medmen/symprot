<?php

namespace App\Formatter;

interface FormatterStrategyInterface
{
    public function canFormat($data, $format);

    public function format($data, $format);
}
