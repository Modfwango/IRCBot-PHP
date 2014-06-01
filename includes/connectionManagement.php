<?php
  class ConnectionManagement {
    private static $connections = array();

    public static function newConnection($connection) {
      if (is_object($connection) && get_class($connection) == "Connection"
          && $connection->configured() == true) {
        self::$connections[] = $connection;
        Logger::info("Connection '".$connection->getConnectionString().
          "' added to the connection manager.");
        return true;
      }
      return false;
    }

    public static function getConnectionByHost($host) {
      $i = self::getConnectionIndexByHost($host);
      if ($i != false) {
        return self::getConnectionByIndex($i);
      }
      return false;
    }

    public static function getConnectionByIndex($i) {
      if (isset(self::$connections[$i])) {
        return self::$connections[$i];
      }
      return false;
    }

    public static function delConnectionByHost($host) {
      $i = self::getConnectionIndexByHost($host);
      if ($i != false) {
        return self::delConnectionByIndex($i);
      }
      return false;
    }

    public static function delConnectionByIndex($i) {
      if (isset(self::$connections[$i])) {self::$connections[$i]->disconnect();
        Logger::info("Connection '".
          self::$connections[$i]->getConnectionString().
          "' removed from the connection manager.");
        unset(self::$connections[$i]);
        return true;
      }
      return false;
    }

    public static funciton getConnectionIndexByHost($host) {
      foreach (self::$connections as $key => $connection) {
        if (strtolower(trim($connection->getHost()))
            == strtolower(trim($host))) {
          return $key;
        }
      }
      return false;
    }

    public static function getConnections() {
      return self::$connections;
    }
  }
?>
