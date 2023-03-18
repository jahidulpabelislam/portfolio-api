<?php

declare(strict_types=1);

namespace App\HTTP;

use JPI\HTTP\RequestAwareTrait;

abstract class AbstractController {

    use Responder;

    use RequestAwareTrait;
}
