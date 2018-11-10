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

	public function __construct() {
		$this->db = Database::get();
	}

	public function getAuthStatus() {

		if (Auth::isLoggedIn()) {
			$results["meta"]["ok"] = true;
			$results["meta"]["status"] = 200;
			$results["meta"]["message"] = "OK";
		}
		else {
			$results = Helper::notAuthorised();
		}

		return $results;
	}

	/**
	 * Get a particular Project defined by $projectID
	 *
	 * @param $projectID int The id of the Project to get
	 * @param bool $images Whether the images for the roject should should be added
	 * @return array
	 */
	public function getProject($projectID, $images = false) {

		$project = new Project();

		$result = $project->getById($projectID, $images);

		return $result;
	}

	//gets all projects but limited
	public function getProjects($data) {

		if (isset($data["limit"])) {
			$limit = min(abs(intval($data["limit"])), 10);
		}

		if (!isset($limit) || !is_int($limit) || $limit < 1) {
			$limit = 10;
		}

		$offset = 0;
		if (isset($data["offset"])) {
			$offset = min(abs(intval($data["offset"])), 10);
		}

		if (isset($data["page"])) {
			$page = intval($data["page"]);
			if (is_int($page) && $page > 1) {
				$offset = $limit * ($data["page"] - 1);
			}
		}

		$filter = "";
		if (isset($data["search"])) {
			//split each word in search
			$searches = explode(" ", $data["search"]);

			$search = "%";

			//loop through each search word
			foreach ($searches as $aSearch) {
				$search .= "${aSearch}%";
			}

			$searchesReversed = array_reverse($searches);

			$search2 = "%";

			//loop through each search word
			foreach ($searchesReversed as $aSearch) {
				$search2 .= "${aSearch}%";
			}

			$filter = "WHERE Name LIKE '" . $search . "' OR Name LIKE '" . $search2 . "' OR LongDescription LIKE '" . $search . "' OR LongDescription LIKE '" . $search2 . "' OR ShortDescription LIKE '" . $search . "' OR ShortDescription LIKE '" . $search2 . "' OR Skills LIKE '" . $search . "' OR Skills LIKE '" . $search2 . "'";
		}

		$query = "SELECT * FROM PortfolioProject $filter ORDER BY Date DESC LIMIT $limit OFFSET $offset;";
		$results = $this->db->query($query);

		//check if database provided any meta data if not all ok
		if (!isset($results["meta"])) {

			$query = "SELECT COUNT(*) AS Count FROM PortfolioProject $filter;";
			$count = $this->db->query($query);
			$results["count"] = $count["rows"][0]["Count"];

			//loop through each project and the projects images
			for ($i = 0; $i < count($results["rows"]); $i++) {

				//run the function provided as data exists and is valid
				$picturesArray = self::getProjectPictures($results["rows"][$i]["ID"]);
				$results["rows"][$i]["Pictures"] = $picturesArray["rows"];
			}

			$results["meta"]["ok"] = true;
		}

		return $results;
	}

	/**
	 * Try and add a Project a user has attempted to add
	 *
	 * @param $data array
	 * @return array
	 */
	public function addProject($data) {

		// Checks if user is authored
		if (Auth::isLoggedIn()) {

			// Checks if data needed is present and not empty
			$dataNeeded = ["Name", "Skills", "LongDescription", "ShortDescription", "GitHub", "Date",];
			if (Helper::checkData($data, $dataNeeded)) {

				$data["Date"] = date("Y-m-d", strtotime($data["Date"]));

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
	 * @return array
	 */
	public function editProject($data) {

		// Checks if user is authored
		if (Auth::isLoggedIn()) {

			// Checks if data needed is present and not empty
			$dataNeeded = ["ID", "Name", "Skills", "LongDescription", "ShortDescription", "GitHub", "Date",];
			if (Helper::checkData($data, $dataNeeded)) {

				// Check the Project trying to edit actually exists
				$result = self::getProject($data["ID"]);
				if (!empty($result["row"])) {

					$data["Date"] = date("Y-m-d", strtotime($data["Date"]));

					$project = new Project();
					$result = $project->save($data);
				}
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

	//Try's to delete a project user has posted before
	public function deleteProject($data) {

		//checks if user is authored
		if (Auth::isLoggedIn()) {

			//checks if requests needed are present and not empty
			$dataNeeded = ["projectID",];
			if (Helper::checkData($data, $dataNeeded)) {

				//Check the project trying to edit actually exists
				$results = self::getProject($data["projectID"]);
				if ($results["count"] > 0) {

					//Delete the images linked to project
					$pictures = $results["rows"][0]["Pictures"];
					foreach ($pictures as $picture) {

						// Delete the image from the database
						$query = "DELETE FROM PortfolioProjectImage WHERE ID = :ID;";
						$bindings = [":ID" => $picture["ID"],];
						$this->db->query($query, $bindings);


						// Checks if file exists to delete the picture from server
						$fileName = $picture["File"];
						if (file_exists($_SERVER['DOCUMENT_ROOT'] . $fileName)) {
							unlink($_SERVER['DOCUMENT_ROOT'] . $fileName);
						}
					}

					// Finally delete the actual project from database
					$query = "DELETE FROM PortfolioProject WHERE ID = :projectID;";
					$bindings = [":projectID" => $data["projectID"],];
					$results = $this->db->query($query, $bindings);

					//if deletion was ok
					if ($results["count"] > 0) {
						$results["meta"]["ok"] = true;

						$results["rows"]["projectID"] = $data["projectID"];
					} //error deleting project
					else {
						//check if database provided any meta data if so problem with executing query
						if (!isset($results["meta"])) {
							$results["meta"]["ok"] = false;
						}
					}
				}
			} //else data was not provided
			else {
				$results["meta"] = Helper::dataNotProvided($dataNeeded);
			}
		}
		else {
			$results = Helper::notAuthorised();
		}

		return $results;
	}

	public function getProjectPictures($projectID) {

		//Check the project trying to get pictures
		$results = self::getProject($projectID);
		if (!empty($results["row"])) {

			$query = "SELECT * FROM PortfolioProjectImage WHERE ProjectID = :projectID ORDER BY Number;";
			$bindings[":projectID"] = $projectID;
			$results = $this->db->query($query, $bindings);

			//check if database provided any meta data if so no problem with executing query but no project pictures found
			if ($results["count"] <= 0 && !isset($result["meta"])) {
				$results["meta"]["ok"] = false;
				$results["meta"]["status"] = 404;
				$results["meta"]["feedback"] = "No project images found for ${projectID}.";
				$results["meta"]["message"] = "Not Found";
			}
			else {
				$results["meta"]["ok"] = true;
			}

		}

		return $results;
	}
	
	/**
	 * Get a Project Image for a Project by id
	 *
	 * @param $projectID int The id of the Project
	 * @param $pictureID int The id of the Project Image to get
	 * @return array
	 */
	public function getProjectPicture($projectID, $pictureID) {

		// Check the project trying to get pictures
		$result = self::getProject($projectID);
		if (!empty($result["row"])) {
			$projectImage = new ProjectImage($pictureID);
			$result = $projectImage->result;
		}

		return $result;
	}

	/**
	 * Try to upload a picture user has tried to add as a project image
	 *
	 * @param $data array The data sent to aid in Inserting Project Image
	 * @return array
	 */
	public function addProjectPicture($data) {

		// Checks if user is authored
		if (Auth::isLoggedIn()) {

			// Checks if requests needed are present and not empty
			$dataNeeded = ["ProjectID",];
			if (Helper::checkData($data, $dataNeeded) && isset($_FILES["picture"])) {

				// Check the project trying to add a a Image for exists
				$result = self::getProject($data["ProjectID"]);
				if (!empty($result["row"])) {

					// Get the file type
					$imageFileType = pathinfo(basename($_FILES["picture"]["name"]), PATHINFO_EXTENSION);

					// The directory to upload file
					$directory = "/assets/images/projects/";

					// The full path for new file on the server
					$filename = date('YmdHis', time()) . mt_rand() . "." . $imageFileType;
					$fileLocation = $directory . $filename;
					$fullPath = $_SERVER['DOCUMENT_ROOT'] . $fileLocation;

					// Check if file is a actual image
					$fileType = mime_content_type($_FILES["picture"]["tmp_name"]);
					if ((strpos($fileType, 'image/') !== false)) {

						// Try to uploaded file
						if (move_uploaded_file($_FILES["picture"]["tmp_name"], $fullPath)) {

							// Update database with location of new picture
							$values = [
								"File" => $fileLocation,
								"ProjectID" => $data["ProjectID"],
								"Number" => 999, // High enough number
							];
							$projectImage = new ProjectImage();
							$result = $projectImage->save($values);
						} // Else there was a problem uploading file to server
						else {
							$result["meta"]["feedback"] = "Sorry, there was an error uploading your file.";
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
				array_push($dataNeeded, "Picture");
				$result["meta"] = Helper::dataNotProvided($dataNeeded);
			}
		}
		else {
			$result = Helper::notAuthorised();
		}

		$result["meta"]["files"] = $_FILES;

		return $result;
	}

	//Tries to delete a picture linked to a project
	public function deletePicture($data) {

		//checks if user is authored
		if (Auth::isLoggedIn()) {

			//checks if requests needed are present and not empty
			$dataNeeded = ["projectID", "id",];
			if (Helper::checkData($data, $dataNeeded)) {

				//Check the project trying to edit actually exists
				$results = self::getProject($data["projectID"]);
				if (!empty($results["row"])) {

					$query = "SELECT File FROM PortfolioProjectImage WHERE ID = :id;";
					$bindings = [":id" => $data["id"],];
					$results = $this->db->query($query, $bindings);

					if ($results["count"] > 0) {

						$fileName = $results["rows"][0]["File"];

						//update database to delete row
						$query = "DELETE FROM PortfolioProjectImage WHERE ID = :id;";
						$bindings = [":id" => $data["id"],];
						$results = $this->db->query($query, $bindings);

						//if deletion was ok
						if ($results["count"] > 0) {

							//checks if file exists to delete the picture
							if (file_exists($_SERVER['DOCUMENT_ROOT'] . $fileName)) {
								unlink($_SERVER['DOCUMENT_ROOT'] . $fileName);
							}

							$results["meta"]["ok"] = true;
							$results["rows"]["id"] = $data["id"];

						} //else error updating
						else {
							//check if database provided any meta data if so problem with executing query
							if (!isset($results["meta"])) {
								$results["meta"]["ok"] = false;
							}
						}

					}
				}
			} //else data was not provided
			else {
				$results["meta"] = Helper::dataNotProvided($dataNeeded);
			}
		}
		else {
			$results = Helper::notAuthorised();
		}

		return $results;
	}
}