<?php

namespace App\Contracts;

interface AIServiceInterface
{
    public function summarize(string $text): string;
}
