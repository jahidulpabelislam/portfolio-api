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
	
	public function delete($id, $fileName = '') {

		$result = parent::delete($id);

		// Check if the deletion was ok
		if ($result["count"] > 0 && $fileName) {
			
			// Checks if file exists to delete the picture
			if (file_exists($_SERVER['DOCUMENT_ROOT'] . $fileName)) {
				unlink($_SERVER['DOCUMENT_ROOT'] . $fileName);
			}
		}

		return $result;
	}
}