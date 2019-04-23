<?php
/*
 * The Project Image Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project Image entities.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 1.1.0
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v3.0.0
 * @copyright 2010-2018 JPI
*/

namespace JPI\API\Entity;

if (!defined("ROOT")) {
    die();
}

class ProjectImage extends Entity {

    protected $tableName = "portfolio_project_image";

    protected $columns = [
        "id",
        "project_id",
        "sort_order_number",
        "file",
        "created_at",
        "updated_at",
    ];

    protected $intColumns = ["id", "project_id", "sort_order_number"];

    protected $defaultOrderByColumn = "sort_order_number";

    protected $defaultOrderByDirection = "ASC";

    public $displayName = "Project Image";

    /**
     * Delete an Entity from the Database
     *
     * Add extra functionality on top of default delete function
     * As these Entities are linked to a file on the server
     * Here actually delete the file from the server
     *
     * @param $id int The Id of the Entity to delete
     * @param string $fileName string The filename of the file to delete
     * @return array Either an array with successful meta data or a array of error feedback meta
     */
    public function delete($id, string $fileName = ""): array {
        $response = parent::delete($id);

        // Check if the deletion was ok
        if ($response["meta"]["affected_rows"] > 0 && $fileName) {

            // Checks if file exists to delete the actual Image file from server
            if (file_exists(ROOT . $fileName)) {
                unlink(ROOT . $fileName);
            }
        }

        return $response;
    }

    /**
     * Return the response when a ProjectImage is not found
     *
     * @param $projectId int The Id of the Project requested
     * @param $imageId int The Id of a Project Image requested
     */
    public function getNotFoundResponse(int $projectId, int $imageId) {
        return [
            "row" => [],
            "meta" => [
                "status" => 404,
                "feedback" => "No {$this->displayName} found with {$imageId} as ID for Project: {$projectId}.",
                "message" => "Not Found",
            ],
        ];
    }
}
