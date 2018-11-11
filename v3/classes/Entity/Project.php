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
				$result["row"]["Images"] = $imagesArray["rows"];
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
				$images = json_decode($values["Images"]);

				if (count($images) > 0) {
					foreach ($images as $image) {
						$imageUpdateData = ['ID' => $image->ID, 'Number' => $image->Number,];
						$projectImage = new ProjectImage();
						$projectImage->save($imageUpdateData);
					}

					$result = $this->getById($values["ID"]);
				}
			}
		}

		return $result;
	}
	
	public function delete($id) {
		$result = parent::delete($id);

		// Delete the images linked to the Project
		$projectImage = new ProjectImage();
		$imagesResult = $projectImage->getByColumn('ProjectID', $id);
		$images = $imagesResult["rows"];
		foreach ($images as $image) {

			// Delete the image from the database & from file
			$projectImage->delete($image["ID"], $image["File"]);
		}
		
		return $result;
	}
}