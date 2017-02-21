<?php

namespace Milhojas\EventSourcing\Utility;

use Symfony\Component\Yaml\Yaml;

class ConfigManager
{
    private $data;
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
        $this->data = null;
    }

    public function getConfiguration($connection)
    {
        $this->load();
        $this->isValidConnection($connection);

        return $this->data['dbal']['connections'][$connection];
    }

    public function getAvailableConnections()
    {
        $this->load();

        return array_keys($this->data['dbal']['connections']);
    }

    private function load()
    {
        if ($this->data) {
            return;
        }

        $data = Yaml::parse(file_get_contents($this->file));
        $this->data = $data['doctrine'];
    }

    private function isValidConnection($connection)
    {
        if (!in_array($connection, $this->getAvailableConnections())) {
            throw new \InvalidArgumentException(sprintf('Connection %s is not configured.', $connection));
        }
    }
}
