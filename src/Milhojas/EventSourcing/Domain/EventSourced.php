<?php

namespace Milhojas\EventSourcing\Domain;

/**
 * An entity that can be Event Sourced.
 *
 * @author Francisco Iglesias Gómez
 */
interface EventSourced
{
    /**
     * Return the stream of uncommitted events.
     *
     * @return EventStream object
     *
     * @author Fran Iglesias
     */
    public function getEventStream();

    /**
     * Return the identity of the entity.
     *
     * @return mixed identity
     *
     * @author Fran Iglesias
     */
    public function getId();

    /**
     * Returns the version number for the entity.
     *
     * -1: the entity has no events applied
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
