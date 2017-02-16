<?php

namespace Milhojas\Library\EventSourcing\Domain;

use Milhojas\Messaging\EventBus\Event;
use Milhojas\Library\EventSourcing\DTO\EntityDTO;
use Milhojas\Library\EventSourcing\EventStream\EventStream;
use Milhojas\Library\EventSourcing\EventStream\EventMessage;

/**
 * Base class for Event Sourced Domain Entities.
 * EventSourced Entities should extend this class.
 */
abstract class EventSourcedEntity implements EventSourced
{
    protected $stream;
    protected $version = 0;

    abstract public function getId();

    /**
     * Recreates an instance of the Entity from a stream of events.
     *
     * @param EventStream $stream
     *
     * @return EventSourcedEntity
     */
    public static function reconstitute(EventStream $stream)
    {
        $entity = new static();
        foreach ($stream as $message) {
            $entity->apply($message->getEvent());
        }
        $entity->initStream(true);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Returns the stream of recorded message events.
     *
     * @return EventStream
     */
    public function getEventStream()
    {
        $this->initStream();

        return $this->stream;
    }

    /**
     * Returns array of recorded events.
     */
    public function getPlainEvents()
    {
        $this->initStream();

        return $this->stream->getEvents();
    }

    /**
     * Clears the stored list of events.
     *
     * @author Fran Iglesias
     */
    public function clearEvents()
    {
        $this->initStream();
        $this->stream->flush();
    }

    /**
     * Applies and record an event.
     *
     * @param Event $event
     *
     * @author Francisco Iglesias Gómez
     */
    protected function recordThat(Event $event)
    {
        if (!$this->canHandleEvent($event)) {
            return;
        }
        $this->apply($event);
        $this->initStream();
        $this->stream->recordThat(EventMessage::record($event, EntityDTO::fromEntity($this)));
    }

    /**
     * Applies an Event to the Entity updating the version number.
     *
     * @param Event $event
     *
     * @author Francisco Iglesias Gómez
     */
    protected function apply(Event $event)
    {
        $method = $this->getMethod($event);
        $this->$method($event);
        ++$this->version;
    }

    /**
     * Utility method to initizalize the EventStream when needed.
     *
     * @param bool $force a new Stream if stream exists
     */
    protected function initStream($force = false)
    {
        if (!$this->stream || $force) {
            $this->stream = new EventStream();
        }
    }

    protected function getMethod($event)
    {
        $parts = explode('\\', get_class($event));

        return 'apply'.end($parts);
    }

    protected function canHandleEvent(Event $event)
    {
        return method_exists($this, $this->getMethod($event));
    }
}
