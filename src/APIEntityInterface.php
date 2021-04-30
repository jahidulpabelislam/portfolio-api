<?php

namespace App;

interface APIEntityInterface {

    public function getAPIURL(): string;
    public function getAPIResponse(): array;
    public function getAPILinks(): array;

}