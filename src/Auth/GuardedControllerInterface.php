<?php

namespace App\Auth;

interface GuardedControllerInterface {

    public function getPublicFunctions(): array;
}
