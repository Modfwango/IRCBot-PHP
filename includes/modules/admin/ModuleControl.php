<?php
	class @@CLASSNAME@@ {
		public $name = "ModuleControl";
		private $queue = array();
		
		public function receiveChannelMessage($name, $data) {
			$connection = $data[0];
			$source = $data[1];
			$target = $data[2];
			$message = $data[3];
			
			$ex = explode(" ", $message);
			Logger::info($message);
			if (preg_match("/^".$connection->getNickname().". load (.+)/i", $message, $matches)) {
				$module = ModuleManagement::getModuleByName("UserIdentification");
				if (is_object($module)) {
					$this->queue[$module->testLogin($connection, $this, "userLoginCallback", $source[0])] = array($source[0], "load", $matches[1]);
				}
			}
			
			if (preg_match("/^".$connection->getNickname().". reload (.+)/i", $message, $matches)) {
				$module = ModuleManagement::getModuleByName("UserIdentification");
				if (is_object($module)) {
					$this->queue[$module->testLogin($connection, $this, "userLoginCallback", $source[0])] = array($source[0], "reload", $matches[1]);
				}
			}
			
			if (preg_match("/^".$connection->getNickname().". unload (.+)/i", $message, $matches)) {
				$module = ModuleManagement::getModuleByName("UserIdentification");
				if (is_object($module)) {
					$this->queue[$module->testLogin($connection, $this, "userLoginCallback", $source[0])] = array($source[0], "unload", $matches[1]);
				}
			}
		}
		
		function userLoginCallback($connection, $id, $nick, $loggedin) {
			$entry = $this->queue[$id];
			if ($loggedin == true) {
				if ($entry[1] == "load") {
					if (ModuleManagement::loadModule($entry[2])) {
						$connection->send("NOTICE ".$entry[0]." :\"".$entry[2]."\" has been loaded.");
					}
					else {
						$connection->send("NOTICE ".$entry[0]." :I was not able to load \"".$entry[2].".\"");
					}
				}
			
				if ($entry[1] == "reload") {
					if (ModuleManagement::reloadModule($entry[2])) {
						$connection->send("NOTICE ".$entry[0]." :\"".$entry[2]."\" has been reloaded.");
					}
					else {
						$connection->send("NOTICE ".$entry[0]." :I was not able to reload \"".$entry[2].".\"");
					}
				}
			
				if ($entry[1] == "unload") {
					if (ModuleManagement::unloadModule($entry[2])) {
						$connection->send("NOTICE ".$entry[0]." :\"".$entry[2]."\" has been unloaded.");
					}
					else {
						$connection->send("NOTICE ".$entry[0]." :I was not able to unload \"".$entry[2].".\"");
					}
				}
			}
			else {
				$connection->send("NOTICE ".$entry[0]." :You are not authorized to use this command.");
			}
		}
		
		public function isInstantiated() {
			EventHandling::registerForEvent("channelMessageEvent", $this, "receiveChannelMessage");
			return true;
		}
	}
?>