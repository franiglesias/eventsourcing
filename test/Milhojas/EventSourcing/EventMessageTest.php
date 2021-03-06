<?php

namespace Test\EventSourcing;

use Milhojas\EventSourcing\EventStream\EventMessage;
use Milhojas\Messaging\EventBus\Event;
use Milhojas\EventSourcing\EventStream\Entity;

/**
 * Description.
 */
class EventMessageTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->event = $this->prophesize(Event::class);
        $this->entity = $this->prophesize(Entity::class);
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
