<?php

namespace Test\EventSourcing\EventStore\Fixtures;

// https://vincent.composieux.fr/article/test-your-doctrine-repository-using-a-sqlite-database

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Common\ClassLoader;
use Milhojas\EventSourcing\EventStore\DBALEventStore;

/**
 * Class DoctrineTestCase.
 *
 * This is the base class to load doctrine fixtures using the symfony configuration
 */
class DBALTestCase extends \PHPUnit_Framework_TestCase
{
    protected $connection;
    protected $schema;
    protected $storage;

    public function setUp()
    {
        // $classLoader = new ClassLoader('Doctrine', dirname(dirname(dirname(dirname(dirname(__DIR__))))).'/vendor/doctrine');
        // $classLoader->register();
        $this->connection = $this->getConnection();
        $this->storage = new DBALEventStore($this->connection, 'events');
        $this->storage->setUpStore();
    }

    public function tearDown()
    {
        $this->storage->tearDownStore();
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        $config = new Configuration();
        // mysql://root:root@localhost/testmilhojas?charset=UTF-8
        $connectionParams = [
            'driver' => 'pdo_mysql',
            'user' => 'root',
            'password' => 'root',
            'dbname' => 'testmilhojas',
            'host' => 'localhost',
        ];
        $connection = DriverManager::getConnection($connectionParams, $config);

        return $connection;
    }
}
