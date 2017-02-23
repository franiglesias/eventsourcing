<?php

namespace Test\EventSourcing\EventStore;

use Milhojas\EventSourcing\EventStream\Entity;
use Milhojas\EventSourcing\EventStream\EventMessage;
use Milhojas\EventSourcing\EventStream\EventStream;
use Milhojas\EventSourcing\EventStore\DBALEventStore;
use Milhojas\EventSourcing\Utility\ConfigManager;
use Test\EventSourcing\Fixtures\EventDouble;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;

class DBALEventStoreTest extends TestCase
{
    protected $connection;
    protected $schema;
    protected $storage;

    public function setUp()
    {
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
    protected function getConnection()
    {
        $config = new Configuration();
        $connection = DriverManager::getConnection($this->getConfigurationData(), $config);

        return $connection;
    }

    /**
     * @return mixed
     */
    private function getConfigurationData()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $manager = new ConfigManager($logger->reveal(), 'config/database.yml');
        $useConnect = getenv('ENV_EVENT_SOURCING');
        if (!$useConnect) {
            $useConnect = 'test';
        }

        return $manager->getConfiguration($useConnect);
    }

    public function test_it_can_store_a_stream()
    {
        $stream = $this->prepareEventStream('Entity', 3, 5);
        $this->storage->saveStream($stream);
        $this->assertEquals(1, $this->storage->countEntitiesOfType('Entity'));
        $this->assertEquals(5, $this->storage->count(new Entity('Entity', 3, 0)));
    }

    public function test_it_loads_stream_with_3_events()
    {
        $this->prepareFixturesEntity1();
        $result = $this->storage->loadStream(new Entity('Entity', 1));
        $this->assertEquals(3, $result->count());
    }

    /**
     * @expectedException \Milhojas\EventSourcing\Exceptions\EntityNotFound
     */
    public function test_throw_exception_if_there_is_no_info_for_entity()
    {
        $result = $this->storage->loadStream(new Entity('Entity', 5));
    }

    public function test_it_loads_other_stream_with_4_events()
    {
        $this->prepareFixturesOther1();
        $result = $this->storage->loadStream(new Entity('Other', 1));
        $this->assertEquals(4, $result->count());
    }

    public function test_it_loads_entity_2_stream_with_6_events()
    {
        $this->prepareFixturesEntity2();
        $result = $this->storage->loadStream(new Entity('Entity', 2));
        $this->assertEquals(6, $result->count());
    }

    public function test_it_can_count_enitites_stored()
    {
        $this->prepareFixturesOther1();
        $this->prepareFixturesEntity1();
        $this->prepareFixturesEntity2();
        $this->assertEquals(2, $this->storage->countEntitiesOfType('Entity'));
        $this->assertEquals(1, $this->storage->countEntitiesOfType('Other'));
    }

    public function test_it_can_count_events_for_an_entity()
    {
        $this->prepareFixturesOther1();
        $this->prepareFixturesEntity1();
        $this->prepareFixturesEntity2();
        $this->assertEquals(3, $this->storage->count(new Entity('Entity', 1, 0)));
        $this->assertEquals(4, $this->storage->count(new Entity('Other', 1, 0)));
        $this->assertEquals(6, $this->storage->count(new Entity('Entity', 2, 0)));
    }

    public function test_it_can_save_a_new_stream()
    {
        $stream = $this->prepareEventStream('Model', 1, 1);
        $this->storage->saveStream($stream);
        $loaded = $this->storage->loadStream(new Entity('Model', 1));
        $this->assertEquals($stream, $loaded);
    }

    /**
     * @expectedException \Milhojas\EventSourcing\Exceptions\ConflictingVersion
     */
    public function test_it_detects_a_conflicting_version()
    {
        $this->prepareFixturesEntity2();
        $stream = $this->prepareEventStream('Entity', 2, 3);
        $this->storage->saveStream($stream);
    }

    public function test_it_can_save_a_stream_and_load_it_and_remains_equal()
    {
        $stream = $this->prepareEventStream('Entity', 3, 20);
        $this->storage->saveStream($stream);
        $loaded = $this->storage->loadStream(new Entity('Entity', 3));
        $this->assertEquals($stream, $loaded);
    }
    private function prepareEventStream($entity, $id, $maxVersion)
    {
        $stream = new EventStream();
        for ($version = 1; $version <= $maxVersion; ++$version) {
            $message = EventMessage::record(new EventDouble($id), new Entity($entity, $id, $version));
            $stream->recordThat($message);
        }

        return $stream;
    }

    public function prepareFixturesEntity1()
    {
        $stream = $this->prepareEventStream('Entity', 1, 3);
        $this->storage->saveStream($stream);
    }

    public function prepareFixturesEntity2()
    {
        $stream = $this->prepareEventStream('Entity', 2, 6);
        $this->storage->saveStream($stream);
    }
    private function prepareFixturesOther1()
    {
        $stream = $this->prepareEventStream('Other', 1, 4);
        $this->storage->saveStream($stream);
    }
}
