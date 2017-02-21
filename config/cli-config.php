<?php

use Doctrine\DBAL\Tools\Console\ConsoleRunner;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Yaml\Yaml;

function getConfigurationData()
{
    $configData = Yaml::parse(file_get_contents('config/database.yml'));
    $useConnect = getenv('ENV_EVENT_SOURCING');
    if (!$useConnect) {
        $useConnect = $configData['doctrine']['dbal']['default_connection'];
    }
    $connectionParams = $configData['doctrine']['dbal']['connections'][$useConnect];

    return $connectionParams;
}

$connection = DriverManager::getConnection(getConfigurationData(), new Configuration());

// You can append new commands to $commands array, if needed

return ConsoleRunner::createHelperSet($connection);
