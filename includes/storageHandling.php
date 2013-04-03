<?php
	class StorageHandling {
		public static function createDirectory($module, $name) {
			$mname = $module->name;
			$file = __PROJECTROOT__."/moddata/".$mname."/".$name;
			
			Logger::debug("Preparing to create directory at ".$file);
			if (self::initDirectories($mname, $name)) {
				Logger::debug("Directories are initialized.");
				if (substr(realpath($file), 0, strlen(__PROJECTROOT__)) == __PROJECTROOT__) {
					Logger::debug("Sandbox test passed.  Continuing check.");
					if (is_writable(dirname($file))) {
						Logger::debug("Creating directory now.");
						return mkdir($file);
					}
				}
				else {
					Logger::info("Module ".$module->name." tried writing outside of its sandbox.");
					Logger::debug(substr(realpath($file), 0, strlen(__PROJECTROOT__))." != ".__PROJECTROOT__);
				}
			}
			return false;
		}
		
		public static function loadFile($module, $name) {
			$mname = $module->name;
			$file = __PROJECTROOT__."/moddata/".$mname."/".$name;
			
			Logger::debug("Preparing to load file at ".$file);
			if (self::initDirectories($mname, $name)) {
				Logger::debug("Directories are initialized.");
				if (substr(realpath($file), 0, strlen(__PROJECTROOT__)) == __PROJECTROOT__) {
					Logger::debug("Sandbox test passed.  Continuing check.");
					if (is_readable($file)) {
						Logger::debug("Reading file now.");
						return file_get_contents($file);
					}
				}
				else {
					Logger::info("Module ".$module->name." tried reading outside of its sandbox.");
					Logger::debug(substr(realpath($file), 0, strlen(__PROJECTROOT__))." != ".__PROJECTROOT__);
				}
			}
			return false;
		}
		
		public static function saveFile($module, $name, $contents) {
			$mname = $module->name;
			$file = __PROJECTROOT__."/moddata/".$mname."/".$name;
			
			Logger::debug("Preparing to write to file at ".$file);
			if (self::initDirectories($mname, $name)) {
				Logger::debug("Directories are initialized.");
				if (substr(realpath($file), 0, strlen(__PROJECTROOT__)) == __PROJECTROOT__) {
					Logger::debug("Sandbox test passed.  Continuing check.");
					if (is_writable($file)) {
						Logger::debug("Writing to file now.");
						return file_put_contents($file, $contents);
					}
				}
				else {
					Logger::info("Module ".$module->name." tried writing outside of its sandbox.");
					Logger::debug(substr(realpath($file), 0, strlen(__PROJECTROOT__))." != ".__PROJECTROOT__);
				}
			}
			Logger::debug("Write failed.");
			return false;
		}
		
		private static function initDirectories($mname, $name = null) {
			$moddata = __PROJECTROOT__."/moddata";
			$moddir = $moddata."/".$mname;
			$file = $moddir."/".$name;
			
			if (!file_exists($moddata)) {
				$ret = mkdir($moddata);
				if ($ret == false) {
					Logger::debug("Could not create folder at ".$moddata);
					return false;
				}
			}
			
			if (!file_exists($moddir)) {
				$ret = mkdir($moddir);
				if ($ret == false) {
					Logger::debug("Could not create folder at ".$moddir);
					return false;
				}
			}
			
			if ($name != null && !file_exists($file)) {
				$ret = touch($file);
				if ($ret == false) {
					Logger::debug("Could not create file at ".$file);
					return false;
				}
			}
			
			return true;
		}
	}
?>