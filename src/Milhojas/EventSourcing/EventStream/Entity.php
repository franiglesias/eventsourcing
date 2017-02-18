<?php

namespace Milhojas\EventSourcing\EventStream;

use Milhojas\EventSourcing\Domain\EventSourced;

/**
 * Transports information about entity type, id, and version.
 */
class Entity
{
    /**
     * @var int
     */
    private $version;
    /**
     * @var string
     */
    private $type;
    /**
     * @var mixed
     */
    private $id;

    public function __construct($type, $id, $version = null)
    {
        $this->type = $type;
        $this->id = $id;
        $this->version = $version;
    }

    public static function fromEntity(EventSourced $entity)
    {
        return new static(get_class($entity), $entity->getId(), $entity->getVersion());
    }

    public function getType()
    {
        return $this->type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPlainId()
    {
        return $this->id;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getKey($unique = false)
    {
        return sprintf('%s:%s', $this->type, $this->id);
    }

    public function getVersionKey()
    {
        return sprintf('%s:%s:%s', $this->type, $this->id, $this->version);
    }

    public function __toString()
    {
        return $this->getKey();
    }
}
