<?php

namespace App\Entity;

trait Validated {

    public function getErrors(): array {
        $errors = [];
        foreach (static::getRequiredFields() as $field) {
            if (!$this->{$field}) {
                $errors[$field] = "$field is a required field.";
            }
        }

        return $errors;
    }

    public function hasErrors(): bool {
        return count($this->getErrors());
    }

}
