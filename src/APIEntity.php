<?php

namespace App;

abstract class APIEntity extends Entity implements APIEntityInterface {

    public function getAPIResponse(): array {
        return $this->toArray();
    }

}
