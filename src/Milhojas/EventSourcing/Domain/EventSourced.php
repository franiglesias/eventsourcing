<?php

namespace Milhojas\EventSourcing\Domain;

use Milhojas\EventSourcing\EventStream\EventStream;

/**
 * An entity that can be Event Sourced.
 */
interface EventSourced
{
    /**
     * Return the stream of uncommitted events.
     *
     * @return EventStream object
     */
    public function getEventStream();

    /**
     * Return the identity of the entity.
     *
     * @return mixed identity
     */
    public function getId();

    /**
     * Returns the version number for the entity.
     *
     *  0: initial version
     *
     * @return int
     *
     * @author Fran Iglesias
     */
    public function getVersion();

    /**
     * Clear remaining events.
     *
     * @author Francisco Iglesias Gómez
     */
    public function clearEvents();
}
