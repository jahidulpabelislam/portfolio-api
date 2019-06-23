<?php
/**
 * The Project Image Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project Image entities.
 *
 * PHP version 7.1+
 *
 * @version 2.0.0
 * @since Class available since Release: v3.0.0
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2010-2019 JPI
 */

namespace JPI\API\Entity;

if (!defined("ROOT")) {
    die();
}

class ProjectImage extends Entity {

    public static $displayName = "Project Image";

    protected static $tableName = "portfolio_project_image";

    protected $columns = [
        "id" => null,
        "project_id" => 0,
        "sort_order_number" => 0,
        "file" => "",
        "created_at" => "",
        "updated_at" => "",
    ];

    protected static $intColumns = ["id", "project_id", "sort_order_number"];

    protected static $orderByColumn = "sort_order_number";

    protected static $orderByDirection = "ASC";

    /**
     * Delete an Entity from the Database
     *
     * Add extra functionality on top of default delete function
     * As these Entities are linked to a file on the server
     * Here actually delete the file from the server
     *
     * @param $id int The Id of the Entity to delete
     * @return bool Whether or not deletion was successful
     */
    public function delete($id): bool {
        $isDeleted = parent::delete($id);

        // Check if the deletion was ok
        if ($isDeleted && !empty($this->file)) {
            $fileName = $this->file;

            // Makes sure there is a leading slash
            $filePath = ROOT . "/" . ltrim($fileName, "/");

            // Checks if file exists to delete the actual Image file from server
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return $isDeleted;
    }
}
