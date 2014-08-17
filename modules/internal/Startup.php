<?php
  class __CLASSNAME__ {
    public $depend = array("ConnectionConnectedEvent", "ConnectionCreatedEvent",
      "NumericEvent");
    public $name = "Startup";

    public function receiveNumericEvent($name, $data) {
      $connection = $data[0];
      $source = $data[1];
      $numeric = $data[2];
      $target = $data[3];
      $message = $data[4];

      if ($numeric == 001) {
        if ($connection->getOption('nspass') != null) {
          Logger::debug("Identifying to NickServ on '".
            $connection->getOption('netname').".'");

          #atheme
          $connection->send("PRIVMSG NickServ :identify ".
            $connection->getOption('nick')." ".
            $connection->getOption('nspass'));

          #anope
          $connection->send("PRIVMSG NickServ :identify ".
            $connection->getOption('nspass'));
        }
      }
      else {
        Logger::debug("Joining channels on '".$connection->getOption('netname').
          ".'");
        $connection->send("JOIN ".$connection->getOption('channels'));
      }
    }

    public function connectionConnectedEvent($name, $connection) {
      if ($connection->getOption('pass') != null) {
        Logger::debug("Sending server password to '".
          $connection->getOption('netname').".'");
        $connection->send("PASS ".$connection->getOption('pass'));
      }
      Logger::debug("Setting nickname '".$connection->getOption('nick').
        "' on '".$connection->getOption('netname').".'");
      $connection->send("NICK ".$connection->getOption('nick'));
      Logger::debug("Setting username '".$connection->getOption('user').
        "' and realname '".$connection->getOption('realname')."' on '".
        $connection->getOption('netname').".'");
      $connection->send("USER ".$connection->getOption('user')." * * :".
        $connection->getOption('realname'));
    }

    public function connectionCreatedEvent($name, $connection) {
      Logger::debug($connection->getOption('netname'));
      Logger::debug($connection->getOption('nick'));
      Logger::debug($connection->getOption('user'));
      Logger::debug($connection->getOption('realname'));

      if (!is_string($connection->getOption('netname'))
          || !is_string($connection->getOption('nick'))
          || !is_string($connection->getOption('user'))
          || !is_string($connection->getOption('realname'))) {
        return false;
      }

      foreach (ConnectionManagement::getConnections() as $conn) {
        if (strtolower(trim($conn->getOption('netname')))
            == strtolower(trim($connection->getOption('netname')))) {
          return false;
        }
      }

      if ($connection->getOption('pass') == false) {
        $connection->setOption('pass', null);
      }

      if ($connection->getOption('channels') == false) {
        $connection->setOption('channels', null);
      }

      if ($connection->getOption('nspass') == false) {
        $connection->setOption('nspass', null);
      }

      return true;
    }

    public function isInstantiated() {
      EventHandling::registerForEvent("connectionConnectedEvent", $this,
        "connectionConnectedEvent");
      EventHandling::registerForEvent("connectionCreatedEvent", $this,
        "connectionCreatedEvent");
      EventHandling::registerForEvent("numericEvent", $this,
        "receiveNumericEvent", 001);
      EventHandling::registerForEvent("numericEvent", $this,
        "receiveNumericEvent", 376);
      EventHandling::registerForEvent("numericEvent", $this,
        "receiveNumericEvent", 422);
      return true;
    }
  }
?>
