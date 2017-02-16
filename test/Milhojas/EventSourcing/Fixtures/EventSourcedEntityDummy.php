<?php

namespace Tests\Library\EventSourcing\Fixtures;

use Milhojas\Messaging\EventBus\Event;
use Milhojas\Library\ValueObjects\Identity\Id;

use Milhojas\Library\EventSourcing\Domain\EventSourcedEntity;

class EventSourcedEntityDummy extends EventSourcedEntity {
	
	private $id;
	private $counter;
	private $value;
	
	public function __construct()
	{
		$this->counter = 0;
		$this->value = 0;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getValue()
	{
		return $this->value;
	}
	
	public function applyFailed()
	{
		throw new \InvalidArgumentException("Error Processing Request", 1);
	}
	
	public function getCounter()
	{
		return $this->counter;
	}
	
	public function recordThat(Event $event)
	{
		parent::recordThat($event);
	}
	
	public function applyEventDouble(Event $event)
	{
	}
	
	public function applyCreationEvent(Event $event)
	{
		$this->id = $event->getId();
	}

	public function applyModificationEvent(Event $event)
	{
		$this->value = $event->getValue();
	}
}


?>
