<?php

namespace JPI\API\Entity;

use JPI\API\Database;

abstract class Entity {

	private $db = null;

	public $tableName = null;

	public $displayName = null;

	public $result = [];

	public function __construct($id = null) {
		$this->db = Database::get();

		if ($id && is_numeric($id)) {
			$this->result = $this->getById($id);
		}
	}

	public function getById($id) {

		$query = "SELECT * FROM $this->tableName WHERE ID = :id;";
		$bindings = array(':id' => $id);
		$result = $this->db->query($query, $bindings);

		$result["row"] = [];

		// Check if database provided any meta data if so no problem with executing query but no item found
		if ($result["count"] <= 0 && !isset($result["meta"])) {
			$result["meta"]["ok"] = false;
			$result["meta"]["status"] = 404;
			$result["meta"]["feedback"] = "No $this->displayName found with $id as ID.";
			$result["meta"]["message"] = "Not Found";
		}
		else {
			$result["row"] = $result["rows"][0];
			$result["meta"]["ok"] = true;
		}

		unset($result["rows"]);
		unset($result["count"]);

		return $result;
	}
}