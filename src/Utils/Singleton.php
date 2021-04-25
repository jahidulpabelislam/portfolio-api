<?php

namespace App\Utils;

trait Singleton {

    protected static $instance = null;

    /**
     * @return static
     */
    public static function get() {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Singleton constructor.
     * By default don't allow creating instances of the class outside the getter
     */
    protected function __construct()
    {
    }

}
