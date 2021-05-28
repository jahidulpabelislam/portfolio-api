<?php

namespace App\Entity;

trait Validated {

    protected $errors = [];

    public static function getRequiredColumns(): array {
        return static::$requiredColumns ?? [];
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function hasErrors(): bool {
        return count($this->getErrors());
    }

    protected function addError(string $column, string $error): void {
        $this->errors[$column] = $error;
    }

}
