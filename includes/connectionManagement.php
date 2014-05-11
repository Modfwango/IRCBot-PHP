<?php
  class ConnectionManagement {
    private static $connections = array();

    public static function newConnection($connection) {
      if (is_object($connection) && get_class($connection) == "Connection"
          && $connection->configured() == true) {
        self::$connections[] = $connection;
        Logger::info("Network '".$connection->getNetworkName().
          "' added to the connection manager.");
        return true;
      }
      return false;
    }

    public static function getConnectionByNetworkName($name) {
      foreach (self::$connections as $connection) {
        if (strtolower(trim($name))
            == strtolower(trim($connection->getNetworkName()))) {
          return $connection;
        }
      }
      return false;
    }

    public static function delConnectionByNetworkName($name) {
      foreach (self::$connections as $key => $connection) {
        if (strtolower(trim($name))
            == strtolower(trim($connection->getNetworkName()))) {
          $connection->disconnect();
          unset(self::$connections[$key]);
          Logger::info("Network '".$connection->getNetworkName().
            "' removed from the connection manager.");
          return true;
        }
      }
      return false;
    }

    public static function getConnections() {
      return self::$connections;
    }
  }
?>
