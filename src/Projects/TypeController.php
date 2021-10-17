<?php

namespace App\Projects;

use App\HTTP\Controller\Crud;
use App\HTTP\Response;
use App\Projects\Entity\Type;

class TypeController extends Crud {

    protected $entityClass = Type::class;

    protected $publicFunctions = [
        "index",
        "read",
    ];

    /**
     * Gets all entities
     *
     * @return Response
     */
    public function index(): Response {
        $entities = $this->entityClass::getCrudService()->index($this->request);
        return $this->getItemsResponse($this->entityClass, $entities);
    }
}
