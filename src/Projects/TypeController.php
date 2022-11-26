<?php

namespace App\Projects;

use App\HTTP\AbstractCrudController;
use App\Projects\Entity\Type;

class TypeController extends AbstractCrudController {

    protected string $entityClass = Type::class;

    protected array $publicActions = [
        "index",
        "read",
    ];
}
