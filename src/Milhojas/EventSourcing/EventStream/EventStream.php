<?php

namespace Milhojas\Library\EventSourcing\EventStream;

/**
 * Keeps a list of event messages.
 *
 * An even message contains Event object and metadata
 * EventSourced Entities return an EventStream
 *
 * @author Francisco Iglesias Gómez
 */
class EventStream implements \IteratorAggregate
{
    private $messages;

    public function __construct()
    {
        $this->messages = array();
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->messages);
    }

    public function count()
    {
        return count($this->messages);
    }

    public function load(array $messages)
    {
        foreach ($messages as $message) {
            $this->append($message);
        }
    }

    public function getEvents()
    {
        return array_map(function (EventMessage $message) {
            return $message->getEvent();
        }, $this->messages);
    }

    private function append(EventMessage $message)
    {
        $this->messages[] = $message;
    }

    public function flush()
    {
        $this->messages = array();
    }

    public function recordThat(EventMessage $message)
    {
        $this->messages[] = $message;
    }

    public function __toString()
    {
        $buffer[] = sprintf('Stream has %s messages', count($this->messages));
        $counter = 0;
        foreach ($this->messages as $message) {
            $buffer[] = sprintf('[%s] %s', $counter, $message);
            ++$counter;
        }

        return implode(chr(10), $buffer);
    }
}
