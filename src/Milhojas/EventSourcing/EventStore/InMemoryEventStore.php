<?php

namespace Milhojas\Library\EventSourcing\EventStore;

use Milhojas\Library\EventSourcing\EventStore\EventStore;
use Milhojas\Library\EventSourcing\DTO\EntityDTO;
use Milhojas\Library\EventSourcing\EventStream\EventStream;
use Milhojas\Library\EventSourcing\Exceptions as Exception;

/**
* A simple in memory event storage.
*/
class InMemoryEventStore extends EventStore
{
	/**
	 * Store the events
	 *
	 * @var array
	 * $events[$entity][$entity_id] = foreach eventStream
	 */
	private $events;
	
	public function __construct()
	{
		$this->events = array();
	}
	
	public function loadStream(EntityDTO $entity) 
	{
		if (! $this->thereAreEventsForEntity($entity)) {
			throw new Exception\EntityNotFound(sprintf('No events found for entity: %s', $entity->getType()), 2);
		}
		$events = $this->getEventsForEntity($entity);
		$stream = new EventStream();
		$stream->load($events);
		return $stream;
	}
	
	private function getEventsForEntity($entity)
	{
		if (!$entity->getVersion()) {
			return $this->events[$entity->getType()][$entity->getPlainId()];
		}
		$events = array();
		$lastVersion = $entity->getVersion();
		for ($i=0; $i < $lastVersion; $i++) { 
			$events[] = $this->events[$entity->getType()][$entity->getPlainId()][$i];
		}
		return $events;
	}
	
	public function saveStream(EventStream $stream)
	{
		foreach ($stream as $message) {
			$this->checkVersion($message->getEntity());
			$this->events[$message->getEntity()->getType()][$message->getEntity()->getPlainId()][] = $message;
		}
	}

	public function count(EntityDTO $entity)
	{
		if ($this->thereAreEventsForEntity($entity)) {
			return count($this->events[$entity->getType()][$entity->getPlainId()]);
		}
		return 0;
	}
	
	public function countEntitiesOfType($type)
	{
		if (isset($this->events[$type])) {
			return count($this->events[$type]);
		}
		return 0;
	}

	protected function thereAreEventsForEntity($entity)
	{
		return isset($this->events[$entity->getType()][$entity->getPlainId()]);
	}

	protected function getStoredVersion(EntityDTO $entity)
	{
		return $this->count($entity);
	}
	
	public function getEventStream()
	{
		return $this->events;
	}
	
}

?>
