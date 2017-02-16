<?php

namespace Tests\Library\EventSourcing;

use Milhojas\Library\EventSourcing\EventStream\EventStream;
use Milhojas\Messaging\EventBus\Event;
use Milhojas\Library\EventSourcing\DTO\EntityDTO;
use Milhojas\Library\ValueObjects\Identity\Id;
use Tests\Library\EventSourcing\Fixtures\EventDouble;
use Milhojas\Library\EventSourcing\EventStream\EventMessage;

class EventStreamTest extends \PHPUnit_Framework_Testcase
{
    public function test_event_stream_linked_to_event_sourced_entity()
    {
        $Stream = new EventStream();
    }

    public function test_it_can_load_an_array_of_events()
    {
        $events = array(
            EventMessage::record(new EventDouble('Event 1'), new EntityDTO('Entity', new Id(1))),
            EventMessage::record(new EventDouble('Event 2'), new EntityDTO('Entity', new Id(1))),
            EventMessage::record(new EventDouble('Event 3'), new EntityDTO('Entity', new Id(1))),
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
            EventMessage::record(new EventDouble('Event 1'), new EntityDTO('Entity', new Id(1))),
            EventMessage::record(new EventDouble('Event 2'), new EntityDTO('Entity', new Id(1))),
            EventMessage::record(new EventDouble('Event 3'), new EntityDTO('Entity', new Id(1))),
        );
        $Stream = new EventStream();
        $Stream->load($events);
        $this->assertEquals([new EventDouble('Event 1'), new EventDouble('Event 2'), new EventDouble('Event 3')], $Stream->getEvents());
    }

    public function test_it_can_return_the_number_of_events_it_holds()
    {
        $events = array(
            EventMessage::record(new EventDouble('Event 1'), new EntityDTO('Entity', new Id(1))),
            EventMessage::record(new EventDouble('Event 2'), new EntityDTO('Entity', new Id(1))),
            EventMessage::record(new EventDouble('Event 3'), new EntityDTO('Entity', new Id(1))),
        );
        $Stream = new EventStream();
        $Stream->load($events);
        $this->assertEquals(3, $Stream->count());
    }

    public function test_it_can_flush_events()
    {
        $events = array(
            EventMessage::record(new EventDouble('Event 1'), new EntityDTO('Entity', new Id(1))),
            EventMessage::record(new EventDouble('Event 2'), new EntityDTO('Entity', new Id(1))),
            EventMessage::record(new EventDouble('Event 3'), new EntityDTO('Entity', new Id(1))),
        );
        $Stream = new EventStream();
        $Stream->load($events);
        $Stream->flush();
        $this->assertEquals(0, $Stream->count());
    }

    public function test_it_can_record_events()
    {
        $Stream = new EventStream();
        $Stream->recordThat(EventMessage::record(new EventDouble('event 1'), new EntityDTO('Entity', new Id(1))));
        $this->assertEquals(1, $Stream->count());
        $Stream->recordThat(EventMessage::record(new EventDouble('event 2'), new EntityDTO('Entity', new Id(1))));
        $this->assertEquals(2, $Stream->count());
        $Stream->recordThat(EventMessage::record(new EventDouble('event 3'), new EntityDTO('Entity', new Id(1))));
        $this->assertEquals(3, $Stream->count());
    }

    public function dont_test_it_ignores_invalid_events()
    {
        $events = array(
            EventMessage::record(new EventDouble('Event 1'), new EntityDTO('Entity', new Id(1))),
            'event 2',
            EventMessage::record(new EventDouble('Event 3'), new EntityDTO('Entity', new Id(1))),
        );
        $Stream = new EventStream();
        $Stream->load($events);
        $this->assertEquals(2, $Stream->count());
    }
}
