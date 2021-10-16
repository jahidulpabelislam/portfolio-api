<?php

namespace App;

use App\Entity\CrudService;

interface APIEntityInterface {

    public static function getCrudService(): CrudService;
    public function getAPIURL(): string;
    public function getAPIResponse(): array;
    public function getAPILinks(): array;
}
