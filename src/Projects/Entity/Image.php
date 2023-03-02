<?php

/**
 * The Project Image Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project Image entities.
 */

namespace App\Projects\Entity;

use App\Core;
use App\Entity\API\AbstractEntity as AbstractAPIEntity;
use App\Entity\Timestamped;
use JPI\Utils\URL;

class Image extends AbstractAPIEntity {

    use Timestamped;

    public static $displayName = "Project Image";

    protected static $table = "project_images";

    protected static $defaultColumns = [
        "project_id" => null,
        "position" => 0,
        "file" => "",
    ];

    protected static $intColumns = ["project_id", "position"];

    public static $defaultOrderByColumn = "position";

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
            $filePath = APP_ROOT . URL::addLeadingSlash($this->file);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return $isDeleted;
    }

    public function getAPIURL(): URL {
        return Core::get()->getRouter()->getURLForRoute(
            "projectImage",
            [
                "id" => $this->getId(),
                "projectId" => $this->project_id,
            ]
        );
    }

    public function getAPIResponse(): array {
        $response = parent::getAPIResponse();

        $response["url"] = (string)Core::get()->getRequest()->makeURL($response["file"]);
        unset($response["file"]);

        return $response;
    }
}
