<?php

namespace App\Auth;

use App\HTTP\Request;
use App\Utils\StringHelper;

abstract class AbstractManager {

    protected static $requiredColumns = [
        "username",
        "password",
    ];

    public static function getErrors(Request $request): array {
        $data = $request->data;
        $errors = [];
        foreach (static::$requiredColumns as $column) {
            if (empty($data[$column])) {
                $label = StringHelper::machineToDisplay($column);
                $errors[$column] = "$label is required.";
            }
        }

        return $errors;
    }

    /**
     * Authenticate a user trying to login
     * If successful store generate JWT and return, else return null
     *
     * @param $request Request
     * @return string|null
     */
    abstract public static function login(Request $request): ?string;

    /**
     * Do the log out here (e.g removing cookie, session or database etc.)
     *
     * @param $request Request
     * @return bool
     */
    abstract public static function logout(Request $request): bool;

    /**
     * Check whether the current user is logged in (e.g check against stored cookie, session or database etc.)
     *
     * @param $request Request
     * @return bool
     */
    abstract public static function isLoggedIn(Request $request): bool;
}
