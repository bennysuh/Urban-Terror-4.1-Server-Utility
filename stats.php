<?php

/****************************************

	stats.php

	Class Parser

*/

class Stats {

	// Properties
	public $servers_id;
	public $db;
	public $server;

	// Methods
	
	public function __destruct(){
		unset($this->servers_id);
		unset($this->db);
		unset($this->server);
	} // end destruct
	
	public function store_log_line($log_time, $log_date, $log_epoch, $log_location, $last_log_line, $action_type){
		$this->db->table = $this->db->prefix . "log";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`type`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($action_type)) . "'";
		$this->db->fields[1] = "`servers_id`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($servers_id)) . "'";
		$this->db->fields[2] = "`log_line`";
		$this->db->values[2] = "'" . mysql_escape_string($this->db->normalize_int($last_log_line)) . "'";
		$this->db->fields[3] = "`log_location`";
		$this->db->values[3] = "'" . mysql_escape_string($this->db->normalize_int($log_location)) . "'";
		$this->db->fields[4] = "`log_date`";
		$this->db->values[4] = "'" . mysql_escape_string($this->db->normalize_string($log_date)) . "'";
		$this->db->fields[5] = "`log_time`";
		$this->db->values[5] = "'" . mysql_escape_string($this->db->normalize_string($log_time)) . "'";
		$this->db->fields[6] = "`log_epoch`";
		$this->db->values[6] = "'" . mysql_escape_string($this->db->normalize_int($log_epoch)) . "'";
		$this->db->stats_query();
	}

	public function process_clientuserinfo($line) {
		$info_changed = false;
		$slotend = strpos($line, ' ');
		$slot = substr($line, 0, $slotend);
		$line = substr($line, $slotend);
		$line = ltrim($line);
		$line = ltrim($line, "\\");
		$line = rtrim($line);
		$infoarray = explode("\\", $line);
		$infoitems = count($infoarray,0);
		$j = 0;
		while ($j < $infoitems) {
			if ($infoarray[$j] == "ip") {
				// $j + 1 is the IP address
				$ip = $infoarray[$j+1];
			}
			if ($infoarray[$j] == "name") {
				// $j + 1 is the name
				$name = $infoarray[$j+1];
				$name_length = strlen($name);
			}
			if ($infoarray[$j] == "cl_guid") {
				// $j + 1 is the GUID
				$cl_guid = $infoarray[$j+1];
			}
			if ($infoarray[$j] == "gear") {
				// $j + 1 is the gear
				$gear = $infoarray[$j+1];
			}
			if ($infoarray[$j] == "password") {
				// $j + 1 is the password
				$password = $infoarray[$j+1];
			}
			if ($infoarray[$j] == "admin_id") {
				// $j + 1 is the password
				$permissions_id = $infoarray[$j+1];
			}
			$j++;
		}
		// First, let's check to see if someone is tagged up
		$colorcodes = array('^0', '^1', '^2', '^3', '^4', '^5', '^6', '^7', '^8', '^9');
		$colorreplace = array('', '', '', '', '', '', '', '', '', '');
		$temp_name = str_replace($colorcodes, $colorreplace, $name);
		// Now that any color codes are removed, let's see if there's an attempt to have a tag in place
		if (stripos(' ' . $temp_name, "/evo/") || stripos($temp_name, "(evo)") || stripos($temp_name, "[evo]") || stripos($temp_name, "{evo}")) {
			// There appears to be a tag in place.
			$this->server->slots[$slot]->tagged_up = 1;
		} else {
			$this->server->slots[$slot]->tagged_up = 0;
		}
		if ($this->server->slots[$slot]->new_player) {
			// Definitely a new player
			$this->server->slots[$slot]->ip = $ip;
			$this->server->slots[$slot]->name = $name;
			$this->server->slots[$slot]->cl_guid = $cl_guid;
			$this->server->slots[$slot]->name_length = $name_length;
			$this->server->slots[$slot]->password = $password;
			$this->server->slots[$slot]->gear = $gear;
			$this->server->slots[$slot]->permissions_id = $$permissions_id;
			$this->server->slots[$slot]->time_connected = time();
			$this->server->connect($slot);
			$info_changed = true;
		}
		// Let's see if anything has changed
		if ($cl_guid != $this->server->slots[$slot]->cl_guid) {
			// Somehow, a player disconnect was mised. Let's disconnect existing player record first
			$this->server->slots[$slot]->disconnect();
			$this->server->slots[$slot] = new Player;
			$this->server->slots[$slot]->ip = $ip;
			$this->server->slots[$slot]->name = $name;
			$this->server->slots[$slot]->cl_guid = $cl_guid;
			$this->server->slots[$slot]->name_length = $name_length;
			$this->server->slots[$slot]->password = $password;
			$this->server->slots[$slot]->gear = $gear;
			$this->server->slots[$slot]->permissions_id = $permissions_id;
			$this->server->slots[$slot]->time_connected = time();
			$this->server->connect($slot);
			$info_changed = true;
		} else {
			// Check IP
			if ($ip != $this->server->slots[$slot]->ip) {
				$this->server->slots[$slot]->disconnect();
				$this->server->slots[$slot] = new Player;
				$this->server->slots[$slot]->ip = $ip;
				$this->server->slots[$slot]->name = $name;
				$this->server->slots[$slot]->cl_guid = $cl_guid;
				$this->server->slots[$slot]->name_length = $name_length;
				$this->server->slots[$slot]->password = $password;
				$this->server->slots[$slot]->gear = $gear;
				$this->server->slots[$slot]->permissions_id = $permissions_id;
				$this->server->slots[$slot]->time_connected = time();
				$this->server->connect($slot);
				$info_changed = true;
			} else {
				// Same player, let's check name
				if ($name != $this->server->slots[$slot]->name) {
					// Player has changed names
					$this->server->slots[$slot]->change_name($name);
					$info_changed = true;
				}
			}
		}
		if ($password != $this->server->slots[$slot]->password) {
			$this->server->slots[$slot]->password = $this->normalize_string($password);
			$info_changed = true;
		}
		if ($permissions_id != $this->server->slots[$slot]->permissions_id) {
			$this->server->slots[$slot]->permissions_id = $this->normalize_int($permissions_id);
			$info_changed = true;
		}
		if ($gear != $this->server->slots[$slot]->gear) {
			$this->server->slots[$slot]->gear = $this->normalize_string($gear);
			$info_changed = true;
		}
		if ($info_changed) {
			// Update restore info and get permissions
			$this->server->slots[$slot]->get_permissions();
			if ($this->server->slots[$slot]->tagged_up && $this->server->tag_enforcement()) {
				if ($this->server->slots[$slot]->permissions_type != "evo_admin" || $this->server->slots[$slot]->permissions_type != "evo_member") {
					$this->server->schedule_kick($slot, 0, "improperly tagged");
				}
			}
			$this->server->slots[$slot]->update_recover();
		}
	} // end process_clientuserinfo

	public function process_clientuserinfochanged($line) {
		$slotend = strpos($line, ' ');
		$slot = substr($line, 0, $slotend);
		$line = substr($line, $slotend);
		$line = ltrim($line);
		$line = rtrim($line);
		$infoarray = explode("\\", $line);
		$infoitems = count($infoarray,0);
		$j = 0;
		while ($j < $infoitems) {
			if ($infoarray[$j] == "t") {
				// $j + 1 is the team
				$this->server->slots[$slot]->current_team = $infoarray[$j+1];
			}
			$j++;
		}
	} // end process_clientuserinfochanged

	public function process_clientbegin($line) {
		$slotend = strpos($line, "\n");
		$slot = substr($line, 0, $slotend);
		if ($this->server->team_enforcement()) {
			$this->server->slots[$slot]->enforce_team();
		}
		// See if GUID is muted
		if ($guid_mute_info_id = $this->bans->is_guid_muted($this->server->slots[$slot]->guids_id)) {
			if ($ip_mute_info_id = $this->bans->is_ip_muted($this->server->slots[$slot]->ips_id)) {
				// IP already muted
			} else {
				// New IP, expire old IP mutes
				$this->bans->expire_ip_mute($guid_mute_info_id);
				// Mute new IP
				$expiration = $this->bans->get_ban_expiration($guid_mute_info_id);
				$this->bans->mute_ip($guid_mute_info_id, $this->server->slots[$slot]->ips_id, $expiration);
			}
		}
		// See if IP is muted
		if ($ip_mute_info_id = $this->bans->is_ip_muted($this->server->slots[$slot]->ips_id)) {
			if ($guid_mute_info_id = $this->bans->is_guid_muted($this->server->slots[$slot]->guids_id)) {
				// GUID already muted
			} else {
				// Mute new GUID
				$expiration = $this->bans->get_ban_expiration($ip_mute_info_id);
				$this->bans->mute_guid($ip_mute_info_id, $this->server->slots[$slot]->guids_id, $expiration);
			}
		}
		// Enforce mutes
		if (($guid_mute_info_id || $ip_mute_info_id) && !$this->server->slots[$slot]->muted) {
			// Player needs to be muted
			$this->server->mute($slot);
			// Inform the player
			if ($guid_mute_info_id) {
				$ban_info_id = $guid_mute_info_id;
			}
			if ($ip_mute_info_id) {
				$ban_info_id = $ip_mute_info_id;
			}
			usleep(5000);
			$this->server->slots[$slot]->muted = 1;
			$mutemessage = "^2You ^2were ^2muted ^2by ^2an ^2admin.";
			$this->tell($this->slot, $mutemessage);
			usleep(5000);
			$mutemessage = "^2Contact ^2bans@evogc.com ^2and ^2reference ^2mute ^2id ^2{$ban_info_id} ^2to ^2inquire ^2further.";
			$this->server->tell($slot, $mutemessage);
		}
		// See if GUID is banned
		if ($guid_ban_info_id = $this->bans->is_guid_banned($this->server->slots[$slot]->guids_id)) {
			if ($ip_ban_info_id = $this->bans->is_ip_banned($this->server->slots[$slot]->ips_id)) {
				// IP already banned
			} else {
				// New IP, expire old IP bans
				$this->bans->expire_ip_ban($guid_ban_info_id);
				// Ban new IP
				$expiration = $this->bans->get_ban_expiration($guid_ban_info_id);
				$this->bans->ban_ip($guid_ban_info_id, $this->server->slots[$slot]->ips_id, $expiration);
			}
		}
		// See if IP is banned
		if ($ip_ban_info_id = $this->bans->is_ip_banned($this->server->slots[$slot]->ips_id)) {
			if ($guid_ban_info_id = $this->bans->is_guid_banned($this->server->slots[$slot]->guids_id)) {
				// GUID already banned
			} else {
				// Ban new GUID
				$expiration = $this->bans->get_ban_expiration($ip_ban_info_id);
				$this->bans->ban_guid($ip_ban_info_id, $this->server->slots[$slot]->guids_id, $expiration);
			}
		}
		// See if Name is allowed
		if ($name_ban_info_id = $this->bans->is_name_forbidden($this->server->slots[$slot]->name)) {
			// Name is forbidden, kick with reason
			$reason = "^2Your ^2name ^2is ^2forbidden. ^2For ^2more ^2information, ^2email ^2bans@evogc.com ^2and ^2reference ^2forbidden ^2name ^2id ^2{$name_ban_info_id}";
			$this->server->kick($slot, $reason);
		}
		// See if player has been banned
		if ($ip_ban_info_id || $guid_ban_info_id) {
			// Banned. Kick with reason
			if ($guid_ban_info_id) {
				$ban_info_id = $guid_ban_info_id;
			}
			if ($ip_ban_info_id) {
				$ban_info_id = $ip_ban_info_id;
			}
			$reason = "You have been banned. Contact bans@evogc.com and reference ban code {$ban_info_id} to inquire about your ban.";
			$this->kick($slot, $reason);
		}
		$this->server->slots[$slot]->entered_game = 1;
		$this->server->slots[$slot]->update_recover();
	} // end process_clientbegin

	public function process_clientdisconnect($line) {
		$slotend = strpos($line, "\n");
		$slot = substr($line, 0, $slotend);
		$this->update_activity($slot);
		$this->server->disconnect($slot);


		$t = time();
		$this->time_seen = date('H:i:s', $t);
		$this->date_seen = date('Y-m-d', $t);
		$table = $this->prefix . "player_activity";
		$queryselect = "SELECT `id`";
		$queryfrom = "FROM $table ";
		$querywhere = "WHERE guids_id=%d AND names_id=%d AND ips_id=%d AND slot=%d AND servers_id=%d AND status = 1";
		//$queryorder = "ORDER BY date_seen DESC, end_time DESC LIMIT 1";
		$querytext = $queryselect . $queryfrom . $querywhere;
		$query = sprintf($querytext, $this->guids_id, $this->names_id, $this->ips_id, $this->slot, $this->sv_id);
		$activityresult = mysql_query($query, $this->link);
		//echo "Line: " . __LINE__ . " " . $query . "\n";
		$activityrow = mysql_fetch_row($activityresult);
		$queryupdate = "UPDATE $table ";
		$queryset = "SET end_time = '$this->time_seen', date_last_seen = '$this->date_seen', status = 0 ";
		$querywhere = "WHERE `id` = $activityrow[0]";
		$query = $queryupdate . $queryset . $querywhere;
		$activityresult = mysql_query($query, $this->link);
	} // end process_clientdisconnect

	public function update_activity($slot) {

	} // end update_activity

	public function create_activity($slot) {

	} // end create_activity

	public function end_activity($slot){

	} // end end_activity

	public function is_active($slot) {
		$this->db->table = $this->db->prefix . "player_activity";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`guids_id`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_int($this->server->slots[$slot]->guids_id)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->criteria_connector[0] = "AND";
		$this->db->criteria_fields[1] = "`ips_id`";
		$this->db->criteria_values[1] = "'" . mysql_escape_string($this->db->normalize_int($this->server->slots[$slot]->ips_id)) . "'";
		$this->db->criteria_type[1] = "=";
		$this->db->criteria_connector[1] = "AND";
		$this->db->criteria_fields[2] = "`names_id`";
		$this->db->criteria_values[2] = "'" . mysql_escape_string($this->db->normalize_int($this->server->slots[$slot]->names_id)) . "'";
		$this->db->criteria_type[2] = "=";
		$this->db->criteria_connector[2] = "AND";
		$this->db->criteria_fields[3] = "`servers_id`";
		$this->db->criteria_values[3] = "'" . mysql_escape_string($this->db->normalize_int($this->server->servers_id)) . "'";
		$this->db->criteria_type[3] = "=";
		$this->db->criteria_connector[3] = "AND";
		$this->db->criteria_fields[4] = "`status`";
		$this->db->criteria_values[4] = "'1'";
		$this->db->criteria_type[4] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	} // end is_active
}
