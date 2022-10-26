<?php

namespace App\Entity\API;

use Exception;
use Throwable;

class InvalidDataException extends Exception
{
    protected $invalidErrors;

    public function __construct($message = "", $code = 0, Throwable $previous = null, array $invalidErrors = [])
    {
        parent::__construct($message, $code, $previous);
        $this->invalidErrors = $invalidErrors;
    }

    public function getInvalidErrors(): array {
        return $this->invalidErrors;
    }
}
