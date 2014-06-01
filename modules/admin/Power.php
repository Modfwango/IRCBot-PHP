<?php
  class @@CLASSNAME@@ {
    public $depend = array("ChannelMessageEvent", "UserIdentification");
    public $name = "Power";
    private $queue = array();

    public function receiveChannelMessage($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $message = $data[3];

      $ex = explode(" ", $message);
      if (preg_match("/^".$connection->getOption('nick').". restart/i", $message,
          $matches)) {
        $module = ModuleManagement::getModuleByName("UserIdentification");
        if (is_object($module)) {
          $this->queue[$module->testLogin($connection, $this,
            "userLoginCallback", $source[0])] = array($source[0], "restart");
        }
      }

      if (preg_match("/^".$connection->getOption('nick').". stop/i", $message,
          $matches)) {
        $module = ModuleManagement::getModuleByName("UserIdentification");
        if (is_object($module)) {
          $this->queue[$module->testLogin($connection, $this,
            "userLoginCallback", $source[0])] = array($source[0], "stop");
        }
      }
    }

    function userLoginCallback($connection, $id, $nick, $loggedin) {
      $entry = $this->queue[$id];
      if ($loggedin == true) {
        if ($entry[1] == "restart") {
          $this->restart();
        }

        if ($entry[1] == "stop") {
          die($this->stop());
        }
      }
      else {
        $connection->send("NOTICE ".$entry[0].
          " :You are not authorized to use this command.");
      }
    }

    public function restart() {
      $this->stop();
      die($this->start());
    }

    public function start() {
      exec("screen -dm php ".__PROJECTROOT__."/main.php");
    }

    public function stop() {
      foreach (ConnectionManagement::getConnections() as $connection) {
        $connection->send("QUIT :Shutting down...");
      }
      sleep(1);
      Logger::info("Shutting down...");
      return null;
    }

    public function isInstantiated() {
      EventHandling::registerForEvent("channelMessageEvent", $this,
        "receiveChannelMessage");
      return true;
    }
  }
?>
