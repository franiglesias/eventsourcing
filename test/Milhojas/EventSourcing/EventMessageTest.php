<?php

namespace Tests\Library\EventSourcing;

use Milhojas\Library\EventSourcing\EventStream\EventMessage;
use Milhojas\Messaging\EventBus\Event;
use Milhojas\Library\EventSourcing\DTO\EntityDTO;

/**
 * Description.
 */
class EventMessageTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->event = $this->prophesize(Event::class);
        $this->entity = $this->prophesize(EntityDTO::class);
        $this->message = EventMessage::record($this->event->reveal(), $this->entity->reveal());
    }

    public function test_it_can_return_the_event()
    {
        $this->assertEquals($this->event->reveal(), $this->message->getEvent());
    }

    public function test_it_can_add_metadata()
    {
        $this->message->addMetadata('Meta', 'Data');
        $this->assertEquals(array('Meta' => 'Data'), $this->message->getMetadata());
    }

    public function test_it_can_add_an_array_of_metadata()
    {
        $metadata = array(
            'meta' => 'data',
            'field' => 'value',
        );
        $this->message->addMetadata($metadata);
        $this->assertEquals($metadata, $this->message->getMetadata());
    }
}
