<?php
/*
 * The Project Image Entity object class (extends the base Entity class, where most of the ORM functionality lies).
 * Within this holds and methods where it overwrites or add extra custom functionality from the base Entity class.
 * Also holds any method only custom to Project Image entities.
 *
 * PHP version 7
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version 1
 * @link https://github.com/jahidulpabelislam/portfolio-api/
 * @since Class available since Release: v3
 * @copyright 2014-2018 JPI
*/
namespace JPI\API\Entity;

class ProjectImage extends Entity {

	public $tableName = 'PortfolioProjectImage';

	public $displayName = 'Project Image';

	protected $defaultOrderingByColumn = 'SortOrderNumber';

	protected $defaultOrderingByDirection = 'ASC';

	public $columns = [
		'ID',
		'File',
		'ProjectID',
		'SortOrderNumber'
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