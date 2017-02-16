<?php

namespace Tests\Library\EventSourcing\EventStore;

use Tests\Infrastructure\Persistence\Shared\DoctrineTestCase;
use Milhojas\Library\EventSourcing\EventStore\DoctrineEventStore;
use Milhojas\Library\EventSourcing\DTO\EntityDTO;
use Milhojas\Library\EventSourcing\EventStream\EventMessage;
use Milhojas\Library\EventSourcing\EventStream\EventEnvelope;
use Milhojas\Library\EventSourcing\EventStream\EventStream;

use Milhojas\Library\ValueObjects\Identity\Id;
use Milhojas\Messaging\EventBus\Event;

use Tests\Library\EventSourcing\Fixtures\EventDouble;


class DoctrineEventStoreTest extends DoctrineTestCase
{
    /**
     * Set up repository test
     */
    public function setUp()
    {
        $this->loadFixturesFromDirectory(__DIR__ . '/Fixtures');
    }

	public function test_it_loads_stream_with_3_events()
	{
		$storage = new DoctrineEventStore($this->em);
		$result = $storage->loadStream(new EntityDTO('Entity', new Id(1)));
		$this->assertEquals(3, $result->count());
	}
	
	/**
	 * @expectedException Milhojas\Library\EventSourcing\Exceptions\EntityNotFound
	 */
	public function test_throw_exception_if_there_is_no_info_for_entity()
	{
		$storage = new DoctrineEventStore($this->em);
		$result = $storage->loadStream(new EntityDTO('Entity', new Id(5)));
	}
	
	public function test_it_loads_other_stream_with_4_events()
	{
		$storage = new DoctrineEventStore($this->em);
		$result = $storage->loadStream(new EntityDTO('Other', new Id(1)));
		$this->assertEquals(4, $result->count());
	}

	public function test_it_loads_entity_2_stream_with_6_events()
	{
		$storage = new DoctrineEventStore($this->em);
		$result = $storage->loadStream(new EntityDTO('Entity', new Id(2)));
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
		$this->assertEquals(3, $storage->count(new EntityDTO('Entity', new Id(1), 0)));
		$this->assertEquals(4, $storage->count(new EntityDTO('Other', new Id(1), 0)));
		$this->assertEquals(6, $storage->count(new EntityDTO('Entity', new Id(2), 0)));
	}
	
	public function test_it_can_store_a_stream()
	{
		$storage = new DoctrineEventStore($this->em);
		$stream = $this->prepareEventStream('Entity', new Id(3), 5);
		$storage->saveStream($stream);
		$this->assertEquals(3, $storage->countEntitiesOfType('Entity'));
		$this->assertEquals(5, $storage->count(new EntityDTO('Entity', new Id(3), 0)));
	}

	public function test_it_can_save_a_stream_and_load_it_and_remains_equal()
	{
		$storage = new DoctrineEventStore($this->em);
		$stream = $this->prepareEventStream('Entity', new Id(3), 5);
		$storage->saveStream($stream);
		$loaded = $storage->loadStream(new EntityDTO('Entity', new Id(3)));
		$this->assertEquals($stream, $loaded);
	}
	
	public function test_it_can_save_a_new_stream()
	{
		$storage = new DoctrineEventStore($this->em);
		$stream = $this->prepareEventStream('Model', new Id(1), 1);
		$storage->saveStream($stream);
		$loaded = $storage->loadStream(new EntityDTO('Model', new Id(1)));
		$this->assertEquals($stream, $loaded);
	}
	
	/**
	 * @expectedException Milhojas\Library\EventSourcing\Exceptions\ConflictingVersion
	 */
	public function test_it_detects_a_conflicting_version()
	{
		$storage = new DoctrineEventStore($this->em);
		$stream = $this->prepareEventStream('Entity', new Id(2), 3);
		$storage->saveStream($stream);
	}
	
	
	private function prepareEventStream($entity, $id, $maxVersion)
	{
		$stream = new EventStream();
		for ($version=1; $version <= $maxVersion; $version++) { 
			$message = new EventMessage(new EventDouble($id), new EntityDTO($entity, $id, $version), EventEnvelope::now());
			$stream->recordThat($message);
		}
		return $stream;
	}

}

?>
