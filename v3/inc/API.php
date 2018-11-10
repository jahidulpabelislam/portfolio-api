<?php
/*
 * All the functions for this API
 * @author Jahidul Pabel Islam
*/

namespace JPI\API;

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

	//get a particular project defined by $projectID
	public function getProject($projectID, $projectOnly = false) {

		$query = "SELECT * FROM PortfolioProject WHERE ID = :projectID;";
		$bindings = array(':projectID' => $projectID);
		$result = $this->db->query($query, $bindings);

		//check if database provided any meta data if so no problem with executing query but no project found
		if ($result["count"] <= 0 && !isset($result["meta"])) {
			$result["meta"]["ok"] = false;
			$result["meta"]["status"] = 404;
			$result["meta"]["feedback"] = "No project found with ${projectID} as ID.";
			$result["meta"]["message"] = "Not Found";
		}
		else {
			if (!$projectOnly) {
				$picturesArray = self::getProjectPictures($projectID);
				$result["rows"][0]["Pictures"] = $picturesArray["rows"];
			}

			$result["meta"]["ok"] = true;
		}

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

	//add a project user has attempted to add
	public function addProject($data) {

		//checks if user is authored
		if (Auth::isLoggedIn()) {

			//checks if requests needed are present and not empty
			$dataNeeded = array("projectName", "skills", "longDescription", "shortDescription", "github", "date");
			if (Helper::checkData($data, $dataNeeded)) {

				$data["date"] = date("Y-m-d", strtotime($data["date"]));

				$query = "INSERT INTO PortfolioProject (Name, Skills, LongDescription, ShortDescription, Link, GitHub, Download, Date, Colour) VALUES (:projectName, :skills, :longDescription, :shortDescription, :link, :github, :download, :date, :colour);";
				$bindings = array(":projectName" => $data["projectName"], ":skills" => $data["skills"], ":longDescription" => $data["longDescription"], ":shortDescription" => $data["shortDescription"], ":link" => $data["link"], ":github" => $data["github"], ":download" => $data["download"], ":date" => $data["date"], ":colour" => $data["colour"]);
				$results = $this->db->query($query, $bindings);

				//if add was ok
				if ($results["count"] > 0) {

					$projectID = $this->db->lastInsertId();
					$results = self::getProject($projectID);

					$results["meta"]["ok"] = true;
					$results["meta"]["status"] = 201;
					$results["meta"]["message"] = "Created";

				} //else error adding project
				else {

					//check if database provided any meta data if so problem with executing query
					if (!isset($results["meta"])) {
						$results["meta"]["ok"] = false;
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

	//try to edit a project user has posted before
	public function editProject($data) {

		//checks if user is authored
		if (Auth::isLoggedIn()) {

			//checks if requests needed are present and not empty
			$dataNeeded = array("projectID", "projectName", "skills", "longDescription", "shortDescription", "github", "date");
			if (Helper::checkData($data, $dataNeeded)) {

				//Check the project trying to edit actually exists
				$project = self::getProject($data["projectID"]);
				if ($project["count"] > 0) {

					$data["date"] = date("Y-m-d", strtotime($data["date"]));

					$query = "UPDATE PortfolioProject SET Name = :projectName, Skills = :skills, LongDescription = :longDescription, Link = :link, ShortDescription = :shortDescription, GitHub = :github, Download = :download, Date = :date, Colour = :colour WHERE ID = :projectID;";
					$bindings = array(":projectID" => $data["projectID"], ":projectName" => $data["projectName"], ":skills" => $data["skills"], ":longDescription" => $data["longDescription"], ":shortDescription" => $data["shortDescription"], ":link" => $data["link"], ":github" => $data["github"], ":download" => $data["download"], ":date" => $data["date"], ":colour" => $data["colour"]);
					$results = $this->db->query($query, $bindings);

					//if update was ok
					if ($results["count"] > 0) {
						$pictures = json_decode($data["pictures"]);

						if (count($pictures) > 0) {
							foreach ($pictures as $picture) {
								$query = "UPDATE PortfolioProjectImage SET Number = :Number WHERE ID = :ID;";
								$bindings = array(":ID" => $picture->ID, ":Number" => $picture->Number);
								$this->db->query($query, $bindings);
							}
						}

						$results = self::getProject($data["projectID"]);
						$results["meta"]["ok"] = true;
					} //error updating project
					else {
						//check if database provided any meta data if so problem with executing query
						if (!isset($project["meta"])) {
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

	//Try's to delete a project user has posted before
	public function deleteProject($data) {

		//checks if user is authored
		if (Auth::isLoggedIn()) {

			//checks if requests needed are present and not empty
			$dataNeeded = array("projectID");
			if (Helper::checkData($data, $dataNeeded)) {

				//Check the project trying to edit actually exists
				$results = self::getProject($data["projectID"]);
				if ($results["count"] > 0) {

					//Delete the images linked to project
					$pictures = $results["rows"][0]["Pictures"];
					foreach ($pictures as $picture) {

						// Delete the image from the database
						$query = "DELETE FROM PortfolioProjectImage WHERE ID = :ID;";
						$bindings = array(":ID" => $picture["ID"]);
						$this->db->query($query, $bindings);


						// Checks if file exists to delete the picture from server
						$fileName = $picture["File"];
						if (file_exists($_SERVER['DOCUMENT_ROOT'] . $fileName)) {
							unlink($_SERVER['DOCUMENT_ROOT'] . $fileName);
						}
					}

					// Finally delete the actual project from database
					$query = "DELETE FROM PortfolioProject WHERE ID = :projectID;";
					$bindings = array(":projectID" => $data["projectID"]);
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
		$results = self::getProject($projectID, true);
		if ($results["count"] > 0) {

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

	public function getProjectPicture($projectID, $pictureID) {

		//Check the project trying to get pictures
		$results = self::getProject($projectID, true);
		if ($results["count"] > 0) {

			$query = "SELECT * FROM PortfolioProjectImage WHERE ProjectID = :projectID AND ID = :pictureID ORDER BY Number;";
			$bindings[":projectID"] = $projectID;
			$bindings[":pictureID"] = $pictureID;
			$results = $this->db->query($query, $bindings);

			//check if database provided any meta data if so no problem with executing query but no project pictures found
			if ($results["count"] <= 0 && !isset($result["meta"])) {
				$results["meta"]["ok"] = false;
				$results["meta"]["status"] = 404;
				$results["meta"]["feedback"] = "No project image found with ${pictureID} as ID for ${projectID} as Project ID.";
				$results["meta"]["message"] = "Not Found";
			}
			else {
				$results["meta"]["ok"] = true;
			}

		}

		return $results;
	}

	//Tries to upload a picture user has tried to add as a project image
	public function addProjectPicture($data) {

		//checks if user is authored
		if (Auth::isLoggedIn()) {

			//checks if requests needed are present and not empty
			$dataNeeded = array("projectID");
			if (Helper::checkData($data, $dataNeeded) && isset($_FILES["picture"])) {

				//Check the project trying to edit actually exists
				$project = self::getProject($data["projectID"]);
				if ($project["count"] > 0) {

					//get the file type
					$imageFileType = pathinfo(basename($_FILES["picture"]["name"]), PATHINFO_EXTENSION);

					//the directory to upload file
					$directory = "/assets/images/projects/";

					//the full path for new file
					$filename = date('YmdHis', time()) . mt_rand() . "." . $imageFileType;
					$fileLocation = $directory . $filename;
					$fullPath = $_SERVER['DOCUMENT_ROOT'] . $fileLocation;

					//check if file is a actual image
					$fileType = mime_content_type($_FILES["picture"]["tmp_name"]);
					if ((strpos($fileType, 'image/') !== false)) {

						//try to upload file
						if (move_uploaded_file($_FILES["picture"]["tmp_name"], $fullPath)) {

							//update database with location of new picture
							$query = "INSERT INTO PortfolioProjectImage (File, ProjectID, Number) VALUES (:file, :projectID, 0);";
							$bindings = array(":file" => $fileLocation, ":projectID" => $data["projectID"]);
							$results = $this->db->query($query, $bindings);

							//if update of user was ok
							if ($results["count"] > 0) {

								$query = "SELECT * FROM PortfolioProjectImage WHERE File = :file AND ProjectID = :projectID;";
								$results = $this->db->query($query, $bindings);

								$results["meta"]["ok"] = true;
								$results["meta"]["status"] = 201;
								$results["meta"]["message"] = "Created";


							} //else error updating user
							else {
								//check if database provided any meta data if so problem with executing query
								if (!isset($picture["meta"])) {
									$results["meta"]["ok"] = false;
								}
							}
						} //else problem uploading file to server
						else {
							$results["meta"]["feedback"] = "Sorry, there was an error uploading your file.";
						}
					} //else bad request as file uploaded is not a image
					else {
						$results["meta"]["status"] = 400;
						$results["meta"]["message"] = "Bad Request";
						$results["meta"]["feedback"] = "File is not an image.";
					}
				}
			} //else data was not provided
			else {
				array_push($dataNeeded, "pictureUploaded");
				$results["meta"] = Helper::dataNotProvided($dataNeeded);
			}
		}
		else {
			$results = Helper::notAuthorised();
		}

		$results["meta"]["files"] = $_FILES;

		return $results;
	}

	//Tries to delete a picture linked to a project
	public function deletePicture($data) {

		//checks if user is authored
		if (Auth::isLoggedIn()) {

			//checks if requests needed are present and not empty
			$dataNeeded = array("projectID", "id");
			if (Helper::checkData($data, $dataNeeded)) {

				//Check the project trying to edit actually exists
				$results = self::getProject($data["projectID"]);
				if ($results["count"] > 0) {

					$query = "SELECT File FROM PortfolioProjectImage WHERE ID = :id;";
					$bindings = array(":id" => $data["id"]);
					$results = $this->db->query($query, $bindings);

					if ($results["count"] > 0) {

						$fileName = $results["rows"][0]["File"];

						//update database to delete row
						$query = "DELETE FROM PortfolioProjectImage WHERE ID = :id;";
						$bindings = array(":id" => $data["id"]);
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