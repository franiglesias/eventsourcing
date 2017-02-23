<?php

namespace Milhojas\EventSourcing\Utility;

use Symfony\Component\Yaml\Yaml;
use Psr\Log\LoggerInterface;

class ConfigManager
{
    private $data;
    private $file;
    private $defaults = [
        'app/config/database.yml',
        'config/database.yml',
        'config/config.yml',
        'database.yml',
        'config.yml',
    ];
    private $logger;

    public function __construct(LoggerInterface $logger, $file = null)
    {
        $this->logger = $logger;
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

        if (!$this->file) {
            $this->file = $this->getDefaultConfigurationFile();
        }
        $this->logger->notice(sprintf('Eventsourcing configuration will be loaded from %s.', $this->file));

        $data = Yaml::parse(file_get_contents($this->file));
        $this->isValidConfiguration($data);
        $this->logger->notice(sprintf('Valid configuration data found in %s.', $this->file));
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
        $this->hasAtLeastOneConnection($data);
    }

    private function hasDoctrineKey($data)
    {
        if (!isset($data['doctrine'])) {
            throw new \InvalidArgumentException(sprintf('doctrine key not found at the root level of the file %s.', $this->file));
        }
    }

    private function hasDbalKey($data)
    {
        if (!isset($data['doctrine']['dbal'])) {
            throw new \InvalidArgumentException(sprintf('dbal key not found under doctrine key %s.', $this->file));
        }
    }

    public function hasAtLeastOneConnection($data)
    {
        if (!isset($data['doctrine']['dbal']['connections'])) {
            throw new \InvalidArgumentException(sprintf('It looks like there are not defined connections %s.', $this->file));
        }
    }

    private function getDefaultConfigurationFile()
    {
        foreach ($this->defaults as $file) {
            $file = getcwd().'/'.$file;
            if (file_exists($file)) {
                return $file;
            }
        }
        throw new \InvalidArgumentException(sprintf('Need a configuration file such as: %s'.PHP_EOL, implode(PHP_EOL.' ', $this->defaults)));
    }

    public function setDefaultConfigFiles(array $files)
    {
        $this->defaults = $files;
    }
}
