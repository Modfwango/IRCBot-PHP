<?php
  class @@CLASSNAME@@ {
    public $depend = array("NumericEvent");
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
            $connection->getOption('nickname')." ".
            $connection->getOption('nspass'));

          #anope
          $connection->send("PRIVMSG NickServ :identify ".
            $connection->getOption('nspass'));
        }
        Logger::debug("Decloaking on '".$connection->getOption('netname').".'");
        $connection->send("MODE ".$connection->getOption('nickname')." -x");
      }
      else {
        Logger::debug("Joining channels on '".$connection->getOption('netname').
          ".'");
        $connection->send("JOIN ".$connection->getOption('channels'));
      }
    }

    public function connectionConnectedEvent($name, $connection) {
      if ($connection->getOption('serverpass') != null) {
        Logger::debug("Sending server password to '".
          $connection->getOption('netname').".'");
        $connection->send("PASS ".$connection->getOption('serverpass'));
      }
      Logger::debug("Setting nickname '".$connection->getOption('nickname').
        "' on '".$connection->getOption('netname').".'");
      $connection->send("NICK ".$connection->getOption('nickname'));
      Logger::debug("Setting username '".$connection->getOption('ident').
        "' and realname '".$connection->getOption('realname')."' on '".
        $connection->getOption('netname').".'");
      $connection->send("USER ".$connection->getOption('ident')." * * :".
        $connection->getOption('realname'));
    }

    public function connectionCreatedEvent($name, $connection) {
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
