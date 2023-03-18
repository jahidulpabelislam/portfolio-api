<?php

declare(strict_types=1);

namespace App\Auth;

use JPI\HTTP\Request;
use App\Utils\Str;

abstract class AbstractManager {

    protected static array $requiredColumns = [
        "username",
        "password",
    ];

    public static function getErrors(Request $request): array {
        $data = $request->getArrayFromBody();
        $errors = [];
        foreach (static::$requiredColumns as $column) {
            if (empty($data[$column])) {
                $label = Str::machineToDisplay($column);
                $errors[$column] = "$label is required.";
            }
        }

        return $errors;
    }

    /**
     * Authenticate a user trying to login
     * If successful store generate JWT and return, else return null
     */
    abstract public static function login(Request $request): ?string;

    /**
     * Do the log out here (e.g removing cookie, session or database etc.)
     */
    abstract public static function logout(Request $request): bool;

    /**
     * Check whether the current user is logged in (e.g check against stored cookie, session or database etc.)
     */
    abstract public static function isAuthenticated(Request $request): bool;
}
