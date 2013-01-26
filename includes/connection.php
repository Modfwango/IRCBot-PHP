<?php
	class Connection {
		private $configured = false;
		private $socket = null;
		private $netname = null;
		private $host = null;
		private $port = null;
		private $ssl = false;
		private $nickname = null;
		private $serverpass = null;
		private $ident = null;
		private $realname = null;
		private $channels = null;
		private $nspass = null;
		
		public function __construct($netname, $host, $port, $ssl, $serverpass, $nickname, $ident, $realname, $channels, $nspass = null) {
			if (is_string($netname) && is_string($host) && is_numeric($port) && is_bool($ssl) && is_string($nickname) && is_string($ident) && is_string($realname) && is_array($channels) && strlen($nspass) >= 0) {
				$this->netname = $netname;
				$this->host = $host;
				$this->port = $port;
				$this->ssl = $ssl;
				$this->serverpass = $serverpass;
				$this->nickname = $nickname;
				$this->ident = $ident;
				$this->realname = $realname;
				$this->channels = $channels;
				$this->nspass = $nspass;
				$this->configured = true;
			}
			return $this->configured;
		}
		
		public function configured() {
			return $this->configured;
		}
		
		public function connect() {
			if ($this->configured == true) {
				if ($this->ssl == true) {
					$this->socket = fsockopen("tls://".$this->host, $this->port);
				}
				else {
					$this->socket = fsockopen($this->host, $this->port);
				}
				stream_set_blocking($this->socket, 0);
				if ($this->serverpass != null) {
					$this->send("PASS ".$this->serverpass);
				}
				$this->send("NICK ".$this->nickname);
				$this->send("USER ".$this->ident." 8 * :".$this->realname);
			}
			return false;
		}
		
		public function disconnect() {
			$this->send("QUIT :Disconnecting...");
			fclose($this->socket);
		}
		
		public function getChannels() {
			return implode(",", $this->channels);
		}
		
		public function getData() {
			$data = trim(fgets($this->socket, 4096));
			if ($data != false && strlen($data) > 0) {
				return $data;
			}
			else {
				return false;
			}
		}
		
		public function getNetworkName() {
			return $this->netname;
		}
		
		public function identify() {
			if ($this->nspass != null) {
				$this->send("PRIVMSG NickServ :identify ".$this->nickname." ".$this->nspass);
			}
			$this->send("MODE ".$this->nickname." -x");
		}
		
		public function joinChannels() {
			$this->send("JOIN ".implode(",",$this->channels));
		}
		
		public function send($data) {
			fputs($this->socket, trim($data)."\n");
		}
	}
?>