<?php

/**
 * Sets Constants for Connection to the Database & API's Auth
 * as well as other settings.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @since Class available since Release: v3.0.0
 * @copyright 2010-2020 JPI
 */

namespace App;

use App\Utils\Singleton;

class Config {

    use Singleton;

    protected $values = [];

    public function __set(string $key, $value) {
        $this->values[$key] = $value;
    }

    public function __get(string $key) {
        return $this->values[$key] ?? null;
    }

}
