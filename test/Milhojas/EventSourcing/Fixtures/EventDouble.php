<?php

namespace Tests\Library\EventSourcing\Fixtures;

use Milhojas\Messaging\EventBus\Event;

/**
* Description
*/
class EventDouble implements Event
{
	private $id;
	
	public function __construct($id)
	{
		$this->id = $id;
	}
	public function getId()
	{
		return $this->id;
	}
	
	public function getName()
	{
		return 'event_double';
	}
}


?>
