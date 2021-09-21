<?php

declare(strict_types=1);

namespace App\Service;

interface IConverter
{
    function setinput(string $input): void;

    function convert(): array;
}
