<?php

/****************************************

	log.php

	Class Team

*/

class Log {

	// Properties
	public $name;
	public $path;
	public $last_line;
	public $current_location;
	public $db;
	public $servers_id;
	public $handle;
	public $location;
	public $ldate;
	public $ltime;
	public $lepoch;

	// Methods
	public function get_line() {
		if ($this->last_line < $this->get_current_log_lines()) {
			$line = fgets($this->handle);
			$this->location = ftell($this->handle);
			$this->update_log_location();
			return $line;
		} else {
			return false;
		}
	}

	public function update_log_location() {
		$this->db->table = $this->db->prefix . "log_last";
		$this->db->query_type = "UPDATE";
		$this->db->fields[0] = "`line`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($this->last_line)) . "'";
		$this->db->fields[1] = "`location`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($this->location)) . "'";
		$this->db->criteria_fields[0] = "`servers_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($this->servers_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
	}

	public function get_last_log_location() {
		$this->db->table = $this->db->prefix . "log_last";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`location`";
		$this->db->criteria_fields[0] = "`servers_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($this->servers_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return 0;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	}

	public function get_current_log_lines() {
		$logfile = $this->path . $this->name;
		return trim(`wc --lines < $logfile`);
	}

	public function get_last_log_line() {
		$this->db->table = $this->db->prefix . "log_last";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`line`";
		$this->db->criteria_fields[0] = "`servers_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($this->servers_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	}

	public function move_to_last_log_location() {
		fseek($this->handle, $this->get_last_log_location());
	}

	public function open_log() {
		$logfile = $this->path . $this->name;
		$this->loghandle = fopen($logfile, "r");
	}

	public function close_log() {
		fclose($this->loghandle);
	}

}

?>
