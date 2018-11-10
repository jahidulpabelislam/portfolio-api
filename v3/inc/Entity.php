<?php

namespace JPI\API\Entity;

use JPI\API\Database;

abstract class Entity {

	private $db = null;

	public $tableName = null;

	public $displayName = null;

	public $defaultOrderingByColumn = 'ID';

	public $defaultOrderingByDirection = 'DESC';

	public $columns = [];

	public $result = [];

	public function __construct($id = null) {
		$this->db = Database::get();

		if ($id && is_numeric($id)) {
			$this->result = $this->getById($id);
		}
	}
	
	public function getByColumn($column, $value) {

		$query = "SELECT * FROM $this->tableName WHERE $column = :value ORDER BY $this->defaultOrderingByColumn $this->defaultOrderingByDirection;";
		$bindings = array(':value' => $value);
		$result = $this->db->query($query, $bindings);

		// Check if database provided any meta data if so no problem with executing query but no item found
		if ($result["count"] <= 0 && !isset($result["meta"])) {
			$result["meta"]["ok"] = false;
			$result["meta"]["status"] = 404;
			$result["meta"]["feedback"] = "No {$this->displayName}s found with $value as $column.";
			$result["meta"]["message"] = "Not Found";
		}
		else {
			$result["meta"]["ok"] = true;
		}

		return $result;
	}

	public function getById($id) {

		$result = $this->getByColumn('ID', $id);

		$result["row"] = [];
		
		// Check if database provided any meta data if so no problem with executing query but no item found
		if ($result["count"] <= 0 && !isset($result["meta"])) {
			$result["meta"]["feedback"] = "No $this->displayName found with $id as ID.";
		}
		// Else everything was okay, so as this /Should/ return only one, use 'Row' as index
		else {
			$result["row"] = $result["rows"][0];
		}

		unset($result["rows"]);
		unset($result["count"]);

		return $result;
	}
	
	public function save($values) {

		if (empty($values["ID"])) {
			list($query, $bindings) = $this->generateInsertQuery($values);
		}
		else {
			list($query, $bindings) = $this->generateUpdateQuery($values);
		}

		$result = $this->db->query($query, $bindings);

		// Checks if insert was ok
		if ($result["count"] > 0) {

			$id = (empty($values["ID"])) ? $this->db->lastInsertId() : $values["ID"];

			$result = $this->getById($id);

			if (empty($values["ID"])) {
				$result["meta"]["status"] = 201;
				$result["meta"]["message"] = "Created";
			}

		} // Else error inserting
		else {
			// Checks if database provided any meta data if so problem with executing query
			if (!isset($picture["meta"])) {
				$result["meta"]["ok"] = false;
			}
		}

		return $result;
	}
	
	private function generateInsertQuery($values) {
		$columnsQuery = '(';
		$valuesQuery = '(';
		$bindings = [];

		foreach ($this->columns as $column) {
			if ($column !== 'ID' && !empty($values[$column])) {
				$columnsQuery .= $column . ', ';
				$valuesQuery .= ':' .$column . ', ';
				$bindings[":$column"] = $values[$column];
			}
		}
		$columnsQuery = rtrim($columnsQuery, ', ');
		$columnsQuery .= ')';

		$valuesQuery = rtrim($valuesQuery, ', ');
		$valuesQuery .= ')';

		$query = "INSERT INTO $this->tableName $columnsQuery VALUES $valuesQuery;";

		return [$query, $bindings];
	}
	
	private function generateUpdateQuery($values) {
		$valuesQuery = 'SET ';
		$bindings = [];

		foreach ($this->columns as $column) {
			if (isset($values[$column])) {
				$bindings[":$column"] = $values[$column];

				if ($column !== 'ID') {
					$valuesQuery .= $column . ' = :' .$column . ', ';
				}
			}
		}

		$valuesQuery = rtrim($valuesQuery, ', ');

		$query = "UPDATE $this->tableName $valuesQuery WHERE ID = :ID;";

		return [$query, $bindings];
	}
	
	public function delete($id) {
		
		$query = "DELETE FROM $this->tableName WHERE ID = :ID;";
		$bindings = [":ID" => $id,];
		$result = $this->db->query($query, $bindings);
		
		// Check if the deletion was ok
		if ($result["count"] > 0) {
			
			$result["meta"]["ok"] = true;
			$result["row"]["ID"] = $id;
			
		} //Else there was an error deleting
		else {
			// Check if database provided any meta data if so problem with executing query
			if (!isset($result["meta"])) {
				$result["meta"]["ok"] = false;
			}
		}
		
		return $result;
	}
}