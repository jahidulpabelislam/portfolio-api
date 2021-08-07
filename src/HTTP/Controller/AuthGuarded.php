<?php

namespace App\HTTP\Controller;

interface AuthGuarded {
    public function getPublicFunctions(): array;
}
