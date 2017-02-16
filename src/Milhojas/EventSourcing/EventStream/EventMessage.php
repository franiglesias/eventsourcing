<?php

namespace Milhojas\Library\EventSourcing\EventStream;

use Milhojas\Messaging\EventBus\Event;
use Milhojas\Library\EventSourcing\DTO\EntityDTO;
use Milhojas\Library\EventSourcing\DTO\EventDTO;

/**
* Stores an event and metadata needed
*/

class EventMessage
{
	private $event;
	private $envelope;
	private $entity;
	
	public function __construct(Event $event, EntityDTO $entity, EventEnvelope $envelope)
	{
		$this->event = $event;
		$this->entity = $entity;
		$this->envelope = $envelope;
	}
	
	static public function record(Event $event, EntityDTO $entity)
	{
		return new static(
			$event, 
			$entity,
			EventEnvelope::now()
		);
	}
	
	static public function fromEventDTO(EventDTO $dto)
	{
		return new static(
			$dto->getEvent(),
			EntityDTO::fromEventDTO($dto),
			EventEnvelope::fromEventDTO($dto)
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

?>
