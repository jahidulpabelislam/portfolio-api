<?php

namespace JPI\API\Entity;

class Project extends Entity {

	public $tableName = 'PortfolioProject';

	public $displayName = 'Project';

	public $defaultOrderingByColumn = 'Date';

	public $columns = [
		'ID',
		'Name',
		'Skills',
		'LongDescription',
		'ShortDescription',
		'Link',
		'GitHub',
		'Download',
		'Date',
		'Colour',
	];

	public function getById($id, $images = true) {
		$result = parent::getById($id);

		// Check if database provided any meta data if so no problem with executing query but no project found
		if (!empty($result["row"])) {
			if ($images) {
				$projectImage = new ProjectImage();
				$imagesArray = $projectImage->getByColumn("ProjectID", $id);
				$result["row"]["Pictures"] = $imagesArray["rows"];
			}
		}

		return $result;
	}

	public function save($values) {
		$result = parent::save($values);

		// Checks if the save was a update
		if (!empty($values["ID"])) {
			// Checks if update was ok
			if (!empty($result["row"])) {
				$pictures = json_decode($values["Pictures"]);

				if (count($pictures) > 0) {
					foreach ($pictures as $picture) {
						$imageUpdateData = ['ID' => $picture->ID, 'Number' => $picture->Number,];
						$projectImage = new ProjectImage();
						$projectImage->save($imageUpdateData);
					}

					$result = $this->getById($values["ID"]);
				}
			}
		}

		return $result;
	}
}