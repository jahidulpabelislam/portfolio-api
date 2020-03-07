<?php

namespace App;

abstract class Controller {

    use Responder;

    protected $api = null;

    public function __construct(Core $api) {
        $this->api = $api;
    }

}
