<?php

declare(strict_types=1);

namespace App\Service;

interface IConverter
{
    public function setinput(string $input): void;

    public function convert(): array;
}
