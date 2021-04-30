<?php

namespace App\Entity;

use App\Utils\StringHelper;

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

    public function validate(): void {
        $this->errors = []; // Always clear first
        foreach (static::getRequiredColumns() as $column) {
            if (!$this->{$column}) {
                $label = StringHelper::machineToDisplay($column);
                $this->addError($column, "$label is a required field.");
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
