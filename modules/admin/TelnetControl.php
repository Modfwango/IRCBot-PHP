<?php
  class __CLASSNAME__ {
    public $depend = array("ConnectionDisconnectedEvent", "RawEvent");
    public $name = "TelnetControl";
    private $auth = array();
    private $credentials = array();

    public function receiveConnectionCreated($name, $connection) {
      $connection->setOption("id", hash("sha256", rand().$connection->getIP()));
      return true;
    }

    public function receiveRaw($name, $data) {
      $connection = $data[0];
      $ex = $data[2];
      $data = $data[1];

      if (strval($connection->getType()) == "1") {
        if (!in_array($connection->getOption("id"), $this->auth)) {
          if (strtolower($ex[0]) == "login"
              && !in_array($connection->getOption("id"), $this->auth)
              && is_array($this->credentials)) {
            foreach ($this->credentials as $user) {
              if (strtolower($ex[1]) == strtolower($user[0])
                  && hash("sha256", $ex[2]) == $user[1]) {
                $this->auth[] = $connection->getOption("id");
                $connection->send("Login successful.");
              }
            }
            if (!in_array($connection->getOption("id"), $this->auth)) {
              $connection->send("Login failed.");
            }
          }
          else {
            $connection->send("Not logged in.");
          }
        }
        else {
          $eval = implode(" ", $ex);
          $output = explode("\n", trim(eval($eval)));
          foreach ($output as $line) {
            $connection->send($line);
          }
        }
      }
    }

    public function receiveConnectionDisconnected($name, $connection) {
      $this->auth = array_diff($this->auth,
        array($connection->getOption("id")));
      return true;
    }

    public function isInstantiated() {
      $contents = StorageHandling::loadFile($this, "credentials.txt");
      if ($contents == false) {
        $contents = "username ".hash("md5", "password");
        StorageHandling::saveFile($this, "credentials.txt", $contents);
        $contents = array(explode(" ", $contents));
      }
      elseif (stristr($contents, "\n")) {
        $contents = explode("\n", $contents);
        foreach ($contents as &$line) {
          $line = explode(" ", trim($line));
        }
      }
      else {
        $contents = array(explode(" ", trim($contents)));
      }
      $this->credentials = $contents;
      EventHandling::registerForEvent("connectionCreatedEvent", $this,
        "receiveConnectionCreated");
      EventHandling::registerForEvent("connectionDisconnectedEvent", $this,
        "receiveConnectionDisconnected");
      EventHandling::registerForEvent("rawEvent", $this, "receiveRaw");
      return true;
    }
  }
?>
