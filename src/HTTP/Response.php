<?php

namespace App\HTTP;

use JPI\HTTP\Response as PackageResponse;

class Response extends PackageResponse {

    public function __construct(int $statusCode = 500, array $content = [], array $headers = []) {
        $content = json_encode($content);

        parent::__construct($statusCode, $content, $headers);
    }
}
