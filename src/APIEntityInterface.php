<?php

namespace App;

use DateTime;

interface APIEntityInterface {

    public function getAPIURL(): string;
    public function getAPIResponse(): array;

}
