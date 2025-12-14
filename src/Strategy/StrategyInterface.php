<?php

namespace App\Strategy;

interface StrategyInterface
{
    public function canProcess($data);

    /**
     * Process the given input data.
     *
     * Optionally accepts a progress callback which will be invoked with an integer percent (0-100)
     * representing converter-local progress based on file read position vs total size.
     */
    public function process($data, ?callable $onProgress = null);
}
