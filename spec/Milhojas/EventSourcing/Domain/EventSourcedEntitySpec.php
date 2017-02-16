<?php

namespace spec\Milhojas\Library\EventSourcing\Domain;

use Milhojas\Library\EventSourcing\Domain\EventSourcedEntity;
use Milhojas\Messaging\EventBus\Event;
use PhpSpec\ObjectBehavior;
use Milhojas\Library\ValueObjects\Identity\Id;

class EventSourcedEntitySpec extends ObjectBehavior
{
    public function let(Id $id)
    {
        $id->getId()->willReturn('entity-id');
        $this->beAnInstanceOf(ESTest::class);
        $this->setId($id);
    }
    public function it_is_initializable()
    {
        $this->shouldHaveType(EventSourcedEntity::class);
    }

    public function it_returns_entity_id($id)
    {
        $this->getId()->shouldBe($id);
    }

    public function it_has_version_zero_if_newly_created()
    {
        $this->getVersion()->shouldBe(0);
    }

    public function it_accepts_an_event_to_change_state_and_version()
    {
        $this->doSomething('event-applied');
        $this->getState()->shouldBe('event-applied');
        $this->getVersion()->shouldBe(1);
    }

    public function it_stores_events()
    {
        $this->doSomething('state after event 1 was applied');
        $this->doSomething('state after event 2 was applied');
        $this->doSomething('state after event 3 was applied');
        $this->getEventStream()->shouldHaveCount(3);
    }

    public function its_state_changes_with_every_new_event()
    {
        $this->doSomething('state after event 1 was applied');
        $this->doSomething('state after event 2 was applied');
        $this->doSomething('state after event 3 was applied');
        $this->getState()->shouldBe('state after event 3 was applied');
    }

    public function its_version_changes_with_every_new_event()
    {
        $this->doSomething('state after event 1 was applied');
        $this->doSomething('state after event 2 was applied');
        $this->doSomething('state after event 3 was applied');
        $this->getVersion()->shouldBe(3);
    }

    public function it_can_forget_events_recorded_while_preserving_state()
    {
        $this->doSomething('state after event 1 was applied');
        $this->doSomething('state after event 2 was applied');
        $this->doSomething('state after event 3 was applied');
        $this->clearEvents();
        $this->getEventStream()->shouldHaveCount(0);
        $this->getState()->shouldBe('state after event 3 was applied');
        $this->getVersion()->shouldBe(3);
    }

    public function it_can_be_reconstituted_from_stream_of_events()
    {
        $this->doSomething('state after event 1 was applied');
        $this->doSomething('state after event 2 was applied');
        $this->doSomething('state after event 3 was applied');
        $entity = ESTest::reconstitute($this->getEventStream()->getWrappedObject());
        $this->getVersion()->shouldBe($entity->getVersion());
        $this->getState()->shouldBe($entity->getState());
    }
}

class ESTest extends EventSourcedEntity
{
    private $state = 'initial-state';

    public function __construct()
    {
    }

    public function setId(Id $id)
    {
        $this->id = $id;
    }
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * This is a simple spy method.
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Method encapsulates Event generation and handling.
     *
     * @param mixed $data
     */
    public function doSomething($data)
    {
        $this->recordThat(new DoEvent($data));
    }

    public function applyDoEvent(Event $event)
    {
        $this->state = $event->getData();
    }
}

class DoEvent implements Event
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getName()
    {
        return 'do.event';
    }
}
