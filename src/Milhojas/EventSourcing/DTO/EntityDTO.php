<?php

namespace Milhojas\Library\EventSourcing\DTO;

use Milhojas\Library\EventSourcing\Domain\EventSourced;
use Milhojas\Library\EventSourcing\DTO\EventDTO;

use Milhojas\Library\ValueObjects\Identity\Id;

/**
* Transports information about entity type, id, and version
*/
class EntityDTO
{
	private $version;
public function __construct($type, Id $id, $version = null)
	{
		$this->type = $type;
		$this->id = $id;
		$this->version = $version;
	}
	
	static public function fromEntity(EventSourced $entity)
	{
		return new static(get_class($entity), $entity->getId(), $entity->getVersion());
	}
	
	static public function fromEventDTO(EventDTO $dto)
	{
		return new static($dto->getEntityType(), new Id($dto->getEntityId()), $dto->getVersion());
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
		return $this->id->getId();
	}
			
	public function getVersion()
	{
		return $this->version;
	}
	
	public function getKey($unique = false)
	{
		return sprintf('%s:%s', $this->type, $this->id->getId());
	}
	
	public function getVersionKey()
	{
		return sprintf('%s:%s:%s', $this->type, $this->id->getId(), $this->version);
	}
	
	public function __toString()
	{
		return $this->getKey();
	}
}
?>
