<?php

namespace Test\EventSourcing;

use Milhojas\EventSourcing\EventStream\EventStream;
use Milhojas\Messaging\EventBus\Event;
use Milhojas\EventSourcing\EventStream\Entity;
use Test\EventSourcing\Fixtures\EventDouble;
use Milhojas\EventSourcing\EventStream\EventMessage;

class EventStreamTest extends \PHPUnit_Framework_Testcase
{
    public function test_it_can_load_an_array_of_events()
    {
        $events = array(
            EventMessage::record(new EventDouble('Event 1'), new Entity('Entity', 1)),
            EventMessage::record(new EventDouble('Event 2'), new Entity('Entity', 1)),
            EventMessage::record(new EventDouble('Event 3'), new Entity('Entity', 1)),
        );
        $Stream = new EventStream();
        $Stream->load($events);
        foreach ($Stream as $event) {
            $this->assertEquals(current($events), $event);
            next($events);
        }
    }

    public function test_is_can_return_plain_events()
    {
        $events = array(
            EventMessage::record(new EventDouble('Event 1'), new Entity('Entity', 1)),
            EventMessage::record(new EventDouble('Event 2'), new Entity('Entity', 1)),
            EventMessage::record(new EventDouble('Event 3'), new Entity('Entity', 1)),
        );
        $Stream = new EventStream();
        $Stream->load($events);
        $this->assertEquals([new EventDouble('Event 1'), new EventDouble('Event 2'), new EventDouble('Event 3')], $Stream->getEvents());
    }

    public function test_it_can_return_the_number_of_events_it_holds()
    {
        $events = array(
            EventMessage::record(new EventDouble('Event 1'), new Entity('Entity', 1)),
            EventMessage::record(new EventDouble('Event 2'), new Entity('Entity', 1)),
            EventMessage::record(new EventDouble('Event 3'), new Entity('Entity', 1)),
        );
        $Stream = new EventStream();
        $Stream->load($events);
        $this->assertEquals(3, $Stream->count());
    }

    public function test_it_can_flush_events()
    {
        $events = array(
            EventMessage::record(new EventDouble('Event 1'), new Entity('Entity', 1)),
            EventMessage::record(new EventDouble('Event 2'), new Entity('Entity', 1)),
            EventMessage::record(new EventDouble('Event 3'), new Entity('Entity', 1)),
        );
        $Stream = new EventStream();
        $Stream->load($events);
        $Stream->flush();
        $this->assertEquals(0, $Stream->count());
    }

    public function test_it_can_record_events()
    {
        $Stream = new EventStream();
        $Stream->recordThat(EventMessage::record(new EventDouble('event 1'), new Entity('Entity', 1)));
        $this->assertEquals(1, $Stream->count());
        $Stream->recordThat(EventMessage::record(new EventDouble('event 2'), new Entity('Entity', 1)));
        $this->assertEquals(2, $Stream->count());
        $Stream->recordThat(EventMessage::record(new EventDouble('event 3'), new Entity('Entity', 1)));
        $this->assertEquals(3, $Stream->count());
    }

    public function dont_test_it_ignores_invalid_events()
    {
        $events = array(
            EventMessage::record(new EventDouble('Event 1'), new Entity('Entity', 1)),
            'event 2',
            EventMessage::record(new EventDouble('Event 3'), new Entity('Entity', 1)),
        );
        $Stream = new EventStream();
        $Stream->load($events);
        $this->assertEquals(2, $Stream->count());
    }
}
