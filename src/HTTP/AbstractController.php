<?php

namespace App\HTTP;

use JPI\HTTP\Request;

abstract class AbstractController {

    use Responder;

    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function getRequest(): Request {
        return $this->request;
    }
}
