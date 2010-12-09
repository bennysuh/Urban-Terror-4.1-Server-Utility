<?php

/****************************************

	server.php

	Class Server

*/

require_once("db.php");

class Server {

	// Properties
	public $slots;
	public $db;
	public $tag;
	public $ip;
	public $port;
	public $rcon;
	public $servers_id;

	// Methods

	public function __construct() {
		
	}

	public function __destruct() {
		unset($this->db);
		unset($this->tag);
		unset($this->ip);
		unset($this->port);
		unset($this->rcon);
		unset($this->servers_id);
		// Do for each unset of slots
		foreach ($this->slots as &$value) {
			$value->__destruct();
			unset($value);
		}
	}

	public function connect($slot) {
		// Perform player connection handling
		// Handle GUID first
		$this->slots[$slot]->guids_id = $this->is_guid_entered($this->slots[$slot]->cl_guid);
		if ($this->slots[$slot]->guids_id) {
			// Player already exists
		} else {
			$this->store_guid($this->slots[$slot]->cl_guid);
			$this->slots[$slot]->guids_id = $this->db->get_last_id();
		}
		// IP Next
		$this->slots[$slot]->ips_id = $this->is_ip_entered($this->slots[$slot]->ip, $this->slots[$slot]->guids_id);
		if ($this->slots[$slot]->ips_id) {
			// Player already exists
		} else {
			$this->store_ip($this->slots[$slot]->ip, $this->slots[$slot]->guids_id);
			$this->slots[$slot]->ips_id = $this->db->get_last_id();
		}
		// Name
		$this->slots[$slot]->names_id = $this->is_name_entered($this->slots[$slot]->name, $this->slots[$slot]->guids_id);
		if ($this->slots[$slot]->names_id) {
			// Player already exists
		} else {
			$this->store_name($this->slots[$slot]->name, $this->slots[$slot]->guids_id);
			$this->slots[$slot]->names_id = $this->db->get_last_id();
		}
		// Check for stats entries in Month, Year and Lifetime
		// Month
		$this->slots[$slot]->stats_month = $this->is_stats_month_entered($this->slots[$slot]->names_id, $this->servers_id);
		if ($this->slots[$slot]->stats_month) {
			// Player already exists
		} else {
			$this->store_month($this->slots[$slot]->names_id);
			$this->slots[$slot]->stats_month = $this->db->get_last_id();
		}
		// Year
		$this->slots[$slot]->stats_year = $this->is_stats_year_entered($this->slots[$slot]->names_id, $this->servers_id);
		if ($this->slots[$slot]->stats_year) {
			// Player already exists
		} else {
			$this->store_year($this->slots[$slot]->names_id);
			$this->slots[$slot]->stats_year = $this->db->get_last_id();
		}
		// Lifetime
		$this->slots[$slot]->stats_lifetime = $this->is_stats_lifetime_entered($this->slots[$slot]->names_id, $this->servers_id);
		if ($this->slots[$slot]->stats_lifetime) {
			// Player already exists
		} else {
			$this->store_lifetime($this->slots[$slot]->names_id);
			$this->slots[$slot]->stats_lifetime = $this->db->get_last_id();
		}
		// Player activity record handling
		$this->slots[$slot]->activity_id = $this->begin_activity_record();
	} // end connect
	
	public function disconnect($slot) {
		// end activity
		$this->end_activity_record($this->slots[$slot]->guids_id, $this->slots[$slot]->ips_id, $this->slots[$slot]->names_id, $slot, $this->slots[$slot]->activity_id);
		// Store stats
		$this->store_stats($slot);
		// destroy player object
		$this->slots[$slot]->__destruct();
	} // end disconnect

	public function store_stats($slot) {
		$this->db->table = $this->db->prefix . "scores_month";
		$this->db->query_type = "UPDATE";
		$this->db->fields[0] = "`date_last_seen`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_string(date('Y-m-d', $t))) . "'";
		$this->db->fields[1] = "`end_time`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_string(date('H:i:s', $t))) . "'";
		$this->db->fields[2] = "`disconnect_epoch`";
		$this->db->values[2] = "'$t'";
		$this->db->criteria_fields[0] = "`id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($activity_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
	} // end store_stats

	public function begin_activity_record($guids_id, $ips_id, $names_id, $slot) {
		$t = time();
		$this->db->table = $this->db->prefix . "player_activity";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`guids_id`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
		$this->db->fields[1] = "`ips_id`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($ips_id)) . "'";
		$this->db->fields[2] = "`names_id`";
		$this->db->values[2] = "'" . mysql_escape_string($this->db->normalize_int($names_id)) . "'";
		$this->db->fields[3] = "`servers_id`";
		$this->db->values[3] = "'" . mysql_escape_string($this->db->normalize_int($this->servers_id)) . "'";
		$this->db->fields[4] = "`slot`";
		$this->db->values[4] = "'" . mysql_escape_string($this->db->normalize_int($slot)) . "'";
		$this->db->fields[5] = "`date_first_seen`";
		$this->db->values[5] = "'" . mysql_escape_string($this->db->normalize_string(date('Y-m-d', $t))) . "'";
		$this->db->fields[6] = "`start_time`";
		$this->db->values[6] = "'" . mysql_escape_string($this->db->normalize_string(date('H:i:s', $t))) . "'";
		$this->db->fields[7] = "`date_last_seen`";
		$this->db->values[7] = "'" . mysql_escape_string($this->db->normalize_string(date('Y-m-d', $t))) . "'";
		$this->db->fields[8] = "`end_time`";
		$this->db->values[8] = "'" . mysql_escape_string($this->db->normalize_string(date('H:i:s', $t))) . "'";
		$this->db->fields[9] = "`status`";
		$this->db->values[9] = "'1'";
		$this->db->fields[10] = "`connect_epoch`";
		$this->db->values[10] = "'$t'";
		$this->db->fields[11] = "`disconnect_epoch`";
		$this->db->values[11] = "'$t'";
		$this->db->stats_query();
		return $this->db->get_last_id();
	} // end begin_activity_record

	public function end_activity_record($guids_id, $ips_id, $names_id, $slot, $activity_id) {
		$t = time();
		$this->db->table = $this->db->prefix . "player_activity";
		$this->db->query_type = "UPDATE";
		$this->db->fields[0] = "`date_last_seen`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_string(date('Y-m-d', $t))) . "'";
		$this->db->fields[1] = "`end_time`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_string(date('H:i:s', $t))) . "'";
		$this->db->fields[2] = "`disconnect_epoch`";
		$this->db->values[2] = "'$t'";
		$this->db->criteria_fields[0] = "`id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($activity_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
	} // end end_activity_record

	public function is_guid_entered($cl_guid) {
		$this->db->table = $this->db->prefix . "guids";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`cl_guid`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_string($cl_guid)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	} // end is_guid_entered

	public function store_guid($cl_guid) {
		$this->db->table = $this->db->prefix . "guids";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`servers_id`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($this->servers_id)) . "'";
		$this->db->fields[1] = "`cl_guid`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_string($cl_guid)) . "'";
		$this->db->fields[2] = "`store_time`";
		$this->db->values[2] = time();
		$this->db->stats_query();
	} // end store_guid

	public function is_ip_entered($ip, $guids_id) {
		$this->db->table = $this->db->prefix . "ips";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`ip`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_string($ip)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->criteria_connector[0] = "AND";
		$this->db->criteria_fields[1] = "`guids_id`";
		$this->db->criteria_values[1] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
		$this->db->criteria_type[1] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	} // end is_ip_entered

	public function store_ip($ip, $guids_id) {
		$this->db->table = $this->db->prefix . "ips";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`guids_id`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
		$this->db->fields[1] = "`ip`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_string($ip)) . "'";
		$this->db->fields[2] = "`store_time`";
		$this->db->values[2] = time();
		$this->db->stats_query();
	} // end store_ip

	public function is_name_entered($name, $guids_id) {
		$this->db->table = $this->db->prefix . "names";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`name`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_string($name)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->criteria_connector[0] = "AND";
		$this->db->criteria_fields[1] = "`guids_id`";
		$this->db->criteria_values[1] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
		$this->db->criteria_type[1] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	} // end is_name_entered

	public function store_name($name, $guids_id) {
		$this->db->table = $this->db->prefix . "names";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`guids_id`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
		$this->db->fields[1] = "`name`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_string($name)) . "'";
		$this->db->fields[2] = "`store_time`";
		$this->db->values[2] = time();
		$this->db->stats_query();
	} // end store_name

	public function is_stats_month_entered($names_id, $servers_id) {
		$this->db->table = $this->db->prefix . "stats_month";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`servers_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($servers_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->criteria_connector[0] = "AND";
		$this->db->criteria_fields[1] = "`names_id`";
		$this->db->criteria_values[1] = "'" . mysql_escape_string($this->db->normalize_int($names_id)) . "'";
		$this->db->criteria_type[1] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	} // end is_stats_month_entered

	public function store_month($names_id, $servers_id) {
		$this->db->table = $this->db->prefix . "stats_month";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`names_id`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($names_id)) . "'";
		$this->db->fields[1] = "`servers_id`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($servers_id)) . "'";
		$this->db->stats_query();
	} // end store_month

	public function is_stats_year_entered($names_id, $servers_id) {
		$this->db->table = $this->db->prefix . "stats_year";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`servers_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($servers_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->criteria_connector[0] = "AND";
		$this->db->criteria_fields[1] = "`names_id`";
		$this->db->criteria_values[1] = "'" . mysql_escape_string($this->db->normalize_int($names_id)) . "'";
		$this->db->criteria_type[1] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	} // end is_stats_year_entered

	public function store_year($names_id, $servers_id) {
		$this->db->table = $this->db->prefix . "stats_year";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`names_id`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($names_id)) . "'";
		$this->db->fields[1] = "`servers_id`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($servers_id)) . "'";
		$this->db->stats_query();
	} // end store_year

	public function is_stats_lifetime_entered($names_id, $servers_id) {
		$this->db->table = $this->db->prefix . "stats_lifetime";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`servers_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($servers_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->criteria_connector[0] = "AND";
		$this->db->criteria_fields[1] = "`names_id`";
		$this->db->criteria_values[1] = "'" . mysql_escape_string($this->db->normalize_int($names_id)) . "'";
		$this->db->criteria_type[1] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	} // end is_stats_lifetime_entered

	public function store_lifetime($names_id, $servers_id) {
		$this->db->table = $this->db->prefix . "stats_lifetime";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`names_id`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($names_id)) . "'";
		$this->db->fields[1] = "`servers_id`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($servers_id)) . "'";
		$this->db->stats_query();
	} // end store_lifetime

	public function player_begin() {
		if ($this->red->players > $this->blue->players) {
			// Assign to blue
		}
		if ($this->red->players < $this->blue->players) {
			// Assign to red
		}
		if ($this->red->players == $this->blue->players) {
			// Perform random assignment
		}
	}

	public function tag_enforcement() {

	} // end tag_enforcement

	public function team_enforcement() {

	} // end team_enforcement

	public function schedule_kick($slot = 0, $ban_info_id = 0, $comments = "none") {
		if ($slot == 0) {
			$guids_id = 0;
			$names_id = 0;
			$ips_id = 0;
		} else {
			$guids_id = $this->slots[$slot]->guids_id;
			$names_id = $this->slots[$slot]->names_id;
			$ips_id = $this->slots[$slot]->ips_id;
		}
		$this->db->table = $this->db->prefix . "script_actions";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`action`";
		$this->db->values[0] = "'" .mysql_escape_string($this->db->normalize_string("kick")) . "'";
		$this->db->fields[1] = "`epoch`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int(time())) . "'";
		$this->db->fields[2] = "`slot`";
		$this->db->values[2] = "'" . mysql_escape_string($this->db->normalize_int($slot)) . "'";
		$this->db->fields[3] = "`ban_info_id`";
		$this->db->values[3] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
		$this->db->fields[4] = "`guids_id`";
		$this->db->values[4] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
		$this->db->fields[5] = "`names_id`";
		$this->db->values[5] = "'" . mysql_escape_string($this->db->normalize_int($names_id)) . "'";
		$this->db->fields[6] = "`ips_id`";
		$this->db->values[6] = "'" . mysql_escape_string($this->db->normalize_int($ips_id)) . "'";
		$this->db->fields[7] = "`comments`";
		$this->db->values[7] = "'" . mysql_escape_string($this->db->normalize_string($comments)) . "'";
		$this->db->fields[7] = "`status`";
		$this->db->values[7] = "'" . mysql_escape_string($this->db->normalize_string("incomplete")) . "'";
		$this->db->stats_query();
	} // end schedule_kick

	public function kick($slot, $reason = "no reason given") {
		$rcmd = "kick $slot" . '"' . $reason . '"';
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$timeout = array("sec" => 1, "usec" => 0);;
		if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout)) {
			echo 'Unable to set option on socket: '. socket_strerror(socket_last_error()) . PHP_EOL;
		}
		$buf = "\xff\xff\xff\xffrcon $this->rcon $rcmd";
		socket_sendto($socket, $buf, strlen($buf), 0, $this->ip, $this->port);
	} // end kick

	public function mute($slot) {
		$rcmd = "mute $slot";
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$timeout = array("sec" => 1, "usec" => 0);;
		if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout)) {
			echo 'Unable to set option on socket: '. socket_strerror(socket_last_error()) . PHP_EOL;
		}
		$buf = "\xff\xff\xff\xffrcon $this->rcon $rcmd";
		socket_sendto($socket, $buf, strlen($buf), 0, $this->ip, $this->port);
	} // end mute

	public function tell($slot, $message) {
		$rcmd = "tell $slot" . '"' . $message . '"';
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$timeout = array("sec" => 1, "usec" => 0);;
		if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout)) {
			echo 'Unable to set option on socket: '. socket_strerror(socket_last_error()) . PHP_EOL;
		}
		$buf = "\xff\xff\xff\xffrcon $this->rcon $rcmd";
		socket_sendto($socket, $buf, strlen($buf), 0, $this->ip, $this->port);
	} // end tell

}

?>
