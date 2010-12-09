<?php

/****************************************

	parser.php

	Class Parser

*/

class Parser {

	// Properties
	public $log_date;
	public $log_time;
	public $log_epoch;
	public $log;

	// Methods
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
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 1);
					break;
				case "InitGame:":
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 2);
					break;
				case "Warmup:":
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 3);
					break;
				case "InitRound:":
					$this->stats->store_log_line($this->log->ltime, $this->log->ldate, $this->log->lepoch, $this->log->location, $this->log->last_line, 4);
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
