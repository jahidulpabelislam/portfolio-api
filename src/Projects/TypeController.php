<?php

namespace App\Projects;

use App\HTTP\AbstractCRUDController;

final class TypeController extends AbstractCRUDController {

    protected string $entityClass = Type::class;

    protected array $publicActions = [
        "index",
        "read",
    ];
}
