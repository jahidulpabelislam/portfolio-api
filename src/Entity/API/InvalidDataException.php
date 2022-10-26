<?php

namespace App\Entity\API;

use Exception;
use Throwable;

class InvalidDataException extends Exception
{
    protected $errors;

    public function __construct(array $errors, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->errors = $errors;

        parent::__construct($message, $code, $previous);
    }

    public function getErrors(): array {
        return $this->errors;
    }
}
