<?php

namespace Test\EventSourcing\EventStore\Fixtures;

// https://vincent.composieux.fr/article/test-your-doctrine-repository-using-a-sqlite-database

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Common\ClassLoader;

/**
 * Class DoctrineTestCase.
 *
 * This is the base class to load doctrine fixtures using the symfony configuration
 */
class DBALTestCase extends \PHPUnit_Framework_TestCase
{
    protected $connection;
    protected $schema;

    public function setUp()
    {
        $classLoader = new ClassLoader('Doctrine', dirname(dirname(dirname(dirname(dirname(__DIR__))))).'/vendor/doctrine');
        $classLoader->register();
        $this->connection = $this->getConnection();
        $this->schema = $this->getSchema();
        $this->createDatabase();
    }

    public function tearDown()
    {
        //$this->destroyDatabase();
    }
    /**
     * Executes fixtures.
     *
     * @param \Doctrine\Common\DataFixtures\Loader $loader
     */
    protected function executeFixtures(Loader $loader)
    {
        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());
    }

    /**
     * Load and execute fixtures from a directory.
     *
     * @param string $directory
     */
    protected function loadFixturesFromDirectory($directory)
    {
        $loader = new Loader();
        $loader->loadFromDirectory($directory);
        $this->executeFixtures($loader);
    }

    public function createDatabase()
    {
        $queries = $this->schema->toSql($this->connection->getDatabasePlatform());
        $manager = $this->connection->getSchemaManager();

        if (!$manager->tablesExist('events')) {
            array_walk($queries, function ($query) {
                $this->connection->query($query);
            });
        }
    }

    public function destroyDatabase()
    {
        $queries = $this->schema->toDropSql($this->connection->getDatabasePlatform());
        $manager = $this->connection->getSchemaManager();

        if ($manager->tablesExist('events')) {
            array_walk($queries, function ($query) {
                $this->connection->query($query);
            });
        }
    }
    /**
     * @return Schema
     */
    public function getSchema()
    {
        $schema = new Schema();
        $table = $schema->createTable('events');
        $table->addColumn('id', 'string');
        $table->addColumn('event_type', 'string');
        $table->addColumn('event', 'object');
        $table->addColumn('timestamp', 'datetimetz');
        $table->addColumn('version', 'integer', array('unsigned' => true));
        $table->addColumn('entity_type', 'string');
        $table->addColumn('entity_id', 'string');
        $table->addColumn('metadata', 'array');
        $table->addIndex(['entity_type', 'entity_id']);

        $table->setPrimaryKey(array('id'));

        return $schema;
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
