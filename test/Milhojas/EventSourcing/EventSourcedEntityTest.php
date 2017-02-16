<?php

namespace Tests\Library\EventSourcing;

use Milhojas\Messaging\EventBus\Event;
use Milhojas\Library\ValueObjects\Identity\Id;
use Milhojas\Library\EventSourcing\Domain\EventSourcedEntity;

class TestESEntity extends EventSourcedEntity {
	
	private $id;
	private $counter;
	
	public function __construct()
	{
		$this->id = Id::create();
		$this->counter = 0;
	}
	public function getId()
	{
		return $this->id;
	}
	
	public function applyHandled()
	{
		$this->counter++;
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
}


class EventSourcedEntityTest extends \PHPUnit_Framework_TestCase {
	
	private $Entity;
	
	protected function start_with_a_new_entity()
	{
		$this->Entity = new TestESEntity();
	}
	
	protected function entity_has_not_recorded_events()
	{
		$this->assertEquals(0, $this->Entity->getEventStream()->count());
	}
	
	protected function entity_receives_an_unknown_event()
	{
		$this->Entity->recordThat($this->getEvent('NoHandled'));
	}
	
	protected function entity_receives_events($count = 1)
	{
		for ($i=0; $i < $count; $i++) { 
			$this->Entity->recordThat($this->getEvent('Handled'));
		}
	}
	
	protected function entity_should_have_recorded_events($expectedEventCount)
	{
		$this->assertEquals($expectedEventCount, $this->Entity->getEventStream()->count());
	}
	
	protected function entity_should_have_version_number_of($expectedVersionNumber)
	{
		$this->assertAttributeEquals($expectedVersionNumber, 'version', $this->Entity);
	}
	
	protected function entity_receives_a_failing_event()
	{
		$this->Entity->recordThat($this->getEvent('Failed'));
	}
	
	protected function entity_is_reconstituted()
	{
		$original = $this->Entity;
		unset($this->Entity);
		$this->Entity = TestESEntity::reconstitute($original->getEventStream());
	}
	
	protected function entity_clears_events()
	{
		$this->Entity->clearEvents();
	}

	protected function entity_has_changed_state_to($expectedState)
	{
		$this->assertEquals($expectedState, $this->Entity->getCounter());
	}
	
	protected function getEvent($name)
	{
		return $this->getMockBuilder('Milhojas\Messaging\EventBus\Event')
			->setMockClassName($name)->disableOriginalConstructor()
            ->getMock();
	}
	
		
	public function test_new_entity_has_no_recorded_events()
	{
		$this->start_with_a_new_entity();
		$this->entity_has_not_recorded_events();
		$this->entity_should_have_version_number_of(0);
	}
	
	public function test_it_applies_event()
	{
		$this->start_with_a_new_entity();
		$this->entity_receives_events(1);
		$this->entity_should_have_recorded_events(1);
		$this->entity_should_have_version_number_of(1);
	}
	
	public function test_it_does_not_apply_unknown_event()
	{
		$this->start_with_a_new_entity();
		$this->entity_receives_an_unknown_event();
		$this->entity_has_not_recorded_events();
		$this->entity_should_have_version_number_of(0);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_it_does_not_record_a_event_that_can_not_be_handled()
	{
		$this->start_with_a_new_entity();
		$this->entity_receives_a_failing_event();
	}
	
	public function test_it_can_forget_events()
	{
		$this->start_with_a_new_entity();
		$this->entity_receives_events(3);
		$this->entity_clears_events();
		$this->entity_should_have_version_number_of(3);
		
	}

	public function test_it_can_reconstitute()
	{
		$this->start_with_a_new_entity();
		$this->entity_receives_events(3);
		
		$this->entity_is_reconstituted();
		
		$this->entity_has_not_recorded_events();
		$this->entity_should_have_version_number_of(3);
		$this->entity_has_changed_state_to(3);
	}
	
	public function test_it_can_reconstitute_with_an_event_that_can_not_handle()
	{
		$this->start_with_a_new_entity();
		$this->entity_receives_events(2);
		$this->entity_receives_an_unknown_event();
		
		$this->entity_is_reconstituted();
		
		$this->entity_has_not_recorded_events();
		$this->entity_should_have_version_number_of(2);
		$this->entity_has_changed_state_to(2);
	}
	
	public function test_it_can_receive_series_of_events_an_maintain_right_version_number()
	{
		$this->start_with_a_new_entity();
		$this->entity_receives_events(2);
		$this->entity_receives_events(3);
		$this->entity_receives_events(4);
		$this->entity_should_have_version_number_of(9);
	}
		
}
?>
