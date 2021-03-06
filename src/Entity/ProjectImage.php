<?php
/**
 * The Project Image Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project Image entities.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @since Class available since Release: v3.0.0
 * @copyright 2010-2020 JPI
 */

namespace App\Entity;

if (!defined("ROOT")) {
    die();
}

use App\Core;
use App\Entity;
use App\Utilities;

class ProjectImage extends Entity {

    public static $displayName = "Project Image";

    protected static $tableName = "portfolio_project_image";

    protected $columns = [
        "project_id" => null,
        "sort_order_number" => 0,
        "file" => "",
    ];

    protected static $intColumns = ["project_id", "sort_order_number"];

    protected static $orderByColumn = "sort_order_number";

    protected static $defaultLimit = null;

    /**
     * @inheritDoc
     *
     * Add extra functionality on top of default delete function
     * As these Entities are linked to a file on the server
     * Here actually delete the file from the server
     *
     * @return bool Whether or not deletion was successful
     */
    public function delete(): bool {
        $isDeleted = parent::delete();

        // Check if the deletion was ok
        if ($isDeleted && !empty($this->file)) {
            // Makes sure there is a leading slash
            $filePath = ROOT . Utilities::addLeadingSlash($this->file);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return $isDeleted;
    }

}
