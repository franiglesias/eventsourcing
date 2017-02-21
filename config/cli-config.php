<?php

use Doctrine\DBAL\Tools\Console\ConsoleRunner;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Milhojas\EventSourcing\Utility\ConfigManager;

$manager = new ConfigManager('config/database.yml');
$useConnect = getenv('ENV_EVENT_SOURCING');
if (!$useConnect) {
    $useConnect = $configData['doctrine']['dbal']['default_connection'];
}

$connection = DriverManager::getConnection($manager->getConfiguration($useConnect), new Configuration());

// You can append new commands to $commands array, if needed

return ConsoleRunner::createHelperSet($connection);
