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

	/**
	 * Entity constructor.
	 *
	 * If $id is passed, load up the Entity from Database where ID = $id
	 *
	 * @param null $id int The ID of a Entity in the Database to load
	 */
	public function __construct($id = null) {
		$this->db = Database::get();

		if ($id && is_numeric($id)) {
			$this->result = $this->getById($id);
		}
	}

	/**
	 * Load Entities from the Database where a column ($column) = a value ($value)
	 * Either return Entities with success meta data, or failed meta data
	 *
	 * @param $column string
	 * @param $value string
	 * @return array The result from the SQL query
	 */
	public function getByColumn($column, $value) : array {

		$query = "SELECT * FROM $this->tableName WHERE $column = :value ORDER BY $this->defaultOrderingByColumn $this->defaultOrderingByDirection;";
		$bindings = array(':value' => $value);
		$result = $this->db->query($query, $bindings);

		// Check everything was okay
		if ($result["count"] > 0) {
			$result["meta"]["ok"] = true;
		}
		// Check if database provided any meta data if not no problem with executing query but no item found
		else if ($result["count"] <= 0 && !isset($result["meta"])){
			$result["meta"]["ok"] = false;
			$result["meta"]["status"] = 404;
			$result["meta"]["feedback"] = "No {$this->displayName}s found with $value as $column.";
			$result["meta"]["message"] = "Not Found";
		}

		return $result;
	}

	/**
	 * Load a single Entity from the Database where a ID column = a value ($id)
	 * Either return Entity with success meta data, or failed meta data
	 * Uses helper function getByColumn();
	 *
	 * @param $id int The ID of the Entity to get
	 * @return array The result from the SQL query
	 */
	public function getById($id) : array {

		$result = $this->getByColumn('ID', $id);

		$result["row"] = [];

		// Check everything was okay, so as this /Should/ return only one, use 'Row' as index
		if ($result["count"] > 0) {
			$result["row"] = $result["rows"][0];
		}
		// Check if database provided any meta data if so no problem with executing query but no item found
		else if (isset($result["meta"]) && isset($result["meta"]["status"]) && $result["meta"]["status"] === 404) {
			$result["meta"]["feedback"] = "No $this->displayName found with $id as ID.";
		}

		unset($result["rows"]);
		unset($result["count"]);

		return $result;
	}

	/**
	 * Save values to the Entity Table in the Database
	 * Will either be a new insert or a update to an existing Entity
	 *
	 * @param $values array The values as an array to use for the Entity
	 * @return array Either an array with successful meta data or an array of error feedback meta
	 */
	public function save(array $values) : array {

		$id = $values["ID"] ?? null;

		if (empty($id)) {
			list($query, $bindings) = $this->generateInsertQuery($values);
		}
		else {
			// Check the Entity trying to edit actually exists
			$result = $this->getById($id);
			if (empty($result["row"])) {
				return $result;
			}
			list($query, $bindings) = $this->generateUpdateQuery($values);
		}

		$result = $this->db->query($query, $bindings);

		// Checks if insert was ok
		if ($result["count"] > 0) {

			$id = (empty($id)) ? $this->db->getLastInsertedId() : $id;

			$result = $this->getById($id);

			if (empty($values["ID"])) {
				$result["meta"]["status"] = 201;
				$result["meta"]["message"] = "Created";
			}

		} // Else error inserting
		else {
			// Checks if database provided any meta data if so problem with executing query
			if (!isset($result["meta"])) {
				$result["meta"]["ok"] = false;
			}
		}

		return $result;
	}

	/**
	 * Helper function to generate a INSERT SQL query using the Entity's columns and provided data
	 *
	 * @param $values array The values as an array to use for the new Entity
	 * @return array [string, array] Return the raw SQL query and an array of bindings to use with query
	 */
	private function generateInsertQuery(array $values) : array {
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
	
	/**
	 * Helper function to generate a UPDATE SQL query using the Entity's columns and provided data
	 *
	 * @param $values array The new values as an array to use for the Entity's update
	 * @return array [string, array] Return the raw SQL query and an array of bindings to use with query
	 */
	private function generateUpdateQuery(array $values) : array {
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
	
	/**
	 * Delete an Entity from the Database
	 *
	 * @param $id int The ID of the Entity to delete
	 * @return array Either an array with successful meta data or a array of error feedback meta
	 */
	public function delete($id) : array {

		// Check the Entity trying to delete actually exists
		$result = $this->getById($id);
		if (!empty($result["row"])) {

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
		}

		return $result;
	}
}