<?php

namespace Milhojas\Library\EventSourcing\EventStore;

use Milhojas\Library\EventSourcing\EventStream\EventStream;
use Milhojas\Library\EventSourcing\DTO\EntityDTO;
use Milhojas\Library\EventSourcing\Exceptions as Exception;

/**
 * An event store stores event streams and allow us to recover the full stream for an entity.
 * Use an event based storage in repositories to create event based ones
 *
 * @package default
 */
abstract class EventStore{
	/**
	 * Load an stream of events, representing the history of an aggregate
	 *
	 * @param EntityDTO $entity 
	 * @return EventStream
	 * @author Francisco Iglesias Gómez
	 */
	abstract public function loadStream(EntityDTO $entity);
	
	
	/**
	 * Save an strema of events, representing recent changes to an aggregate
	 *
	 * @param EventStream $stream 
	 * @return void or ConflictingVersion Exception
	 * @author Francisco Iglesias Gómez
	 */
	abstract public function saveStream(EventStream $stream);
	
	/**
	 * Counts the number of events stored for the Entity
	 *
	 * @param EntityDTO $entity 
	 * @return integer
	 */
	abstract public function count(EntityDTO $entity);

	/**
	 * Compares aggregate's current version with the stored version. If thery are out os sync throws exception
	 *
	 * @param EntityDTO $entity 
	 * @return nothing or ConflictingVersion Exception
	 * @author Francisco Iglesias Gómez
	 */
	protected function checkVersion(EntityDTO $entity)
	{
		$newVersion = $entity->getVersion();
		$storedVersion = $this->getStoredVersion($entity);
		if ($newVersion <= $storedVersion) {
			throw new Exception\ConflictingVersion(sprintf('Stored version for %s found to be %s, trying to save version %s', $entity, $storedVersion, $newVersion), 1);
		}
	}
	
	/**
	 * Computes or obtains the max version number of the aggregate stored in the Event Store 
	 *
	 * @param EntityDTO $entity Tramsports data for entity
	 * @return integer
	 */
	abstract protected function getStoredVersion(EntityDTO $entity);
}

?>
