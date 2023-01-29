<?php

namespace App\HTTP;

use App\Core;
use JPI\HTTP\Request as PackageRequest;
use JPI\Utils\URL;

class Request extends PackageRequest {

    /**
     * Generates a full URL of current request
     *
     * @return URL
     */
    public function getURL(): URL {
        return Core::get()->makeFullURL($this->uri);
    }
}
