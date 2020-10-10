<?php

namespace App\HTTP;

use App\Utils\ArrayCollection;
use DateTime;

class Headers extends ArrayCollection {

    public function set($name, $value) {
        if ($name === "Last-Modified" && $value instanceof DateTime) {
            $value->setTimezone(Response::getCacheTimeZone());
            $value = $value->format("D, j M Y H:i:s") . " GMT";
        }

        parent::set($name, $value);
    }

}
