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

        $this->configured = true;
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
      stream_set_blocking($this->socket, 0);
      return true;
    }

    public function disconnect() {
      Logger::debug("Disconnecting from '".$this->getConnectionString().".'");
      fclose($this->socket);
    }

    public function getChannels() {
      return implode(",", $this->channels);
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
  }
?>
