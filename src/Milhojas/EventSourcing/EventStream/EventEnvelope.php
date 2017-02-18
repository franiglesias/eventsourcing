<?php

namespace Milhojas\EventSourcing\EventStream;

use Ramsey\Uuid\Uuid;
use Milhojas\EventSourcing\DTO\EventDTO;

/**
 * Contains metadata for event messages.
 */
class EventEnvelope
{
    private $id;
    private $time;
    private $metadata;

    public function __construct($id, $time, $metadata)
    {
        $this->id = $id;
        $this->time = $time;
        $this->metadata = $metadata;
    }

    public static function now()
    {
        return new static(
            self::autoAssignIdentity(),
            new \DateTime(),
            array()
        );
    }

    public static function fromEventDTO(EventDTO $dto)
    {
        return new static(
            $dto->getId(),
            $dto->getTime(),
            $dto->getMetadata()
        );
    }

    private static function autoAssignIdentity()
    {
        return Uuid::uuid4()->toString();
    }

    public function addMetaData($key, $value = null)
    {
        $data = $key;
        if (!is_array($key)) {
            $data = array($key => $value);
        }
        $this->metadata += $data;
    }

    public function getMessageId()
    {
        return $this->id;
    }
    public function getTime()
    {
        return $this->time;
    }
    public function getMetaData()
    {
        return $this->metadata;
    }
}
