<?php

namespace App\Exceptions;

use Exception;

class InsufficientCreditsException extends Exception
{
    public function __construct(public readonly int $available, public readonly int $required)
    {
        parent::__construct("Insufficient credits: have {$available}, need {$required}.");
    }
}
