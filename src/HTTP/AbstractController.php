<?php

namespace App\HTTP;

use JPI\HTTP\RequestAwareTrait;

abstract class AbstractController {

    use Responder;

    use RequestAwareTrait;
}
