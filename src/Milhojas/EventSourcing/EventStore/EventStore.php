<?php

namespace Milhojas\EventSourcing\EventStore;

use Milhojas\EventSourcing\EventStream\EventStream;
use Milhojas\EventSourcing\EventStream\Entity;
use Milhojas\EventSourcing\Exceptions as Exception;

/**
 * An event store stores event streams and allow us to recover the full stream for an entity.
 * Use an event based storage in repositories to create event based ones.
 */
abstract class EventStore
{
    /**
     * Load an stream of events, representing the history of an aggregate.
     *
     * @param Entity $entity
     *
     * @return EventStream
     *
     * @author Francisco Iglesias Gómez
     */
    abstract public function loadStream(Entity $entity);

    /**
     * Save an strema of events, representing recent changes to an aggregate.
     *
     * @param EventStream $stream
     *
     * @author Francisco Iglesias Gómez
     */
    abstract public function saveStream(EventStream $stream);

    /**
     * Counts the number of events stored for the Entity.
     *
     * @param Entity $entity
     *
     * @return int
     */
    abstract public function count(Entity $entity);

    /**
     * Compares aggregate's current version with the stored version. If they are out of sync throws exception.
     *
     * @param Entity $entity
     *
     * @author Francisco Iglesias Gómez
     */
    protected function checkVersion(Entity $entity)
    {
        $newVersion = $entity->getVersion();
        $storedVersion = $this->getStoredVersion($entity);
        if ($newVersion <= $storedVersion) {
            throw new Exception\ConflictingVersion(sprintf('Stored version for %s found to be %s, trying to save version %s', $entity, $storedVersion, $newVersion), 1);
        }
    }

    /**
     * Computes or obtains the max version number of the aggregate stored in the Event Store.
     *
     * @param Entity $entity Tramsports data for entity
     *
     * @return int
     */
    abstract protected function getStoredVersion(Entity $entity);
}
