<?php

namespace App;

abstract class AbstractUser {

    protected static $requiredColumns = [
        "username",
        "password",
    ];

    public static function getRequiredColumns(): array {
        return static::$requiredColumns;
    }

    public static function getErrors(array $data): array {
        $errors = [];
        foreach (static::getRequiredColumns() as $field) {
            if (!Core::isFieldValid($data, $field)) {
                $errors[$field] = "$field is a required field.";
            }
        }

        return $errors;
    }

    public static function hasErrors(array $data): bool {
        foreach (static::getRequiredColumns() as $field) {
            if (!Core::isFieldValid($data, $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Authenticate a user trying to login
     * If successful store generate JWT and return, else return null
     *
     * @param $data array Submitted data to support in login attempt
     * @return string|null
     */
    abstract public static function login(array $data): ?string;

    /**
     * Do the log out here (e.g removing cookie, session or database etc.)
     *
     * @return bool
     */
    abstract public static function logout(): bool;

    /**
     * Check whether the current user is logged in (e.g check against stored cookie, session or database etc.)
     *
     * @return bool
     */
    abstract public static function isLoggedIn(): bool;

}
