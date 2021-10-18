<?php

namespace App\Projects;

use App\HTTP\Controller\Crud;
use App\Projects\Entity\Type;

class TypeController extends Crud {

    protected $entityClass = Type::class;

    protected $publicFunctions = [
        "index",
        "read",
    ];
}
