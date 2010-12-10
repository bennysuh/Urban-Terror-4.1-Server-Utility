<?php

/****************************************
 *
 *	bans.php
 *
 *	Class Bans
 *
 *
 *      Copyright 2010 Aaron Kincer <kincera@gmail.com>
 *
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */

// Version 0.1.2

class Bans {

	// Properties
	public $db;

	// Methods
	public function expire_ip_mute($ban_info_id) {
		$this->db->table = $this->db->prefix . "muted_ips";
		$this->db->query_type = "UPDATE";
		$this->db->fields[0] = "`expiration`";
		$this->db->values[0] = time();
		$this->db->criteria_fields[0] = "`ban_info_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
	}

	public function expire_guid_mute($ban_info_id) {
		$this->db->table = $this->db->prefix . "muted_guids";
		$this->db->sql = "UPDATE $table SET `expiration` = " . time() . " WHERE `ban_info_id` = $ban_info_id";
		$this->db->query_type = "UPDATE";
		$this->db->fields[0] = "`expiration`";
		$this->db->values[0] = time();
		$this->db->criteria_fields[0] = "`ban_info_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
	}

	public function expire_ip_ban($ban_info_id) {
		$this->db->table = $this->db->prefix . "banned_ips";
		$this->db->query_type = "UPDATE";
		$this->db->fields[0] = "`expiration`";
		$this->db->values[0] = time();
		$this->db->criteria_fields[0] = "`ban_info_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
	}

	public function expire_guid_ban($ban_info_id) {
		$this->db->table = $this->db->prefix . "banned_guids";
		$this->db->query_type = "UPDATE";
		$this->db->fields[0] = "`expiration`";
		$this->db->values[0] = time();
		$this->db->criteria_fields[0] = "`ban_info_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
	}

	public function expire_ban_info($ban_info_id) {
		$this->db->table = $this->db->prefix . "ban_info";
		$this->db->query_type = "UPDATE";
		$this->db->fields[0] = "`status`";
		$this->db->values[0] = "'inactive'";
		$this->db->criteria_fields[0] = "`id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
	}

	public function is_ip_muted($ips_id) {
		$this->db->table = $this->db->prefix . "muted_ips";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`ban_info_id`";
		$this->db->criteria_fields[0] = "`ips_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($ips_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	}

	public function is_ip_banned($ips_id) {
		$this->db->table = $this->db->prefix . "banned_ips";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`ban_info_id`";
		$this->db->criteria_fields[0] = "`ips_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($ips_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	}

	public function is_guid_muted($guids_id) {
		$this->db->table = $this->db->prefix . "muted_guids";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`ban_info_id`";
		$this->db->criteria_fields[0] = "`guids_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	}

	public function is_guid_banned($guids_id) {
		$this->db->table = $this->db->prefix . "banned_guids";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`ban_info_id`";
		$this->db->criteria_fields[0] = "`guids_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	}

	public function mute_ip($ban_info_id, $ips_id, $expiration) {
		// Adds the IP mute to the database
		// Check for active mute first
		if ($this->is_ip_muted($ips_id)) {
			// Mute already active
		} else {
			// No active mute found
			$this->db->table = $this->db->prefix . "muted_ips";
			$this->db->query_type = "INSERT";
			$this->db->fields[0] = "`ban_info_id`";
			$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
			$this->db->fields[1] = "`ips_id`";
			$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($ips_id)) . "'";
			$this->db->fields[2] = "`expiration`";
			$this->db->values[2] = "'" . mysql_escape_string($this->db->normalize_int($expiration)) . "'";
			$this->db->stats_query();
		}
	}

	public function ban_ip($ban_info_id, $ip, $ips_id, $expiration) {
		// Adds the IP ban to the database
		// Check for active ban first
		if ($this->is_ip_banned($ips_id)) {
			// Ban already active
		} else {
			// No active ban found
			$this->db->table = $this->db->prefix . "banned_ips";
			$this->db->query_type = "INSERT";
			$this->db->fields[0] = "`ban_info_id`";
			$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
			$this->db->fields[1] = "`ips_id`";
			$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($ips_id)) . "'";
			$this->db->fields[2] = "`expiration`";
			$this->db->values[2] = "'" . mysql_escape_string($this->db->normalize_int($expiration)) . "'";
			$this->db->fields[2] = "`ip`";
			$this->db->values[2] = "'" . mysql_escape_string($this->db->normalize_string($ip)) . "'";
			$this->db->stats_query();
		}
	}

	public function mute_guid($ban_info_id, $guids_id, $expiration) {
		// Adds the GUID mute to the database
		// Check for active mute first
		if ($this->is_guid_muted($guids_id)) {
			// Mute already active
		} else {
			// No active mute found
			$this->db->table = $this->db->prefix . "muted_guids";
			$this->db->query_type = "INSERT";
			$this->db->fields[0] = "`ban_info_id`";
			$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
			$this->db->fields[1] = "`guids_id`";
			$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
			$this->db->fields[2] = "`expiration`";
			$this->db->values[2] = "'" . mysql_escape_string($this->db->normalize_int($expiration)) . "'";
			$this->db->stats_query();
		}
	}

	public function ban_guid($ban_info_id, $guids_id, $expiration) {
		// Adds the GUID ban to the database
		// Check for active ban first
		if ($this->is_guid_banned($guids_id)) {
			// Ban already active
		} else {
			// No active ban found
			$this->db->table = $this->db->prefix . "banned_guids";
			$this->db->query_type = "INSERT";
			$this->db->fields[0] = "`ban_info_id`";
			$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
			$this->db->fields[1] = "`guids_id`";
			$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($guids_id)) . "'";
			$this->db->fields[2] = "`expiration`";
			$this->db->values[2] = "'" . mysql_escape_string($this->db->normalize_int($expiration)) . "'";
			$this->db->stats_query();
		}
	}

	public function process_script_actions() {
		$this->db->table = $this->db->prefix . "script_actions";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->fields[1] = "`action`";
		$this->db->fields[2] = "`slot`";
		$this->db->fields[3] = "`ban_info_id`";
		$this->db->fields[4] = "`guids_id`";
		$this->db->fields[5] = "`names_id`";
		$this->db->fields[6] = "`ips_id`";
		$this->db->fields[7] = "`comments`";
		$this->db->criteria_fields[0] = "`status`";
		$this->db->criteria_values[0] = "incomplete";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			// No actions to perform
			return;
		} else {
			while ($row = $this->db->get_row()) {
				if ($row[1] == "kick") {
					if ($this->server->slots[$row[2]]->entered_game) {
						// Player has entered the game, so kick
						if ($row[7] == "improperly tagged") {
							$reason = "You are not authorized to wear the {$this->server->tag} tag.";
						}
						$this->server->kick($row[2], $reason);
						$this->db->table = $this->db->prefix . "script_actions";
						$this->db->query_type = "UPDATE";
						$this->db->fields[0] = "`status`";
						$this->db->values[0] = "'complete'";
						$this->db->criteria_fields[0] = "`id`";
						$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($row[0])) . "'";
						$this->db->criteria_type[0] = "=";
						$this->db->stats_query();
					}
				}
			}
		}
	} // end process_script_actions

	public function get_ban_expiration($ban_info_id) {
		$this->db->table = $this->db->prefix . "ban_info";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`expiration`";
		$this->db->criteria_fields[0] = "`id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($ban_info_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return 0;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	} // end get_ban_expiration
}

?>
