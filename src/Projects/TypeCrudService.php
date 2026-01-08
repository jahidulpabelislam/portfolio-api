<?php

declare(strict_types=1);

namespace App\Projects;

use JPI\CRUD\API\CrudService as BaseService;

final class TypeCrudService extends BaseService {

    protected ?int $perPage = null;
}
