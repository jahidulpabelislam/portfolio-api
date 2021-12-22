<?php

namespace App;

interface APIEntityInterface {

    public function getAPIURL(): string;

    public function getAPILinks(): array;

    public function getAPIResponse(): array;
}
