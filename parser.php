<?php

/****************************************
 *
 *	parser.php
 *
 *	Class Parser
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

class Parser {

	// Properties
	public $log_date;
	public $log_time;
	public $log_epoch;
	public $log;

	// Methods

	public function rcon_status($server_reply) {
		$s = $server_reply;
		$loc = strpos($s, "\n");
		do {
			$line = substr($s, 0, strpos($s, "\n"));
			if (strpos($s, "map:") === 0) {
				$mapinfo = explode(':', $line);
				$map = ltrim(rtrim($mapinfo[1]));
				echo "$map\n";
				$s = substr(strstr($s, "\n"), 1);
			} elseif (strpos($s, "num") === 0) {
				$s = substr(strstr($s, "\n"), 1);
			} elseif (strpos($s, "-") === 0) {
				$s = substr(strstr($s, "\n"), 1);
			} elseif (strpos($s, "\n") !== 0) {
				$slot = substr(ltrim($line), 0, strpos(ltrim($line), " "));
				$line = ltrim(substr(ltrim($line), strpos(ltrim($line), " ")));
				$score = substr(ltrim($line), 0, strpos(ltrim($line), " "));
				$line = ltrim(substr(ltrim($line), strpos(ltrim($line), " ")));
				$ping = substr(ltrim($line), 0, strpos(ltrim($line), " "));
				$line = ltrim(substr(ltrim($line), strpos(ltrim($line), " ")));
				$name = substr(ltrim($line), 0, strrpos(ltrim($line), '^7'));
				$line = ltrim(substr(ltrim($line), strrpos(ltrim($line), '^7') + 2));
				$lastmsg = substr(ltrim($line), 0, strpos(ltrim($line), " "));
				$line = ltrim(substr(ltrim($line), strpos(ltrim($line), " ")));
				$ip = substr(ltrim($line), 0, strpos(ltrim($line), " "));
				$line = ltrim(substr(ltrim($line), strpos(ltrim($line), " ")));
				$qport = substr(ltrim($line), 0, strpos(ltrim($line), " "));
				$line = ltrim(substr(ltrim($line), strpos(ltrim($line), " ")));
				$rate = $line;
			}
			$s = substr(strstr($s, "\n"), 1);
			$loc = strpos($s, "\n");
		} while ($loc > 0);

	} // end rcon_status

	public function rcon_dumpuser($server_reply) {

	} // end rcon_status

	public function rcon_alphastatus($server_reply) {

	} // end rcon_alphastatus

	public function rcon_urtstatus($server_reply) {

	} // end rcon_urtstatus

	public function get_action_info($line) {
		// Get the type of action and return an array containing the [0]action, [1]timestamp and [2]line with action removed
		if (strlen($line) == 0) {
			return false;
		}
		$new_line = array();
		$line = ltrim($line);
		$timestampend = strpos($line, ':');
		$timestampend += 3;
		$new_line[1] = substr($line, 0, $timestampend);
		$line = ltrim(substr($line, $timestampend));
		if ($line[1] == '-') {
			$new_line[0] = "delimiter";
			return $new_line;
		}
		$logactionend = strpos($line, ' ');
		$new_line[0] = substr($line, 0, $logactionend);
		$new_line[2] = ltrim(substr($line, $logactionend));
		return $new_line;
	}

	public function parse_new_lines() {
		// Parse new lines in log file
		while ($line = $this->log->get_line()) {
			// Sleep for a short while to allow new lines to write to disk
			usleep(10000);
			$this->log->lepoch = time();
			$this->log->ldate = date('Y-m-d', $this->log->lepoch);
			$this->log->ltime = date('H:i:s', $this->log->lepoch);
			$line = $this->get_action_info($line);
			switch($line[0]) {
				case "delimiter":
					$this->log->last_processed_delimeter = $this->log->last_line;

					break;
				case "InitGame:":
					//$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 2);
					break;
				case "Warmup:":
					//$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 3);
					break;
				case "InitRound:":
					//$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 4);
					break;
				case "ClientConnect:":
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 5);
					break;
				case "ClientUserinfo:":
					$this->stats->process_clientuserinfo($line[2]);
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 6);
					break;
				case "ClientUserinfoChanged:":
					$this->stats->process_clientuserinfochanged($line[2]);
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 7);
					break;
				case "ClientBegin:":
					$this->stats->process_clientbegin($line[2]);
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 8);
					break;
				case "ClientDisconnect:":
					$this->stats->process_clientdisconnect($line[2]);
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 9);
					break;
				case "Kill:":
					$this->stats->process_kill($line[2]);
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 10);
					break;

			}
		}
	}

}
