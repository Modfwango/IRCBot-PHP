<?php
  class Connection {
    private $configured = false;
    private $socket = null;
    private $host = null;
    private $port = null;
    private $ssl = false;
    private $options = array();

    public function __construct($host, $port, $ssl, $options) {
      if (is_string($host) && is_numeric($port) && is_bool($ssl)
          && is_array($options)) {
        $this->host = $host;
        $this->port = $port;
        $this->ssl = $ssl;
        $this->options = $options;

        Logger::info("Connection to '".($this->ssl ? "tls://" : null).
          $this->host.":".$this->port."' created.");

        // Iterate through each event to find the connectionCreatedEvent
        // event.
        foreach (EventHandling::getEvents() as $key => $event) {
          if ($key == "connectionCreatedEvent") {
            foreach ($event[2] as $id => $registration) {
              // Trigger the connectionCreatedEvent event for each registered
              // module.
              if (EventHandling::triggerEvent("connectionCreatedEvent", $id,
                  $this)) {
                $this->configured = true;
              }
            }
          }
        }
      }
      return $this->configured;
    }

    public function configured() {
      return $this->configured;
    }

    public function connect() {
      Logger::debug("Attempting connection to '".
        $this->getConnectionString()."'");
      $this->socket = fsockopen(($this->ssl ? "tls://" : null).$this->host,
        $this->port);
      if (is_resource($this->socket)) {
        stream_set_blocking($this->socket, 0);

        // Iterate through each event to find the connectionConnectedEvent
        // event.
        foreach (EventHandling::getEvents() as $key => $event) {
          if ($key == "connectionConnectedEvent") {
            foreach ($event[2] as $id => $registration) {
              // Trigger the connectionConnectedEvent event for each registered
              // module.
              EventHandling::triggerEvent("connectionConnectedEvent", $id,
                $this);
            }
          }
        }

        return true;
      }
      return false;
    }

    public function disconnect() {
      Logger::debug("Disconnecting from '".$this->getConnectionString().".'");
      fclose($this->socket);
      // Iterate through each event to find the connectionConnectedEvent event.
      foreach (EventHandling::getEvents() as $key => $event) {
        if ($key == "connectionDisconnectedEvent") {
          foreach ($event[2] as $id => $registration) {
            // Trigger the connectionDisconnectedEvent event for each registered
            // module.
            EventHandling::triggerEvent("connectionDisconnectedEvent", $id,
              $this);
          }
        }
      }
      return true;
    }

    public function getConnectionString() {
      return ($this->ssl ? "tls://" : null).$this->host.":".$this->port;
    }

    public function getData() {
      $data = trim(fgets($this->socket, 4096));
      if ($data != false && strlen($data) > 0) {
        Logger::debug("Data received on '".$this->getConnectionString()."':  '".
          $data."'");
        return $data;
      }
      else {
        return false;
      }
    }

    public function getHost() {
      return $this->host;
    }

    public function getOption($key) {
      return (isset($this->options[$key]) ? $this->options[$key] : false);
    }

    public function getPort() {
      return $this->port;
    }

    public function getSSL() {
      return $this->ssl;
    }

    public function send($data) {
      Logger::debug("Sending data to '".$this->host."':  '".$data."'");
      fputs($this->socket, trim($data)."\n");
    }

    public function setOption($key, $value) {
      $this->options[$key] = $value;
      return true;
    }
  }
?>
