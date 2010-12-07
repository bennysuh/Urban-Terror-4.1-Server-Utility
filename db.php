<?php

/****************************************

	db.php

	Class DB

*/

class DB {

	// Properties
	public $stats_link;
	public $auth_link;
	public $results;
	public $prefix;
	public $sql;
	public $table;
	public $query_type;
	public $database;
	public $fields;
	public $values;
	public $criteria_fields;
	public $criteria_values;
	public $criteria_type;
	public $criteria_connector;

	// Methods
	public function stats_query() {
		if ($this->query_type == "SELECT") {
			$f = 0;
			$fields = "";
			$fcount = count($this->fields,0);
			while ($f < $fcount) {
				if ($f == ($fcount - 1)) {
					$fields .= $this->fields[$f];
				} else {
					$fields .= "{$this->fields[$f]}, ";
				}
				$f++;
			}
			$c = 0;
			$criteria_fields = "";
			$ccount = count($this->criteria_fields, 0);
			while ($c < $ccount) {
				if ($c == ($ccount - 1)) {
					$criteria_fields .= $this->criteria_fields[$c] . $this->crtieria_type[$c] . $this->criteria_values[$c];
				} else {
					$criteria_fields .= $this->criteria_fields[$c] . $this->crtieria_type[$c] . $this->criteria_values[$c] . " " . $this->criteria_connector[$c] . " ";
				}
				$c++;
			}
			if ($criteria_fields == "") {
				$criteria_fields = "TRUE";
			}
			$this->sql = "SELECT {$fields} FROM {$this->table} WHERE {$criteria_fields}";
			$this->results = mysql_query($this->sql, $this->stats_link);
		}

		if ($this->query_type == "INSERT") {
			$f = 0;
			$fields = "";
			$fcount = count($this->fields,0);
			while ($f < $fcount) {
				if ($f == ($fcount - 1)) {
					$fields .= $this->fields[$f];
				} else {
					$fields .= "{$this->fields[$f]}, ";
				}
				$f++;
			}
			$v = 0;
			$values = "";
			$vcount = count($this->values,0);
			while ($v < $vcount) {
				if ($v == ($vcount - 1)) {
					$values .= $this->values[$v];
				} else {
					$values .= "{$this->values[$v]}, ";
				}
				$v++;
			}
			$this->sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$values})";
			mysql_query($this->sql, $this->stats_link);
		}

		if ($this->query_type == "UPDATE") {
			$f = 0;
			$fields = "";
			$fcount = count($this->fields,0);
			while ($f < $fcount) {
				if ($f == ($fcount - 1)) {
					$fields .= "{$this->fields[$f]}={$this->values[$f]}";
				} else {
					$fields .= "{$this->fields[$f]}={$this->values[$f]}, ";
				}
				$f++;
			}
			$c = 0;
			$criteria_fields = "";
			$ccount = count($this->criteria_fields, 0);
			while ($c < $ccount) {
				if ($c == ($ccount - 1)) {
					$criteria_fields .= $this->criteria_fields[$c] . $this->crtieria_type[$c] . $this->criteria_values[$c];
				} else {
					$criteria_fields .= $this->criteria_fields[$c] . $this->crtieria_type[$c] . $this->criteria_values[$c] . ", ";
				}
				$c++;
			}
			$this->sql = "UPDATE {$this->table} SET {$fields} WHERE {$criteria_fields}";
			mysql_query($this->sql, $this->stats_link);
		}

		if ($this->query_type == "LASTID") {
			mysql_query($this->sql, $this->stats_link);
		}
		$this->fields = array();
		$this->values = array();
		$this->criteria_fields = array();
		$this->criteria_values = array();
		$this->criteria_type = array();
		$this->criteria_connector = array();
	}

	public function auth_query() {
		if ($this->query_type == "SELECT") {
			$f = 0;
			$fields = "";
			$fcount = count($this->fields,0);
			while ($f < $fcount) {
				if ($f == ($fcount - 1)) {
					$fields .= $this->fields[$f];
				} else {
					$fields .= "{$this->fields[$f]}, ";
				}
				$f++;
			}
			$c = 0;
			$criteria_fields = "";
			$ccount = count($this->criteria_fields, 0);
			while ($c < $ccount) {
				if ($c == ($ccount - 1)) {
					$criteria_fields .= $this->criteria_fields[$c] . $this->crtieria_type[$c] . $this->criteria_values[$c];
				} else {
					$criteria_fields .= $this->criteria_fields[$c] . $this->crtieria_type[$c] . $this->criteria_values[$c] . " " . $this->criteria_connector[$c] . " ";
				}
				$c++;
			}
			if ($criteria_fields == "") {
				$criteria_fields = "TRUE";
			}
			$this->sql = "SELECT {$fields} FROM {$this->table} WHERE {$criteria_fields}";
			$this->results = mysql_query($this->sql, $this->auth_link);
		}
		$this->fields = array();
		$this->values = array();
		$this->criteria_fields = array();
		$this->criteria_values = array();
		$this->criteria_type = array();
		$this->criteria_connector = array();
	}

	public function get_row() {
		return mysql_fetch_row($this->results);
	}

	public function normalize_string($text) {
		// Normalize text for safe database usage and processing
		return (string) $text;
	}

	public function normalize_int($number) {
		// Normalize number for safe database usage and processing
		return (int) $number;
	}

	public function get_last_id() {
		$this->sql = "SELECT LAST_INSERT_ID()";
		$this->query_type = "LASTID";
		$this->stats_query();
		$row = $this->get_row();
		return $row[0];
	}

}

?>
