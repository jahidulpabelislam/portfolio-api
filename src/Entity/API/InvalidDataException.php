<?php

namespace App\Entity\API;

use Exception;
use Throwable;

class InvalidDataException extends Exception
{
    protected $errors;

    public function __construct($message = "", $code = 0, Throwable $previous = null, array $invalidErrors = [])
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $invalidErrors;
    }

    public function getErrors(): array {
        return $this->errors;
    }
}
