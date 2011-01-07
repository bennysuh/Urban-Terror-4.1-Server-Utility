<?php

/****************************************
 *
 *	server.php
 *
 *	Class Server
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

require_once("db.php");

class Server {

	// Properties
	public $slots; // List of players as monitored by the log parser
	public $players; // List of players as monitored by the real-time rcon monitor
	public $db;
	public $tag;
	public $ip;
	public $port;
	public $rcon;
	public $servers_id;
	public $ip_bans;
	public $guid_bans;
	public $name_bans;
	public $ip_mutes;
	public $guid_mutes;
	public $buf = "\xFF\xFF\xFF\xFF";
	public $last_scan;
	public $scan_delay;
	public $last_rcon;
	public $error_code;
	public $server_response;

	// Settings and messages
	public $tag_enforce_strict;
	public $tag_enforce_location;
	// Methods

	public function __construct($servers_id) {
		$this->servers_id = $servers_id;
		$ip_bans = $this->get_ip_bans($this->servers_id);
		$guid_bans = $this->get_guid_bans($this->servers_id);
		$name_bans = $this->get_name_bans($this->servers_id);
		$ip_mutes = $ip_bans = $this->get_ip_mutes($this->servers_id);
		$guid_mutes = $this->get_guid_mutes($this->servers_id);
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

	public function error_processing($code) {
		if ($code == SOCKET_FAILURE) {

		}
		if ($code == INVALID_RCON) {

		}
	}

	public function process_server_tasks() {
		if ($this->first_pass) {
			// Populate ban/mute information
			if (!$this->use_ban_text_file) {
				$this->get_banned_ips();
			}
			if ($this->guid_bans_enabled) {
				$this->get_banned_guids();
			}
			if ($this->name_bans_enabled) {
				$this->get_banned_names();
			}
			if ($this->mute_enforcement_enabled) {
				$this->get_muted_ips();
				$this->get_muted_guids();
				if ($this->name_bans_enabled) {
					$this->get_muted_names();
				}
			}
			// Get last known log positions
			$this->slots_last_disconnect_recorded = $this->get_slots_last_disconnect_recorded();
			$this->last_end_of_game_recorded = $this->get_last_end_of_game_recorded();
		}
		// Get player info
		if (!$this->real_time_player_info && (((time() - $this->last_scan) > $this->scan_delay) || ($this->scan_delay == 0))) {
			// Must use rcon commands to get player info
			if ($this->stock_build) {
				// Standard UrT build
				$t = time();
				if ($this->rcon_cmd("status")) { $this->parser->rcon_status($this->server_response); } else { $this->error_processing($this->error_code); break;}
				if ($this->rcon_cmd("g_redteamlist")) { $red_team = $this->parser->rcon_g_redteamlist($this->server_response); } else { $this->error_processing($this->error_code); break; }
				if ($this->rcon_cmd("g_blueteamlist")) { $blue_team = $this->parser->rcon_g_blueteamlist($this->server_response); } else { $this->error_processing($this->error_code); break; }
				foreach ($players as $slot => $player) {
					foreach ($player as $key => $value) {
						if ($key == "name") {
							$name = $value;
						}
						if ($key == "ip") {
							$ip = $value;
						}
						if ($key == "qport") {
							$qport = $value;
						}
						if (isset($red_team[$slot])) {
							$team = 1;
						} elseif (isset($blue_team[$slot])) {
							$team = 2;
						} else {
							$team = 3;
						}
					}
					if (!isset($this->players[$slot])) {
						// New connection for this player
						$this->players[$slot] = new Player;
						$this->players[$slot]->time_connected = $t;
						$this->players[$slot]->last_updated = $t;
						$this->players[$slot]->current_team = $team;
						if ($this->rcon_cmd("dumpuser", $slot)) { $playerinfo = $this->parser->rcon_dumpuser($server_response); } else { $this->error_processing($this->error_code); break; }
						foreach ($playerinfo as $pkey => $pvalue) {
							if ($pkey == "cl_guid") {
								$this->players[$slot]->cl_guid = $pvalue;
							}
							if ($pkey == "gear") {
								$this->players[$slot]->gear = $pvalue;
							}
							if ($pkey == "name") {
								$this->players[$slot]->name = $pvalue;
							}
							if ($pkey == "ip") {
								$this->players[$slot]->ip = $pvalue;
							}
							if ($pkey == "qport") {
								$this->players[$slot]->qport = $pvalue;
							}
						}
						$this->players[$slot]->guids_id = $this->store_guid($this->players[$slot]->cl_guid);
						$this->players[$slot]->ips_id = $this->store_ip($this->players[$slot]->ip, $this->players[$slot]->guids_id);
						$this->players[$slot]->names_id = $this->store_name($this->players[$slot]->guids_id);
					} else {
						// Verify this is the same player and update
						if ($ip == $this->players[$slot]->ip && $qport == $this->players[$slot]->qport) {
							if ($name != $this->players[$slot]->name) {
								$this->players[$slot]->names_id = $this->store_name($this->players[$slot]->guids_id);
							}
							$this->players[$slot]->name = $name;
							$this->players[$slot]->current_team = $team;
							$this->players[$slot]->last_updated = $t;
						} else {
							// This is a new player. Disconnect old player
							$this->rcon_disconnect($slot);
							$this->players[$slot] = new Player;
							$this->players[$slot]->time_connected = $t;
							$this->players[$slot]->last_updated = $t;
							$this->players[$slot]->current_team = $team;
							if ($this->rcon_cmd("dumpuser", $slot)) { $playerinfo = $this->parser->rcon_dumpuser($this->server_response); } else { $this->error_processing($this->error_code); break; }
							foreach ($playerinfo as $pkey => $pvalue) {
								if ($pkey == "cl_guid") {
									$this->players[$slot]->cl_guid = $pvalue;
								}
								if ($pkey == "gear") {
									$this->players[$slot]->gear = $pvalue;
								}
								if ($pkey == "name") {
									$this->players[$slot]->name = $pvalue;
								}
								if ($pkey == "ip") {
									$this->players[$slot]->ip = $pvalue;
								}
								if ($pkey == "qport") {
									$this->players[$slot]->qport = $pvalue;
								}
							}
							$this->players[$slot]->guids_id = $this->store_guid($this->players[$slot]->cl_guid);
							$this->players[$slot]->ips_id = $this->store_ip($this->players[$slot]->ip, $this->players[$slot]->guids_id);
							$this->players[$slot]->names_id = $this->store_name($this->players[$slot]->guids_id);
						}
					}
				}
				if ($this->error_code) {
					break;
				}
				// Now let's cycle back through and find players no longer connected
				foreach ($this->players as $slot => $player) {
					if ($player->last_updated < $t) {
						$this->rcon_disconnect($slot);
					}
				}
			} elseif ($this->alpha_build) {
				// ALPHA build
				$t = time();;
				if ($this->rcon_cmd("alphastatus")) { $players = $this->parser->rcon_alphastatus($this->server_response); } else { $this->error_processing($this->error_code); break; }
				foreach ($players as $slot => $player) {
					foreach ($player as $key => $value) {
						if ($key == "name") {
							$name = $value;
						}
						if ($key == "ip") {
							$ip = $value;
						}
						if ($key == "cl_guid") {
							$cl_guid = $value;
						}
						if ($key == "team") {
							if ($value == 0) {
								$team = 3;
							} else {
								$team = $value;
							}
						}
					}
					if (!isset($this->players[$slot])) {
						// New connection for this player
						$this->players[$slot] = new Player;
						$this->players[$slot]->time_connected = $t;
						$this->players[$slot]->last_updated = $t;
						$this->players[$slot]->current_team = $team;
						$this->players[$slot]->cl_guid = $cl_guid;
						$this->players[$slot]->ip = $ip;
						$this->players[$slot]->qport = $qport;
					} else {
						if ($cl_guid == $this->players[$slot]->cl_guid) {
							$this->players[$slot]->name = $name;
							$this->players[$slot]->current_team = $team;
							$this->players[$slot]->last_updated = $t;
							$this->players[$slot]->ip = $ip;
							$this->players[$slot]->qport = $qport;
						} else {
							// This is a new player. Disconnect old player
							$this->rcon_disconnect($slot);
							$this->players[$slot] = new Player;
							$this->players[$slot]->time_connected = $t;
							$this->players[$slot]->last_updated = $t;
							$this->players[$slot]->current_team = $team;
							$this->players[$slot]->cl_guid = $cl_guid;
							$this->players[$slot]->ip = $ip;
							$this->players[$slot]->qport = $qport;
						}
					}
					$this->players[$slot]->guids_id = $this->store_guid($this->players[$slot]->cl_guid);
					$this->players[$slot]->ips_id = $this->store_ip($this->players[$slot]->ip, $this->players[$slot]->guids_id);
					$this->players[$slot]->names_id = $this->store_name($this->players[$slot]->guids_id);
				}
				if ($this->rcon_cmd("urtstatus")) { $players = $this->parser->rcon_urtstatus($this->server_resposne); } else { $this->error_processing($this->error_code); break; }
				foreach ($players as $slot => $player) {
					foreach ($player as $key => $value) {
						if ($key == "ip") {
							$ip = $value;
						}
						if ($key == "gear") {
							$gear = $value;
						}
					}
					if ($ip == $this->players[$slot]->ip) {
						$this->players[$slot]->gear = $gear;
					}
				}
				// Now let's cycle back through and find players no longer connected
				foreach ($this->players as $slot => $player) {
					if ($player->last_updated < $t) {
						$this->rcon_disconnect($slot);
					}
				}
			}
			// Get game info and player scores from the players command
			$server_response = $this->rcon_cmd("players");
			if ($this->rcon_cmd("players")) { $data = $this->parser->rcon_players($this->server_response); } else { $this->error_processing($this->error_code); break; }
			$this->map = $data["map"];
			$this->red_score = $data["red_score"];
			$this->blue_score = $data["blue_score"];
		}
		// Enforce bans
		

	} // end process_server_tasks

	public function get_banned_ips() {


	} // end get_banned_ips

	public function get_banned_guids() {


	} // end get_banned_guids

	public function get_banned_names() {


	} // end get_banned_names

	public function get_muted_ips() {


	} // end get_muted_ips

	public function get_muted_guids() {


	} // end get_muted_guids

	public function get_muted_names() {


	} // end get_muted_names

	public function get_slots_last_disconnect_recorded() {

	} // end get_slots_last_disconnect_recorded

	public function get_last_end_of_game_recorded() {

	} // end get_last_end_of_game_recorded

	public function rcon_disconnect($slot) {

	} // end rcon_disconnect

	public function recover() {
		// Recover from a script crash
		// Get log status
		$status = $this->log->get_log_status('delimiter');
		$this->log->last_processed_delimiter = $status[0];
		$this->log->last_processed_delimiter_location = $status[1];
		$status = $this->log->get_log_status('chat');
		$this->log->last_processed_chat = $status[0];
		$this->log->last_processed_chat_location = $status[1];
		$status = $this->log->get_log_status('hit');
		$this->log->last_processed_hit = $status[0];
		$this->log->last_processed_hit_location = $status[1];
		$status = $this->log->get_log_status('kill');
		$this->log->last_processed_kill = $status[0];
		$this->log->last_processed_kill_location = $status[1];
		$status = $this->log->get_log_status('clientconnect');
		$this->log->last_processed_clientconnect = $status[0];
		$this->log->last_processed_clientconnect_location = $status[1];
		$status = $this->log->get_log_status('clientdisconnect');
		$this->log->last_processed_clientdisconnect = $status[0];
		$this->log->last_processed_clientdisconnect_location = $status[1];
		$status = $this->log->get_log_status('flag');
		$this->log->last_processed_flag = $status[0];
		$this->log->last_processed_flag_location = $status[1];
		$status = $this->log->get_log_status('initgame');
		$this->log->last_processed_initgame = $status[0];
		$this->log->last_processed_initgame_location = $status[1];
		$status = $this->log->get_log_status('warmup');
		$this->log->last_processed_warmup = $status[0];
		$this->log->last_processed_warmup_location = $status[1];
		$status = $this->log->get_log_status('initround');
		$this->log->last_processed_initround = $status[0];
		$this->log->last_processed_initround_location = $status[1];
		$status = $this->log->get_log_status('flag_return');
		$this->log->last_processed_flag_return = $status[0];
		$this->log->last_processed_flag_return_location = $status[1];
		$status = $this->log->get_log_status('item');
		$this->log->last_processed_item = $status[0];
		$this->log->last_processed_item_location = $status[1];
	
		// Get team info

		$this->blueteam->players = $this->get_team_count('blue');
		$this->redteam->players = $this->get_team_count('red');

		// Get player info

		$players = $this->get_players();
		foreach ($players as $player) {
			
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
		$this->db->table = $this->db->prefix . "player_stats";
		$this->db->query_type = "UPDATE";
		$j = 0;
		if ($this->settings_scores) {
			$this->db->fields[$j] = "`score`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->score)) . "'";
			$j++;
		}
		if ($this->settings_damage_dealt) {
			$this->db->fields[$j] = "`sr8_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->sr8_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`g36_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->g36_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`lr300_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->lr300_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`m4_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->m4_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`ak47_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->ak47_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`negev_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->negev_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`ump_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->ump_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`mp5k_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->mp5k_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`de_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->de_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`psg1_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->psg1_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`baretta_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->baretta_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`knife_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->knife_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`flyingknife_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flyingknife_damage_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`boot_damage_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->boot_damage_dealt)) . "'";
			$j++;
		}
		if ($this->settings_damage_received) {
			$this->db->fields[$j] = "`sr8_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->sr8_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`g36_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->g36_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`lr300_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->lr300_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`m4_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->m4_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`ak47_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->ak47_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`negev_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->negev_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`ump_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->ump_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`mp5k_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->mp5k_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`de_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->de_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`psg1_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->psg1_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`baretta_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->baretta_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`knife_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->knife_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`flyingknife_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flyingknife_damage_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`boot_damage_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->boot_damage_received)) . "'";
			$j++;
		}
		if ($this->settings_hit_locations_dealt) {
			$this->db->fields[$j] = "`arms_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->arms_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`legs_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->legs_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`body_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->body_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`kevlar_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->kevlar_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`head_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->head_dealt)) . "'";
			$j++;
			$this->db->fields[$j] = "`helmet_dealt`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->helmet_dealt)) . "'";
			$j++;
		}
		if ($this->settings_hit_locations_received) {
			$this->db->fields[$j] = "`arms_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->arms_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`legs_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->legs_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`body_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->body_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`kevlar_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->kevlar_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`head_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->head_received)) . "'";
			$j++;
			$this->db->fields[$j] = "`helmet_received`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->helmet_received)) . "'";
			$j++;
		}
		if ($this->settings_weapon_kills) {
			$this->db->fields[$j] = "`sr8_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->sr8_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`g36_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->g36_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`lr300_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->lr300_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`m4_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->m4_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`ak47_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->ak47_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`negev_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->negev_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`ump_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->ump_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`he_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->he_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`mp5k_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->mp5k_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`de_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->de_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`psg1_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->psg1_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`hk_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->hk_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`baretta_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->baretta_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`spas_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->spas_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`knife_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->knife_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`flyinghk_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flyinghk_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`goomba_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->goobma_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`flyingknife_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flyingknife_kill)) . "'";
			$j++;
			$this->db->fields[$j] = "`boot_kill`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->boot_kill)) . "'";
			$j++;
		}
		if ($this->settings_weapon_deaths) {
			$this->db->fields[$j] = "`sr8_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->sr8_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`g36_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->g36_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`lr300_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->lr300_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`m4_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->m4_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`ak47_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->ak47_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`negev_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->negev_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`ump_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->ump_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`he_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->he_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`mp5k_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->mp5k_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`bleed_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->bleed_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`de_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->de_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`psg1_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->psg1_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`hk_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->hk_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`baretta_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->baretta_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`spas_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->spas_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`knife_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->knife_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`flyinghk_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flyinghk_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`goomba_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->goomba_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`flyingknife_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flyingknife_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`boot_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->boot_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`slap_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->slap_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`telefrag_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->telefrag_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`nuke_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->nuke_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`bomb_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->bomb_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`flag_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flag_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`fall_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`fall_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`drown_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->drown_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`suicide_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->suicide_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`environmental_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->environmental_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`heself_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->heself_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`hkself_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->hkself_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`splode_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->splode_death)) . "'";
			$j++;
			$this->db->fields[$j] = "`unknownself_death`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->unknownself_death)) . "'";
			$j++;
		}
		if ($this->settings_kills_deaths) {
			$this->db->fields[$j] = "`kills`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->kills)) . "'";
			$j++;
			$this->db->fields[$j] = "`deaths`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->deaths)) . "'";
			$j++;
		}
		if ($this->settings_ctf_stats) {
			$this->db->fields[$j] = "`flag_captures`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flag_captures)) . "'";
			$j++;
			$this->db->fields[$j] = "`flag_carrier_kills`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flag_carrier_kills)) . "'";
			$j++;
			$this->db->fields[$j] = "`flag_returns`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flag_returns)) . "'";
			$j++;
			$this->db->fields[$j] = "`flag_protections`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flag_protections)) . "'";
			$j++;
			$this->db->fields[$j] = "`flag_carrier_protections`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flag_carrier_protections)) . "'";
			$j++;
		}
		if ($this->settings_ctf_stats) {
			$this->db->fields[$j] = "`bomb_diffuses`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flag_captures)) . "'";
			$j++;
			$this->db->fields[$j] = "`bomb_carrier_kills`";
			$this->db->values[$j] = "'" . mysql_escape_string($this->db->normalize_int($this->slots[$slot]->flag_carrier_kills)) . "'";
			$j++;
		}
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
		return $this->db->get_last_id();
	} // end store_guid

	public function get_guids_id($cl_guid) {
		$this->db->table = $this->db->prefix . "guids";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`id`";
		$this->db->criteria_fields[0] = "`cl_guid`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_string($cl_guid)) . "'";
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
	} // end get_guids_id

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
		return $this->db->get_last_id();
	} // end store_ip

	public function get_ips_id($ip, $guids_id) {
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
	} // end get_ips_id

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
		return $this->db->get_last_id();
	} // end store_name

	public function get_names_id($name, $guids_id) {
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
	} // end get_names_id

	public function get_stats_id($names_id, $servers_id) {
		$this->db->table = $this->db->prefix . "player_stats";
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
	} // end get_stats_id

	public function create_stats($names_id, $servers_id) {
		$this->db->table = $this->db->prefix . "player_stats";
		$this->db->query_type = "INSERT";
		$this->db->fields[0] = "`names_id`";
		$this->db->values[0] = "'" . mysql_escape_string($this->db->normalize_int($names_id)) . "'";
		$this->db->fields[1] = "`servers_id`";
		$this->db->values[1] = "'" . mysql_escape_string($this->db->normalize_int($servers_id)) . "'";
		$this->db->stats_query();
		return $this->db->get_last_id();
	} // end store_month

	public function update_stats($slot) {

	} // end update_stats

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
	
	public function rcon_cmd($rcmd, $slot = null, $reason = null) {
		$this->rcon_buffer();
		$rcmd = build_rcon_cmd($rcmd, $reason, $slot);
		$socket = fsockopen("udp://" . $this->ip, $this->port, $errno, $errstr, 5);
		if (!$socket || !isset($rcmd))
                {
                        $this->error_code = SOCKET_FAILURE;
			return false;
                } else {
			$this->error_code = false;
		}
		fwrite ($socket, $rcmd);
		stream_set_timeout($socket, 1, 0);
		$response = '';
		do {
			$read = fread($socket, 9999);
			$response .= substr(strstr($read, "\n"), 1);
			$info = stream_get_meta_data($socket);
		} while (!$info["timed_out"]);
		$this->last_rcon = time();
		if (strpos($response, "Bad rconpassword.") === 0) {
			$this->error_code = INVALID_RCON;
			return false;
		} else {
			$this->error_code = false;
		}
		$this->server_response = $response;
		return true;
	} // end rcon_cmd

	public function rcon_buffer() {
		while ((time() - $this->last_rcon) < 1 ) {
			usleep(100000);
		}
	} // end rcon_buffer

	public function build_rcon_cmd($rcmd, $slot = null, $reason = null) {

		if ($rcmd == "urtstatus") {
			return $this->buf . "rcon " . $this->rcon . " urtstatus";
		}
		if ($rcmd == "alphastatus") {
			return $this->buf . "rcon " . $this->rcon . " alphastatus";
		}
		if ($rcmd == "status") {
			return $this->buf . "rcon " . $this->rcon . " status";
		}
		if ($rcmd == "kick") {
			if ($this->build == "alpha") {
				return $this->buf . "rcon " . $this->rcon . " kick $slot " . '"' . $reason . '"';
			}
			if ($this->build == "stock") {
				return $this->buf . "rcon " . $this->rcon . " kick $slot";
			}
		}
	} // end build_rcon_cmd

	public function get_teams() {

		if ($this->build == "stock") {

			 $red = $this->cvar("g_redteamlist");
                $blue = $this->cvar("g_blueteamlist");

                $teams = array ( "red" => array(), "blue" => array());

                for ($i = 0; $i < strlen ($red[1]); $i++)
                {
                        $teams["red"][] = (ord($red[1]{$i})-65);
                }
                for ($i = 0; $i < strlen ($blue[1]); $i++)
                {
                        $teams["blue"][] = (ord($blue[1]{$i})-65);
                }
                return $teams;

		}
		if ($this->build == "alpha") {

		}
		$this->blue = 4;
		$this->red = 5;
		$this->specs = 6;
	} // end get_teams

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

	public function get_server_setting($setting) {
		$this->db->table = $this->db->prefix . "server_settings";
		$this->db->query_type = "SELECT";
		$this->db->fields[0] = "`value`";
		$this->db->criteria_fields[0] = "`setting`";
		$this->db->criteria_values[0] = "'" . mysql_escape_string($this->db->normalize_string($setting)) . "'";
		$this->db->criteria_type[0] = "=";
		$this->db->stats_query();
		if ($this->db->row_count() == 0) {
			return false;
		} else {
			$row = $this->db->get_row();
			return $row[0];
		}
	} // end tag_enforcement

}

?>
