<?php
  class @@CLASSNAME@@ {
    public $depend = array("ConnectionDisconnectedEvent", "RawEvent");
    public $name = "TelnetControl";
    private $auth = array();
    private $credentials = null;

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
            if (strtolower($ex[1]) == strtolower($this->credentials[0])
                && hash("sha256", $ex[2]) == $this->credentials[1]) {
              $this->auth[] = $connection->getOption("id");
              $connection->send("Login successful.");
            }
            else {
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
        $contents = "username ".hash("sha256", "password");
        StorageHandling::saveFile($this, "credentials.txt", $contents);
      }
      if (stristr($contents, "\n")) {
        $contents = explode("\n", $contents);
        $contents = trim($contents[0]);
      }
      $this->credentials = explode(" ", $contents);
      EventHandling::registerForEvent("connectionCreatedEvent", $this,
        "receiveConnectionCreated");
      EventHandling::registerForEvent("connectionDisconnectedEvent", $this,
        "receiveConnectionDisconnected");
      EventHandling::registerForEvent("rawEvent", $this, "receiveRaw");
      return true;
    }
  }
?>
