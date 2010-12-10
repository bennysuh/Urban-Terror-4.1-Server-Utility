<?php

/****************************************
 *
 *	player.php
 *
 *	Class Player
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

class Player {

	// Properties

	public $tags; // array of clan tags found on player
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
	public $activity_id; // id of the player's activity row
	public $rank; // Last determined rank
	public $score; // Current score
	public $sr8_kill;
	public $g36_kill;
	public $lr300_kill;
	public $m4_kill;
	public $ak47_kill;
	public $negev_kill;
	public $ump_kill;
	public $he_kill;
	public $mp5k_kill;
	public $bleed_kill;
	public $de_kill;
	public $psg1_kill;
	public $hk_kill;
	public $baretta_kill;
	public $spas_kill;
	public $knife_kill;
	public $flyinghk_kill;
	public $goomba_kill;
	public $flyingknife_kill;
	public $boot_kill;
	public $sr8_death;
	public $g36_death;
	public $lr300_death;
	public $m4_death;
	public $ak47_death;
	public $negev_death;
	public $ump_death;
	public $he_death;
	public $mp5k_death;
	public $bleed_death;
	public $de_death;
	public $psg1_death;
	public $hk_death;
	public $baretta_death;
	public $spas_death;
	public $knife_death;
	public $flyinghk_death;
	public $goomba_death;
	public $flyingknife_death;
	public $boot_death;
	public $slap_death;
	public $telefrag_death;
	public $nuke_death;
	public $bomb_death;
	public $flag_death;
	public $fall_death;
	public $drown_death;
	public $suicide_death;
	public $environmental_death;
	public $heself_death;
	public $hkself_death;
	public $splode_death;
	public $unknownself_death;
	public $sr8_damage_dealt;
	public $g36_damage_dealt;
	public $lr300_damage_dealt;
	public $m4_damage_dealt;
	public $ak47_damage_dealt;
	public $negev_damage_dealt;
	public $ump_damage_dealt;
	public $mp5k_damage_dealt;
	public $de_damage_dealt;
	public $psg1_damage_dealt;
	public $baretta_damage_dealt;
	public $knife_damage_dealt;
	public $flyingknife_damage_dealt;
	public $boot_damage_deal;
	public $spas_damage_dealt;
	public $sr8_damage_received;
	public $g36_damage_received;
	public $lr300_damage_received;
	public $m4_damage_received;
	public $ak47_damage_received;
	public $negev_damage_received;
	public $ump_damage_received;
	public $mp5k_damage_received;
	public $de_damage_received;
	public $psg1_damage_received;
	public $baretta_damage_received;
	public $knife_damage_received;
	public $flyingknife_damage_received;
	public $boot_damage_received;
	public $spas_damage_received;
	public $head_dealt;
	public $helmet_dealt;
	public $kevlar_dealt;
	public $body_dealt;
	public $legs_dealt;
	public $arms_dealt;
	public $head_received;
	public $helmet_received;
	public $kevlar_received;
	public $body_received;
	public $legs_received;
	public $arms_received;
	public $kills;
	public $deaths;
	public $flag_captures;
	public $flag_carrier_kills;
	public $flag_returns;
	public $flag_protections;
	public $flag_carrier_protections;
	public $bomb_diffuses;
	public $bomb_carrier_kills;

	// Methods
	
	public function __destruct(){
		unset($this->name);
		unset($this->names_id);
		unset($this->name_length);
		unset($this->ip);
		unset($this->ips_id);
		unset($this->cl_guid);
		unset($this->guids_id);
		unset($this->gear);
		unset($this->current_team);
		unset($this->assigned_team);
		unset($this->time_connected);
		unset($this->time_team_join);
		unset($this->names_id_last_bleed);
		unset($this->ips_id_last_bleed);
		unset($this->guids_id_last_bleed);
		unset($this->headshots_month); // Number of headshots this player has for the current month
		unset($this->headshots_game); // Number of headshots this player has for their current session
		unset($this->kills_month); // Number of kills this player has for the current month
		unset($this->kills_game); // Number of kills this player has for the current session
		unset($this->deaths_month); // Number of times this player has died this month
		unset($this->deaths_game); // Number of times this player has died during the current session
		unset($this->damage_month); // Amount of damage this player has dealt during the month
		unset($this->damage_game); // Amount of damage this player has dealt during the current session
		unset($this->points_month); // Total points for the month for this player
		unset($this->points_game); // Total points for this session
		unset($this->muted); // Flag to denote that a player has been muted
		unset($this->permissions_id); // admin_id
		unset($this->permissions_type); // Defined permission such as evo_admin, evo_member or guest_admin
		unset($this->password); // Temporary password used for registering an admin/member/guest admin
		unset($this->user_id); // User ID as defined in Joomla. Set by retrieving from permissions table
		unset($this->db); // Placeholder for
		unset($this->servers_id); // Server ID
		unset($this->tagged_up); // Is the player wearing a tag?
		unset($this->new_player); // flag to denote a new player
		unset($this->entered_game); // flag to denote a player has completed the connection process
		unset($this->stats_month); // id of the player's monthly stats row
		unset($this->stats_year); // id of the player's yearly stats row
		unset($this->stats_lifetime); // id of the player's lifetime stats row
		unset($this->activity_id); // id of the player's activity row
		unset($this->rank); // Last determined rank
		unset($this->score); // Current score
		unset($this->sr8_kill);
		unset($this->g36_kill);
		unset($this->lr300_kill);
		unset($this->m4_kill);
		unset($this->ak47_kill);
		unset($this->negev_kill);
		unset($this->ump_kill);
		unset($this->he_kill);
		unset($this->mp5k_kill);
		unset($this->bleed_kill);
		unset($this->de_kill);
		unset($this->psg1_kill);
		unset($this->hk_kill);
		unset($this->baretta_kill);
		unset($this->spas_kill);
		unset($this->knife_kill);
		unset($this->flyinghk_kill);
		unset($this->goomba_kill);
		unset($this->flyingknife_kill);
		unset($this->boot_kill);
		unset($this->sr8_death);
		unset($this->g36_death);
		unset($this->lr300_death);
		unset($this->m4_death);
		unset($this->ak47_death);
		unset($this->negev_death);
		unset($this->ump_death);
		unset($this->he_death);
		unset($this->mp5k_death);
		unset($this->bleed_death);
		unset($this->de_death);
		unset($this->psg1_death);
		unset($this->hk_death);
		unset($this->baretta_death);
		unset($this->spas_death);
		unset($this->knife_death);
		unset($this->flyinghk_death);
		unset($this->goomba_death);
		unset($this->flyingknife_death);
		unset($this->boot_death);
		unset($this->slap_death);
		unset($this->telefrag_death);
		unset($this->nuke_death);
		unset($this->bomb_death);
		unset($this->flag_death);
		unset($this->fall_death);
		unset($this->drown_death);
		unset($this->suicide_death);
		unset($this->environmental_death);
		unset($this->heself_death);
		unset($this->hkself_death);
		unset($this->splode_death);
		unset($this->unknownself_death);
		unset($this->sr8_damage_dealt);
		unset($this->g36_damage_dealt);
		unset($this->lr300_damage_dealt);
		unset($this->m4_damage_dealt);
		unset($this->ak47_damage_dealt);
		unset($this->negev_damage_dealt);
		unset($this->ump_damage_dealt);
		unset($this->mp5k_damage_dealt);
		unset($this->de_damage_dealt);
		unset($this->psg1_damage_dealt);
		unset($this->baretta_damage_dealt);
		unset($this->knife_damage_dealt);
		unset($this->flyingknife_damage_dealt);
		unset($this->boot_damage_deal);
		unset($this->spas_damage_dealt);
		unset($this->sr8_damage_received);
		unset($this->g36_damage_received);
		unset($this->lr300_damage_received);
		unset($this->m4_damage_received);
		unset($this->ak47_damage_received);
		unset($this->negev_damage_received);
		unset($this->ump_damage_received);
		unset($this->mp5k_damage_received);
		unset($this->de_damage_received);
		unset($this->psg1_damage_received);
		unset($this->baretta_damage_received);
		unset($this->knife_damage_received);
		unset($this->flyingknife_damage_received);
		unset($this->boot_damage_received);
		unset($this->spas_damage_received);
		unset($this->head_dealt);
		unset($this->helmet_dealt);
		unset($this->kevlar_dealt);
		unset($this->body_dealt);
		unset($this->legs_dealt);
		unset($this->arms_dealt);
		unset($this->head_received);
		unset($this->helmet_received);
		unset($this->kevlar_received);
		unset($this->body_received);
		unset($this->legs_received);
		unset($this->arms_received);
		unset($this->kills);
		unset($this->deaths);
		unset($this->flag_captures);
		unset($this->flag_carrier_kills);
		unset($this->flag_returns);
		unset($this->flag_protections);
		unset($this->flag_carrier_protections);
		unset($this->bomb_diffuses);
		unset($this->bomb_carrier_kills);
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
