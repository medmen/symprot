<?php
namespace App\Strategy;

interface StrategyInterface
{
    public function canProcess($data);
    public function process($data);
}
