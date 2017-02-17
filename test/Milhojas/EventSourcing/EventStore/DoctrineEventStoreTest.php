<?php

namespace Test\EventSourcing\EventStore;

use Milhojas\EventSourcing\EventStore\DoctrineEventStore;
use Milhojas\EventSourcing\DTO\EntityDTO;
use Milhojas\EventSourcing\EventStream\EventMessage;
use Milhojas\EventSourcing\EventStream\EventEnvelope;
use Milhojas\EventSourcing\EventStream\EventStream;
use Test\EventSourcing\Fixtures\EventDouble;
use Test\EventSourcing\EventStore\Fixtures\DoctrineTestCase;

class DoctrineEventStoreTest extends DoctrineTestCase
{
    /**
     * Set up repository test.
     */
    public function setUp()
    {
        parent::setup();
        $this->loadFixturesFromDirectory(__DIR__.'/Fixtures');
        $this->em->clear();
    }

    public function test_it_loads_stream_with_3_events()
    {
        $storage = new DoctrineEventStore($this->em);
        $result = $storage->loadStream(new EntityDTO('Entity', 1));
        $this->assertEquals(3, $result->count());
    }

    /**
     * @expectedException \Milhojas\EventSourcing\Exceptions\EntityNotFound
     */
    public function test_throw_exception_if_there_is_no_info_for_entity()
    {
        $storage = new DoctrineEventStore($this->em);
        $result = $storage->loadStream(new EntityDTO('Entity', 5));
    }

    public function test_it_loads_other_stream_with_4_events()
    {
        $storage = new DoctrineEventStore($this->em);
        $result = $storage->loadStream(new EntityDTO('Other', 1));
        $this->assertEquals(4, $result->count());
    }

    public function test_it_loads_entity_2_stream_with_6_events()
    {
        $storage = new DoctrineEventStore($this->em);
        $result = $storage->loadStream(new EntityDTO('Entity', 2));
        $this->assertEquals(6, $result->count());
    }

    public function test_it_can_count_enitites_stored()
    {
        $storage = new DoctrineEventStore($this->em);
        $this->assertEquals(2, $storage->countEntitiesOfType('Entity'));
        $this->assertEquals(1, $storage->countEntitiesOfType('Other'));
    }

    public function test_it_can_count_events_for_an_entity()
    {
        $storage = new DoctrineEventStore($this->em);
        $this->assertEquals(3, $storage->count(new EntityDTO('Entity', 1, 0)));
        $this->assertEquals(4, $storage->count(new EntityDTO('Other', 1, 0)));
        $this->assertEquals(6, $storage->count(new EntityDTO('Entity', 2, 0)));
    }

    public function test_it_can_store_a_stream()
    {
        $storage = new DoctrineEventStore($this->em);
        $stream = $this->prepareEventStream('Entity', 3, 5);
        $storage->saveStream($stream);
        $this->assertEquals(3, $storage->countEntitiesOfType('Entity'));
        $this->assertEquals(5, $storage->count(new EntityDTO('Entity', 3, 0)));
    }

    public function test_it_can_save_a_new_stream()
    {
        $storage = new DoctrineEventStore($this->em);
        $stream = $this->prepareEventStream('Model', 1, 1);
        $storage->saveStream($stream);
        $loaded = $storage->loadStream(new EntityDTO('Model', 1));
        $this->assertEquals($stream, $loaded);
    }

    /**
     * @expectedException \Milhojas\EventSourcing\Exceptions\ConflictingVersion
     */
    public function test_it_detects_a_conflicting_version()
    {
        $storage = new DoctrineEventStore($this->em);
        $stream = $this->prepareEventStream('Entity', 2, 3);
        $storage->saveStream($stream);
    }

    public function test_it_can_save_a_stream_and_load_it_and_remains_equal()
    {
        $storage = new DoctrineEventStore($this->em);
        $stream = $this->prepareEventStream('Entity', 3, 20);
        $storage->saveStream($stream);
        $this->em->clear();
        $loaded = $storage->loadStream(new EntityDTO('Entity', 3));
        $this->em->clear();
        $this->assertEquals($stream, $loaded);
    }
    private function prepareEventStream($entity, $id, $maxVersion)
    {
        $stream = new EventStream();
        for ($version = 1; $version <= $maxVersion; ++$version) {
            $message = new EventMessage(new EventDouble($id), new EntityDTO($entity, $id, $version), EventEnvelope::now());
            $stream->recordThat($message);
        }

        return $stream;
    }
}
