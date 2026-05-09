<?php

namespace App\Exceptions;

use RuntimeException;

class PackingException extends RuntimeException
{
    public function __construct(string $message, public readonly string $code_key = 'error')
    {
        parent::__construct($message);
    }
}
