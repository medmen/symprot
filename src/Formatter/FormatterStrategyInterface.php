<?php
namespace App\Formatter;

interface FormatterStrategyInterface
{
    public function canFormat($data);
    public function format($data);
}
