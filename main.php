<?php
  class Main {
    public function __construct($argv) {
      // Verify that the bot can run in the provided environment.
      $this->verifyEnvironment();

      if (isset($argv[1])) {
        // Ensure that any non-default user input is converted to a boolean.
        $debug = (bool)$argv[1];
      }
      else {
        $debug = false;
      }

      // Activate full error reporting.
      $this->setErrorReporting();

      // Setup required constants for operation and load required classes.
      $this->prepareEnvironment($debug);

      // Load requested modules.
      $this->loadModules();

      // Discover connections located in conf/connections/.
      $this->discoverConnections();

      // Initiate all loaded connections.
      $this->activateConnections();

      // Start the main loop.
      $this->loop();

      // Return a false value if the loop fails.
      return false;
    }

    private function activateConnections() {
      // Iterate through the list of defined connections.
      foreach (ConnectionManagement::getConnections() as $connection) {
        // Connect.
        $connection->connect();
      }
    }

    private function discoverConnections() {
      // Get a list of connection configurations.
      $connections = glob(__PROJECTROOT__."/conf/connections/*");

      // Iterate through the list and load each item individually.
      foreach ($connections as $file) {
        // Parse the files using an ini parser.
        $connection = parse_ini_file($file, true);
        Logger::debug(str_ireplace("\n", " ", var_export($connection, true)));

        // Require these items to be defined.
        if (isset($connection['address']) && isset($connection['port'])) {
          // Define optional parameters to their default values.
          if (!isset($connection['ssl'])) {
            $connection['ssl'] = false;
          }

          if (!isset($connection['options'])
              || !is_array($connection['options'])) {
            $connection['options'] = array();
          }

          // Restrict possible data types for certain values.
          $connection['port'] = intval($connection['port']);
          $connection['ssl'] = (bool)$connection['ssl'];

          // Add the network to the connection manager.
          ConnectionManagement::newConnection(new Connection(
            $connection['address'], $connection['port'], $connection['ssl'],
            $connection['options']));
        }
        else {
          // Uh-oh!
          Logger::info("Connection in file \"".$file."\" failed to parse.");
        }
      }
    }

    private function loadModules() {
      // Load modules in requested order in conf/modules.conf.
      foreach (explode("\n",
          trim(file_get_contents(__PROJECTROOT__."/conf/modules.conf")))
          as $module) {
        $module = trim($module);
        if (strlen($module) > 0) {
          ModuleManagement::loadModule($module);
        }
      }
    }

    private function loop() {
      // Infinitely loop.
      while (true) {
        // Iterate through each connection.
        foreach (ConnectionManagement::getConnections() as $connection) {
          // Fetch any received data.
          $data = $connection->getData();
          if ($data != false) {
            // Pass the connection and associated data to the event handler.
            EventHandling::receiveData($connection, $data);
          }
          // Sleep for a small amount of time to prevent high CPU usage.
          usleep(10000);
        }

        // Iterate through each event to find the connectionLoopEndEvent event.
        foreach (EventHandling::getEvents() as $key => $event) {
          if ($key == "connectionLoopEndEvent") {
            foreach ($event[2] as $id => $registration) {
              // Trigger the connectionLoopEndEvent event for each registered
              // module.
              EventHandling::triggerEvent("connectionLoopEndEvent", $id);
            }
          }
        }
      }
    }

    private function prepareEnvironment($debug) {
      // Define the root of the project folder.
      define("__PROJECTROOT__", dirname(__FILE__));

      // Change current working directory to project root.
      chdir(__PROJECTROOT__);

      // Define start timestamp.
      define("__STARTTIME__", time());

      // Define the debug constant to allow the logger determine the correct
      // output type.
      define("__DEBUG__", $debug);

      // Load the connection related classes.
      require_once(__PROJECTROOT__."/includes/connection.php");
      require_once(__PROJECTROOT__."/includes/connectionManagement.php");

      // Load the event handler.
      require_once(__PROJECTROOT__."/includes/eventHandling.php");

      // Load the logger.
      require_once(__PROJECTROOT__."/includes/logger.php");

      // Load the module management class.
      require_once(__PROJECTROOT__."/includes/moduleManagement.php");

      // Load the storage handling class.
      require_once(__PROJECTROOT__."/includes/storageHandling.php");
    }

    private function setErrorReporting() {
      error_reporting(E_ALL);
      ini_set("display_errors", 1);
    }

    private function verifyEnvironment() {
      // Verify that the current directory structure is named safely.
      if (!preg_match("/^[a-zA-Z0-9\\/.\\-]+$/", dirname(__FILE__))) {
        die("The full path to this file must match this regular expression:\n^".
          "[a-zA-Z0-9\\/.\\-]+$\n");
      }
    }
  }

  $bot = new Main($argv);
?>
