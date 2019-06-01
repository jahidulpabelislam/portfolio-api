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
 * @copyright 2010-2019 JPI
*/

namespace JPI\API\Entity;

if (!defined("ROOT")) {
    die();
}

class ProjectImage extends Entity {

    public static $displayName = "Project Image";

    protected $tableName = "portfolio_project_image";

    protected $columns = [
        "id" => 0,
        "project_id" => 0,
        "sort_order_number" => 0,
        "file" => "",
        "created_at" => "",
        "updated_at" => "",
    ];

    protected $intColumns = ["id", "project_id", "sort_order_number"];

    protected $orderByColumn = "sort_order_number";

    protected $orderByDirection = "ASC";

    /**
     * Delete an Entity from the Database
     *
     * Add extra functionality on top of default delete function
     * As these Entities are linked to a file on the server
     * Here actually delete the file from the server
     *
     * @param $id int The Id of the Entity to delete
     * @param $fileName string The filename of the file to delete
     * @return array Either an array with successful meta data or a array of error feedback meta
     */
    public function delete($id, string $fileName = ""): array {
        $response = parent::delete($id);

        // Check if the deletion was ok
        if (!empty($fileName) && $response["meta"]["affected_rows"] > 0) {

            // Makes sure there is a leading slash
            $fileName = "/" . ltrim($fileName, "/");

            // Checks if file exists to delete the actual Image file from server
            if (file_exists(ROOT . $fileName)) {
                unlink(ROOT . $fileName);
            }
        }

        return $response;
    }
}
