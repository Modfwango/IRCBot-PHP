<?php
  class @@CLASSNAME@@ {
    public $depend = array("ChannelMessageEvent", "UserIdentification");
    public $name = "Eval";
    private $queue = array();

    public function receiveChannelMessage($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $message = $data[3];

      $ex = explode(" ", $message);
      if (preg_match("/^".$connection->getOption('nick').". eval (.+)/i",
          $message, $matches)) {
        $module = ModuleManagement::getModuleByName("UserIdentification");
        if (is_object($module)) {
          $this->queue[$module->testLogin($connection, $this,
            "userLoginCallback", $source[0])] = array($source[0], $target,
            $matches[1]);
        }
      }
    }

    function userLoginCallback($connection, $id, $nick, $loggedin) {
      $entry = $this->queue[$id];
      if ($loggedin == true) {
        $output = explode("\n", trim(eval($entry[2])));
        foreach ($output as $line) {
          $base = "PRIVMSG ".$entry[1]." :";
          $length = (511 - strlen($base));
          foreach (str_split($line, $length) as $outline) {
            $connection->send($base.$line);
            usleep(1000);
          }
        }
      }
      else {
        $connection->send("NOTICE ".$entry[0].
          " :You are not authorized to use this command.");
      }
    }

    public function isInstantiated() {
      EventHandling::registerForEvent("channelMessageEvent", $this,
        "receiveChannelMessage");
      return true;
    }
  }
?>
