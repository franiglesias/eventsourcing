<?php

namespace Milhojas\EventSourcing\Utility;

use Symfony\Component\Yaml\Yaml;

class ConfigManager
{
    private $data;
    private $file;
    private $defaults = [
        'config/database.yml',
        'config/config.yml',
    ];

    public function __construct($file = null)
    {
        if (!$file) {
            $file = $this->getDefaultConfigurationFile();
        }
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
        $this->isValidConfiguration($data);
        $this->data = $data['doctrine'];
    }

    private function isValidConnection($connection)
    {
        if (!in_array($connection, $this->getAvailableConnections())) {
            throw new \InvalidArgumentException(sprintf('Connection %s is not configured.', $connection));
        }
    }

    private function isValidConfiguration($data)
    {
        $this->hasDoctrineKey($data);
        $this->hasDbalKey($data);
    }

    private function hasDoctrineKey($data)
    {
        if (!isset($data['doctrine'])) {
            throw new \InvalidArgumentException('doctrine key not found at the root level of the file.');
        }
    }

    private function hasDbalKey($data)
    {
        if (!isset($data['doctrine']['dbal'])) {
            throw new \InvalidArgumentException('dbal key not found under doctrine key.');
        }
    }

    private function getDefaultConfigurationFile()
    {
        foreach ($this->defaults as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }
        throw new \InvalidArgumentException('Need a configuration file a config/database.yml or config/config.yml');
    }
}
