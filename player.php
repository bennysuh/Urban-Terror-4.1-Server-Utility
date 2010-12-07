<?php

/****************************************

	player.php

	Class Player

*/

class Player {

	// Properties

	public $name; // client Name
	public $names_id; // ID from database for Name
	public $name_length; // Name length of client
	public $ip; // client IP
	public $ips_id; // ID from database for IP
	public $cl_guid; // client GUID
	public $guids_id; // ID from database for GUID
	public $gear; // Gear string
	public $current_team; // Team as defined by log
	public $assigned_team; // Team assigned by utility
	public $time_connected; // Timestamp of when someone connected
	public $time_team_join; // Use to determine how long someone has been in spec
	public $names_id_last_bleed; // name ID of last person to make this player bleed
	public $ips_id_last_bleed; // ip ID of last person to make this player bleed
	public $guids_id_last_bleed; // guid ID of last person to make this player bleed
	public $headshots_month; // Number of headshots this player has for the current month
	public $headshots_game; // Number of headshots this player has for their current session
	public $kills_month; // Number of kills this player has for the current month
	public $kills_game; // Number of kills this player has for the current session
	public $deaths_month; // Number of times this player has died this month
	public $deaths_game; // Number of times this player has died during the current session
	public $damage_month; // Amount of damage this player has dealt during the month
	public $damage_game; // Amount of damage this player has dealt during the current session
	public $points_month; // Total points for the month for this player
	public $points_game; // Total points for this session
	public $rank; // Last determined rank
	public $muted; // Flag to denote that a player has been muted
	public $permissions_id; // admin_id
	public $permissions_type; // Defined permission such as evo_admin, evo_member or guest_admin
	public $password; // Temporary password used for registering an admin/member/guest admin
	public $user_id; // User ID as defined in Joomla. Set by retrieving from permissions table
	public $db; // Placeholder for
	public $servers_id; // Server ID
	public $tagged_up; // Is the player wearing a tag?
	public $new_player; // flag to denote a new player
	public $entered_game; // flag to denote a player has completed the connection process
	public $stats_month; // id of the player's monthly stats row
	public $stats_year; // id of the player's yearly stats row
	public $stats_lifetime; // id of the player's lifetime stats row

	// Methods
	
	public function __destruct(){
		unset($this->name);
		unset($this->names_id);
		unset($this->name_length);
		unset($this->ip);
		unset($this->ips_id);
		unset($this->cl_guid);
	} // end destruct

	public function get_names_id() {
		$this->db->table = $this->db->prefix . "names";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`guids_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($this->guids_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->criteria_connector[0] = "AND";
		$this->db->criteria_fields[1] = "`name`";
		$this->db->criteria_values[1] = "'" . mysql_escape_string($this->db->normalize_string($this->name)) . "'";
		$this->db->criteria_type[1] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	}

	public function get_ips_id() {
		$this->db->table = $this->db->prefix . "ips";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`guids_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($this->guids_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->criteria_connector[0] = "AND";
		$this->db->criteria_fields[1] = "`ip`";
		$this->db->criteria_values[1] = "'" . mysql_escape_string($this->db->normalize_string($this->ip)) . "'";
		$this->db->criteria_type[1] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	}

	public function get_guids_id() {
		$this->db->table = $this->db->prefix . "guids";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`cl_guid`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_string($this->cl_guid)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->criteria_connector[0] = "AND";
		$this->db->criteria_fields[1] = "`servers_id`";
		$this->db->criteria_values[1] = "'" . mysql_escape_string($this->db->normalize_int($this->servers_id)) . "'";
		$this->db->criteria_type[1] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	}

	public function create_player() {

	}

	public function update_player() {
		//
	}

	public function connect() {
		// Perform connection operations

	}

}
?>
