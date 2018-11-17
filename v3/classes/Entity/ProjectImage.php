<?php

namespace JPI\API\Entity;

class ProjectImage extends Entity {

	public $tableName = 'PortfolioProjectImage';

	public $displayName = 'Project Image';

	public $defaultOrderingByColumn = 'NUMBER';

	public $defaultOrderingByDirection = 'ASC';

	public $columns = [
		'ID',
		'File',
		'ProjectID',
		'Number'
	];
	
	/**
	 * Delete an Entity from the Database
	 *
	 * Add extra functionality on top of default delete function
	 * As these Entities are linked to a file on the server
	 * Here actually delete the file from the server
	 *
	 * @param $id int The ID of the Entity to delete
	 * @param string $fileName string The filename of the file to delete
	 * @return array Either an array with successful meta data or a array of error feedback meta
	 */
	public function delete($id, $fileName = '') : array {

		$result = parent::delete($id);

		// Check if the deletion was ok
		if ($result["count"] > 0 && $fileName) {
			
			// Checks if file exists to delete the actual Image file from server
			if (file_exists($_SERVER['DOCUMENT_ROOT'] . $fileName)) {
				unlink($_SERVER['DOCUMENT_ROOT'] . $fileName);
			}
		}

		return $result;
	}
	
	/**
	 * Check if a ProjectImage is a child of a Project.
	 * Use in conjunction with ProjectImage::getById()
	 *
	 * @param $projectId int The id of a Project it should check against
	 * @param $imageId int
	 */
	public function checkProjectImageIsChildOfProject($projectId) {
		
		$result = $this->result;
		
		if (!empty($result['row']) && $result['row']['ProjectID'] !== $projectId) {
			$imageId = $result['row']['ID'];
			$result = [
				'row' => [],
				'meta' => [
					'ok' => false,
					'status' => 404,
					'feedback' => "No $this->displayName found with $imageId as ID for Project: $projectId.",
					'message' => 'Not Found',
				],
			];
			
			$this->result = $result;
		}
	}
}