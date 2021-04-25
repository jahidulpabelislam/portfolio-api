<?php

namespace App\Entity;

trait Validated {

    protected $errors = [];

    public function getErrors(): array {
        return $this->errors;
    }

    public function hasErrors(): bool {
        return count($this->getErrors());
    }

    protected function addError(string $field, string $error): void {
        $this->errors[$field] = $error;
    }

    public function validate(): void {
        $this->errors = []; // Always clear first
        foreach (static::getRequiredFields() as $field) {
            if (!$this->{$field}) {
                $label = static::getColumnLabel($field);
                $this->addError($field, "$label is a required field.");
            }
        }
    }

    public function save(): bool {
        $this->validate();

        if ($this->hasErrors()) {
            return false;
        }

        return parent::save();
    }

}
