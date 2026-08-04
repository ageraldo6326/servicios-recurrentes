<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

interface ContextBuilder
{
    /** @return array<string, mixed> */
    public function build(): array;
}
