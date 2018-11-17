<?php
/*
 * All the functions for this API
 * @author Jahidul Pabel Islam
*/

namespace JPI\API;

use JPI\API\Entity\Project;
use JPI\API\Entity\ProjectImage;

class API {

	private $db = null;
	
	/**
	 * API constructor.
	 */
	public function __construct() {
		$this->db = Database::get();
	}
	
	/**
	 * Check whether the user is logged or no
	 *
	 * @return array The request response to send back
	 */
	public function getAuthStatus() {

		$result = [];

		if (Auth::isLoggedIn()) {
			$result["meta"]["ok"] = true;
			$result["meta"]["status"] = 200;
			$result["meta"]["message"] = "OK";
		}
		else {
			$result = Helper::notAuthorised();
		}

		return $result;
	}

	/**
	 * Get a particular Project defined by $projectID
	 *
	 * @param $projectID int The id of the Project to get
	 * @param bool $images bool Whether the images for the Project should should be added
	 * @return array The request response to send back
	 */
	public function getProject($projectID, $images = false) {

		$project = new Project();
		$result = $project->getById($projectID, $images);

		return $result;
	}

	/**
	 * Gets all projects but paginated, also might include search
	 *
	 * @param $data array Any data to aid in the search query
	 * @return array The request response to send back
	 */
	public function getProjects($data) {

		// If user added a limit param, use this if valid, unless its bigger than 10
		if (isset($data["limit"])) {
			$limit = min(abs(intval($data["limit"])), 10);
		}

		// Default limit to 10 if not specified or invalid
		if (!isset($limit) || !is_int($limit) || $limit < 1) {
			$limit = 10;
		}

		$offset = 0;

		// Add a offset to the query, if specified
		if (isset($data["offset"])) {
			$offset = abs(intval($data["offset"]));
		}

		// Generate a offset to the query, if a page was specified using, page number and limit number
		if (isset($data["page"])) {
			$page = intval($data["page"]);
			if (is_int($page) && $page > 1) {
				$offset = $limit * ($data["page"] - 1);
			}
		}

		$filter = "";
		
		// Add a filter if a search was entered
		if (isset($data["search"])) {
			// Split each word in search
			$searches = explode(" ", $data["search"]);

			$search = "%";

			// Loop through each search word
			foreach ($searches as $aSearch) {
				$search .= "${aSearch}%";
			}

			$searchesReversed = array_reverse($searches);

			$search2 = "%";

			// Loop through each search word
			foreach ($searchesReversed as $aSearch) {
				$search2 .= "${aSearch}%";
			}

			$filter = "WHERE Name LIKE '" . $search . "' OR Name LIKE '" . $search2 . "' OR LongDescription LIKE '" . $search . "' OR LongDescription LIKE '" . $search2 . "' OR ShortDescription LIKE '" . $search . "' OR ShortDescription LIKE '" . $search2 . "' OR Skills LIKE '" . $search . "' OR Skills LIKE '" . $search2 . "'";
		}

		$query = "SELECT * FROM PortfolioProject $filter ORDER BY Date DESC LIMIT $limit OFFSET $offset;";
		$result = $this->db->query($query);

		// Check if database provided any meta data if not all ok
		if (count($result["rows"]) > 0 && !isset($result["meta"])) {

			$query = "SELECT COUNT(*) AS Count FROM PortfolioProject $filter;";
			$count = $this->db->query($query);
			
			if ($count && count($count["rows"]) > 0) {
				$result["count"] = $count["rows"][0]["Count"];
			}

			// Loop through each project and get the Projects Images
			for ($i = 0; $i < count($result["rows"]); $i++) {

				// Run the function provided as data exists and is valid
				$imagesArray = self::getProjectImages($result["rows"][$i]["ID"]);
				$result["rows"][$i]["Images"] = $imagesArray["rows"] ?? [];
			}

			$result["meta"]["ok"] = true;
		}

		return $result;
	}

	/**
	 * Try and add a Project a user has attempted to add
	 *
	 * @param $data array The data to insert into the database for this new Project
	 * @return array The request response to send back
	 */
	public function addProject($data) {

		$result = [];

		// Checks if user is authored
		if (Auth::isLoggedIn()) {

			// Checks if data needed is present and not empty
			$dataNeeded = ["Name", "Skills", "LongDescription", "ShortDescription", "GitHub", "Date",];
			if (Helper::checkData($data, $dataNeeded)) {

				$project = new Project();
				$result = $project->save($data);

			} // Else the data needed was not provided
			else {
				$result["meta"] = Helper::dataNotProvided($dataNeeded);
			}
		}
		else {
			$result = Helper::notAuthorised();
		}

		return $result;
	}

	/**
	 * Try to edit a Project a user has posted before
	 *
	 * @param $data array The new data entered to use to update the project with
	 * @return array The request response to send back
	 */
	public function editProject($data) {

		$result = [];

		// Checks if user is authored
		if (Auth::isLoggedIn()) {

			// Checks if data needed is present and not empty
			$dataNeeded = ["ID", "Name", "Skills", "LongDescription", "ShortDescription", "GitHub", "Date",];
			if (Helper::checkData($data, $dataNeeded)) {

				$project = new Project();
				$result = $project->save($data);

			} // Else the data was not provided
			else {
				$result["meta"] = Helper::dataNotProvided($dataNeeded);
			}
		}
		else {
			$result = Helper::notAuthorised();
		}

		return $result;
	}

	/**
	 * Try to delete a Project a user has posted before
	 *
	 * @param $data array The data sent to aid in the deletion of the Project
	 * @return array The request response to send back
	 */
	public function deleteProject($data) {

		$result = [];

		// Checks if user is authored
		if (Auth::isLoggedIn()) {

			// Checks if the data needed is present and not empty
			$dataNeeded = ["ID",];
			if (Helper::checkData($data, $dataNeeded)) {

				$project = new Project();
				$result = $project->delete($data["ID"]);

			} // Else the data needed was not provided
			else {
				$result["meta"] = Helper::dataNotProvided($dataNeeded);
			}
		}
		else {
			$result = Helper::notAuthorised();
		}

		return $result;
	}
	
	/**
	 * Get the Images attached to a Project
	 *
	 * @param $projectID int The Id of the Project
	 * @return array The request response to send back
	 */
	public function getProjectImages($projectID) {

		// Check the project trying to get Images for
		$result = self::getProject($projectID);
		if (!empty($result["row"])) {

			$projectImage = new ProjectImage();
			$result = $projectImage->getByColumn('ProjectID', $projectID);
		}

		return $result;
	}
	
	/**
	 * Get a Project Image for a Project by id
	 *
	 * @param $projectId int The id of the Project trying to get Images for
	 * @param $imageId int The id of the Project Image to get
	 * @return array The request response to send back
	 */
	public function getProjectImage($projectId, $imageId) {

		// Check the Project trying to get Images for
		$result = self::getProject($projectId);
		if (!empty($result["row"])) {
			$projectImage = new ProjectImage($imageId);
			$result = $projectImage->result;
		}

		return $result;
	}

	/**
	 * Try to upload a Image user has tried to add as a Project Image
	 *
	 * @param $data array The data sent to aid in Inserting Project Image
	 * @return array The request response to send back
	 */
	public function addProjectImage($data) {

		$result = [];

		// Checks if user is authored
		if (Auth::isLoggedIn()) {

			// Checks if the data needed is present and not empty
			$dataNeeded = ["ProjectID",];
			if (Helper::checkData($data, $dataNeeded) && isset($_FILES["image"])) {

				// Check the project trying to add a a Image for exists
				$result = self::getProject($data["ProjectID"]);
				if (!empty($result["row"])) {

					// Get the file type
					$imageFileType = pathinfo(basename($_FILES["image"]["name"]), PATHINFO_EXTENSION);

					// The directory to upload file
					$directory = "/assets/images/projects/";

					// The full path for new file on the server
					$filename = date('YmdHis', time()) . mt_rand() . "." . $imageFileType;
					$fileLocation = $directory . $filename;
					$fullPath = $_SERVER['DOCUMENT_ROOT'] . $fileLocation;

					// Check if file is a actual image
					$fileType = mime_content_type($_FILES["image"]["tmp_name"]);
					if ((strpos($fileType, 'image/') !== false)) {

						// Try to uploaded file
						if (move_uploaded_file($_FILES["image"]["tmp_name"], $fullPath)) {

							// Update database with location of new Image
							$values = [
								"File" => $fileLocation,
								"ProjectID" => $data["ProjectID"],
								"Number" => 999, // High enough number
							];
							$projectImage = new ProjectImage();
							$result = $projectImage->save($values);
						} // Else there was a problem uploading file to server
						else {
							$result["meta"]["feedback"] = "Sorry, there was an error uploading your Image.";
						}
					} // Else bad request as file uploaded is not a image
					else {
						$result["meta"]["status"] = 400;
						$result["meta"]["message"] = "Bad Request";
						$result["meta"]["feedback"] = "File is not an image.";
					}
				}
			} // Else data needed was not provided
			else {
				array_push($dataNeeded, "Image");
				$result["meta"] = Helper::dataNotProvided($dataNeeded);
			}
		}
		else {
			$result = Helper::notAuthorised();
		}

		$result["meta"]["files"] = $_FILES;

		return $result;
	}

	/**
	 * Try to delete a Image linked to a project
	 *
	 * @param $data array The data sent to delete the Project Image
	 * @return array The request response to send back
	 */
	public function deleteImage($data) {

		$result = [];

		// Checks if user is authored
		if (Auth::isLoggedIn()) {

			// Checks if data needed is present and not empty
			$dataNeeded = ["ProjectID", "ID",];
			if (Helper::checkData($data, $dataNeeded)) {

				// Check the Project trying to edit actually exists
				$result = self::getProject($data["ProjectID"]);
				if (!empty($result["row"])) {

					$result = $this->getProjectImage($data["ProjectID"], $data["ID"]);

					if (!empty($result["row"])) {

						$fileName = $result["row"]["File"];

						// Update database to delete row
						$projectImage = new ProjectImage();
						$result = $projectImage->delete($data["ID"], $fileName);
					}
				}
			} // Else data was not provided
			else {
				$result["meta"] = Helper::dataNotProvided($dataNeeded);
			}
		}
		else {
			$result = Helper::notAuthorised();
		}

		return $result;
	}
}