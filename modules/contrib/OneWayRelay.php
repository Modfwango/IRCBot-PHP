<?php
  class @@CLASSNAME@@ {
    public $depend = array("ChannelJoinEvent", "ChannelMessageEvent",
      "ChannelModeEvent", "ChannelNoticeEvent", "ChannelPartEvent",
      "ChannelQuitEvent", "ChannelTopicEvent", "PrivateMessageEvent",
      "PrivateNoticeEvent", "UserIdentification", "UserModeEvent");
    public $name = "OneWayRelay";
    private $destination = array();
    private $queue = array();
    private $relayConnections = array();

    private function addRelayChannel($detail) {
      $netname = $detail[0];
      $channel = $detail[1];

      if (!is_array($this->destination) || count($this->destination) < 2) {
        return false;
      }

      $connection = $this->getConnectionByNetworkName($netname);

      if ($connection != false
          && strtolower($netname) != strtolower($this->destination[0])
          && strtolower($channel) != strtolower($this->destination[1])) {
        foreach ($this->relayConnections as &$c) {
          if (strtolower($c[0]) == strtolower($netname)
              && !in_array(strtolower($channel), $c[8])) {
            $c[8][] = strtolower($channel);
          }
        }
        $this->flushConfig();
        $connection->send("JOIN ".$channel);
      }
    }

    private function addRelayConnection($detail) {
      if (!is_array($this->destination) || count($this->destination) < 2) {
        return false;
      }

      if (!is_string($detail[0]) || !(strlen($detail[0]) > 0)) {
        return false;
      }

      if (!is_string($detail[1]) || !(strlen($detail[1]) > 0)
          || gethostbyname($detail[1]) == $detail[1]) {
        return false;
      }

      if (!is_numeric($detail[2]) || $detail[2] > 65535 || $detail[2] < 1001) {
        return false;
      }

      if (!is_numeric($detail[3]) || !in_array(intval($detail[3]),
          array(0, 1))) {
        return false;
      }

      if (!is_string($detail[4]) || !(strlen($detail[4]) > 0)) {
        return false;
      }

      if (!is_string($detail[5]) || !(strlen($detail[5]) > 0)
          || ord($detail[5]) > 125 || ord($detail[5]) < 65) {
        return false;
      }

      if (!is_string($detail[6]) || !(strlen($detail[6]) > 0)
          || ord($detail[6]) > 125 || ord($detail[6]) < 65) {
        return false;
      }

      if (!is_string($detail[7]) || !(strlen($detail[7]) > 0)) {
        return false;
      }

      $netname = $detail[0];
      $address = $detail[1];
      $port = $detail[2];
      $ssl = (bool)$detail[3];
      $serverpass = ($detail[4] != "null" ? $detail[4] : null);
      $nick = $detail[5];
      $ident = $detail[6];
      $nspass = ($detail[7] != "null" ? $detail[7] : null);

      for ($i = 0; $i < 8; $i++) {
        unset($detail[$i]);
      }
      if (count($detail) > 0) {
        $realname = implode(" ", $detail);
      }
      else {
        $realname = null;
      }

      if ($this->getConnectionByNetworkName($netname) != false) {
        return false;
      }

      $this->relayConnections[] = array($netname, $address, $port, $ssl,
        $serverpass, $nick, $ident, $realname, array(), $nspass);
      $this->flushConfig();
      $c = new Connection($netname, $address, $port, $ssl, $serverpass, $nick,
        $ident, $realname, array(), $nspass);
      ConnectionManagement::newConnection($c);
      $c->connect();
      return true;
    }

    private function channelMatchesFilter($netname, $channel) {
      if (!isset($this->destination[0]) || !isset($this->destination[1])) {
        return false;
      }

      if (strtolower($netname) == strtolower($this->destination[0])
          && strtolower($channel) == strtolower($this->destination[1])) {
        return false;
      }

      if (count($this->relayConnections) > 0) {
        foreach ($this->relayConnections as $c) {
          if (strtolower($c[0]) == strtolower($netname)
              && (substr($channel, 0, 1) != "#"
                  || in_array(strtolower($channel), $c[8]))) {
            return true;
          }
        }
      }
      return false;
    }

    private function deliver($content) {
      if (!is_array($this->destination) || count($this->destination) < 2) {
        return false;
      }

      $connection = ConnectionManagement::
        getConnectionByNetworkName($this->destination[0]);
      if ($connection != false) {
        $connection->send("PRIVMSG ".$this->destination[1]." :".$content);
      }
    }

    private function delRelayChannel($detail) {
      $netname = $detail[0];
      $channel = $detail[1];

      $connection = $this->getConnectionByNetworkName($netname);

      if ($connection != false) {
        foreach ($this->relayConnections as &$c) {
          if (strtolower($c[0]) == strtolower($netname)
              && in_array(strtolower($channel), $c[8])) {
            $c[8] = array_diff($c[8], array(strtolower($channel)));
          }
        }
        $this->flushConfig();
        $connection->send("PART ".$channel);
        return true;
      }
      return false;
    }

    private function delRelayConnection($netname) {
      if ($this->getConnectionByNetworkName($netname) == false) {
        return false;
      }

      ConnectionManagement::delConnectionByNetworkName($netname);
      foreach ($this->relayConnections as $key => $entry) {
        if (strtolower($entry[0]) == strtolower($netname)) {
          unset($this->relayConnections[$key]);
        }
      }
      $this->flushConfig();
      return true;
    }

    private function flushConfig() {
      return StorageHandling::saveFile($this, "config.txt", serialize(
        array($this->destination, $this->relayConnections)));
    }

    private function getConnectionByNetworkName($name) {
      foreach (ConnectionManagement::getConnections() as $connection) {
        $netname = $connection->getOption("netname");
        if (is_string($netname) && strtolower(trim($netname))
            == strtolower(trim($name))) {
          return $connection;
        }
      }
      return false;
    }

    private function initMod() {
      if (!is_array($this->destination) || count($this->destination) < 2) {
        return false;
      }

      foreach ($this->relayConnections as $rc) {
        if (!$this->getConnectionByNetworkName($rc[0])) {
          $c = new Connection($rc[0], $rc[1], $rc[2], $rc[3], $rc[4], $rc[5],
            $rc[6], $rc[7], $rc[8], $rc[9]);
          ConnectionManagement::newConnection($c);
          $c->connect();
        }
      }
      $this->flushConfig();
    }

    public function receiveChannelJoin($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];

      if (!$this->channelMatchesFilter($connection->getOption('netname'),
          $target)) {
        return false;
      }

      if (substr($target, 0, 1) == ":") {
        $target = substr($target, 1);
      }

      $this->deliver("[".$connection->getOption('netname')." / ".$target."] * ".
        $source[0]."(".$source[1]."@".$source[2].") Join");
    }

    public function receiveChannelMessage($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $message = $data[3];

      if (!$this->channelMatchesFilter($connection->getOption('netname'),
          $target)) {
        return false;
      }

      if (preg_match("/ACTION (.*)/", $message, $matches)) {
        $message = $matches[1];
        $this->deliver("[".$connection->getOption('netname')." / ".$target."] * ".
          $source[0]." ".$message);
      }
      else {
        $this->deliver("[".$connection->getOption('netname')." / ".$target."] <".
          $source[0]."> ".$message);
      }
    }

    public function receiveChannelMode($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $modestring = $data[3];

      if (!$this->channelMatchesFilter($connection->getOption('netname'),
          $target)) {
        return false;
      }

      $this->deliver("[".$connection->getOption('netname')." / ".$target."] * ".
        $source[0]."(".$source[1]."@".$source[2].") set mode: ".$modestring);
    }

    public function receiveChannelNotice($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $message = $data[3];

      if (!$this->channelMatchesFilter($connection->getOption('netname'),
          $target)) {
        return false;
      }

      $this->deliver("[".$connection->getOption('netname')." / ".$target."] -".
        $source[0]."- ".$message);
    }

    public function receiveChannelPart($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $message = $data[3];

      if (!$this->channelMatchesFilter($connection->getOption('netname'),
          $target)) {
        return false;
      }

      if ($message != null) {
        $message = " (".$message.")";
      }

      $this->deliver("[".$connection->getOption('netname')." / ".$target."] * ".
        $source[0]."(".$source[1]."@".$source[2].") Part".$message);
    }

    public function receiveChannelQuit($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $message = $data[2];

      if (!$this->channelMatchesFilter($connection->getOption('netname'),
          $source[0])) {
        return false;
      }

      if ($message != null) {
        $message = " (".$message.")";
      }

      $this->deliver("[".$connection->getOption('netname')."] * ".$source[0]."(".
        $source[1]."@".$source[2].") Quit".$message);
    }

    public function receiveChannelTopic($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $topic = $data[3];

      if (!$this->channelMatchesFilter($connection->getOption('netname'),
          $target)) {
        return false;
      }

      $this->deliver("[".$connection->getOption('netname')."] * ".$source[0]."(".
        $source[1]."@".$source[2].") changed the topic to: '".$topic."'");
    }

    public function receivePrivateMessage($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $message = $data[3];

      if (preg_match("/^addRelayChannel(\\s(.*))?$/i", $message, $matches)) {
        $module = ModuleManagement::getModuleByName("UserIdentification");
        if (is_object($module) && stristr($matches[2], " ")) {
          $this->queue[$module->testLogin($connection, $this,
            "userLoginCallback", $source[0])] = array($source[0],
            "addRelayChannel", explode(" ", $matches[2]));
        }
      }
      elseif (preg_match("/^addRelayConnection(\\s(.*))?$/i", $message,
          $matches)) {
        $module = ModuleManagement::getModuleByName("UserIdentification");
        if (is_object($module) && stristr($matches[2], " ")) {
          $this->queue[$module->testLogin($connection, $this,
            "userLoginCallback", $source[0])] = array($source[0],
            "addRelayConnection", explode(" ", $matches[2]));
        }
      }
      elseif (preg_match("/^delRelayChannel(\\s(.*))?$/i", $message,
          $matches)) {
        $module = ModuleManagement::getModuleByName("UserIdentification");
        if (is_object($module) && stristr($matches[2], " ")) {
          $this->queue[$module->testLogin($connection, $this,
            "userLoginCallback", $source[0])] = array($source[0],
            "delRelayChannel", explode(" ", $matches[2]));
        }
      }
      elseif (preg_match("/^delRelayConnection(\\s(.*))?$/i", $message,
          $matches)) {
        $module = ModuleManagement::getModuleByName("UserIdentification");
        if (is_object($module)) {
          $this->queue[$module->testLogin($connection, $this,
            "userLoginCallback", $source[0])] = array($source[0],
            "delRelayConnection", $matches[2]);
        }
      }
      elseif (preg_match("/^setRelayDestination(\\s(.*))?$/i", $message,
          $matches)) {
        $module = ModuleManagement::getModuleByName("UserIdentification");
        if (is_object($module) && stristr($matches[2], " ")) {
          $this->queue[$module->testLogin($connection, $this,
            "userLoginCallback", $source[0])] = array($source[0],
            "setRelayDestination", explode(" ", $matches[2]));
        }
      }
      elseif ($this->channelMatchesFilter($connection->getOption('netname'),
          $target)) {
        if (preg_match("/ACTION (.*)/", $message, $matches)) {
          $message = $matches[1];
          $this->deliver("[".$connection->getOption('netname')." / PM] * ".
            $source[0]." ".$message);
        }
        else {
          $this->deliver("[".$connection->getOption('netname')." / PM] <".
            $source[0]."> ".$message);
        }
      }
    }

    public function receivePrivateNotice($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $message = $data[3];

      if (!$this->channelMatchesFilter($connection->getOption('netname'),
          $target)) {
        return false;
      }

      $this->deliver("[".$connection->getOption('netname')." / PM] -".$source[0].
        "- ".$message);
    }

    public function receiveUserMode($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $target = $data[2];
      $modestring = $data[3];

      if (!$this->channelMatchesFilter($connection->getOption('netname'),
          $target)) {
        return false;
      }

      $this->deliver("[".$connection->getOption('netname')." / ".$target."] * ".
        $source[0]." set mode: ".$modestring);
    }

    private function setRelayDestination($detail) {
      $netname = $detail[0];
      $channel = $detail[1];

      foreach ($this->relayConnections as $rc) {
        if (strtolower($rc[0]) == strtolower($netname)) {
          if (in_array(strtolower($channel), $rc[8])) {
            return false;
          }
        }
      }

      $connection = $this->getConnectionByNetworkName($netname);

      if ($connection != false) {
        if (is_array($this->destination) && count($this->destination) > 1) {
          ConnectionManagement::
            getConnectionByNetworkName($this->destination[0])->send("PART ".
            $this->destination[1]);
        }
        $connection->send("JOIN ".$channel);
        $this->destination = array($netname, strtolower($channel));
        $this->flushConfig();
        return true;
      }
      return false;
    }

    public function userLoginCallback($connection, $id, $nick, $loggedin) {
      $entry = $this->queue[$id];
      if ($loggedin == true) {
        if ($entry[1] == "addRelayConnection") {
          if (is_array($entry[2]) && count($entry[2]) >= 8) {
            $this->addRelayConnection($entry[2]);
          }
        }
        if ($entry[1] == "addRelayChannel") {
          if (is_array($entry[2]) && count($entry[2]) >= 2) {
            $this->addRelayChannel($entry[2]);
          }
        }
        if ($entry[1] == "delRelayConnection") {
          if (is_string($entry[2]) && strlen($entry[2]) > 0) {
            $this->delRelayConnection($entry[2]);
          }
        }
        if ($entry[1] == "delRelayChannel") {
          if (is_array($entry[2]) && count($entry[2]) >= 2) {
            $this->delRelayChannel($entry[2]);
          }
        }
        if ($entry[1] == "setRelayDestination") {
          if (is_array($entry[2]) && count($entry[2]) >= 2) {
            $this->setRelayDestination($entry[2]);
          }
        }
      }
    }

    public function isInstantiated() {
      $conf = @unserialize(StorageHandling::loadFile($this, "config.txt"));
      if (is_array($conf) && isset($conf[0]) && isset($conf[1])) {
        $this->destination = $conf[0];
        $this->relayConnections = $conf[1];
        $this->initMod();
      }
      else {
        $this->flushConfig();
      }

      EventHandling::registerForEvent("channelJoinEvent", $this,
        "receiveChannelJoin");
      EventHandling::registerForEvent("channelMessageEvent", $this,
        "receiveChannelMessage");
      EventHandling::registerForEvent("channelModeEvent", $this,
        "receiveChannelMode");
      EventHandling::registerForEvent("channelNoticeEvent", $this,
        "receiveChannelNotice");
      EventHandling::registerForEvent("channelPartEvent", $this,
        "receiveChannelPart");
      EventHandling::registerForEvent("channelQuitEvent", $this,
        "receiveChannelQuit");
      EventHandling::registerForEvent("channelTopicEvent", $this,
        "receiveChannelTopic");
      EventHandling::registerForEvent("privateMessageEvent", $this,
        "receivePrivateMessage");
      EventHandling::registerForEvent("privateNoticeEvent", $this,
        "receivePrivateNotice");
      EventHandling::registerForEvent("userModeEvent", $this,
        "receiveUserMode");
      return true;
    }
  }
?>
