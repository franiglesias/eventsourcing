<?php

namespace Milhojas\EventSourcing\EventStream;

use Milhojas\Messaging\EventBus\Event;

/**
 * Stores an event and metadata needed.
 */
class EventMessage
{
    private $event;
    private $envelope;
    private $entity;

    public function __construct(Event $event, Entity $entity, EventEnvelope $envelope)
    {
        $this->event = $event;
        $this->entity = $entity;
        $this->envelope = $envelope;
    }

    public static function record(Event $event, Entity $entity)
    {
        return new static(
            $event,
            $entity,
            EventEnvelope::now()
        );
    }

    public static function fromDtoArray(array $dto)
    {
        return new static(
            unserialize($dto['event']),
            new Entity($dto['entity_type'], $dto['entity_id'], $dto['version']),
            new EventEnvelope(
                $dto['id'],
                new \DateTime($dto['timestamp']),
                unserialize($dto['metadata'])
                )
        );
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function getEnvelope()
    {
        return $this->envelope;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function addMetaData($key, $value = null)
    {
        $this->envelope->addMetaData($key, $value);
    }

    public function getMetaData()
    {
        return $this->envelope->getMetadata();
    }

    public function __toString()
    {
        return sprintf('%s with %s', get_class($this->event), $this->entity);
    }

    public function getId()
    {
        return $this->envelope->getMessageId();
    }
}
