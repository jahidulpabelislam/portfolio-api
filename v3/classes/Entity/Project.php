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
		'Colour',
		'Date',
	];
	
	public $searchableColumns = [
		'Name',
		'Skills',
		'LongDescription',
		'ShortDescription',
	];

	/**
	 * Load a single Entity from the Database where a ID column = a value ($id)
	 * Either return Entity with success meta data, or failed meta data
	 * Uses helper function getByColumn();
	 *
	 * As extra functionality on top of default function
	 * As Project is linked to Multiple Project Images
	 * Add these to the result unless specified
	 *
	 * @param $id int The ID of the Entity to get
	 * @param bool $images bool Whether of not to also get and output the Project Images linked to this Project
	 * @return array The result from the SQL query
	 */
	public function getById($id, $images = true) : array {
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

	/**
	 * Save values to the Entity Table in the Database
	 * Will either be a new insert or a update to an existing Entity
	 *
	 * Add extra functionality on top of default save
	 * If the save was a update, update the Order 'Number' on its Project Images
	 * The Order Number is based on to order the items are in
	 *
	 * @param $values array The values as an array to use for the Entity
	 * @return array Either an array with successful meta data or an array of error feedback meta
	 */
	public function save(array $values) : array {
		
		$values["Date"] = date("Y-m-d", strtotime($values["Date"]));
		
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

	/**
	 * Delete an Entity from the Database
	 *
	 * Add extra functionality on top of default delete function
	 * As these Entities are linked to many Project Images, so delete these also
	 *
	 * @param $id int The ID of the Entity to delete
	 * @return array Either an array with successful meta data or a array of error feedback meta
	 */
	public function delete($id) : array {
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