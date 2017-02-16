<?php

namespace Tests\Infrastructure\Persistence\Contents\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Milhojas\EventSourcing\DTO\EventDTO;
use Test\EventSourcing\Fixtures\EventDouble;

// https://vincent.composieux.fr/article/test-your-doctrine-repository-using-a-sqlite-database

class ESFixtures extends AbstractFixture
{
    private $eventId;

    /**
     * Load fixtures.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $manager->clear();
        gc_collect_cycles(); // Could be useful if you have a lot of fixtures
        $this->eventId = 0;
        $this->generateEvents($manager, 'Entity', 1, 3);
        $this->generateEvents($manager, 'Other', 1, 4);
        $this->generateEvents($manager, 'Entity', 2, 6);
        $manager->flush();
    }

    private function generateEvents($manager, $entity, $id, $maxVersion)
    {
        for ($version = 1; $version <= $maxVersion; ++$version) {
            ++$this->eventId;
            $event = new EventDTO();

            $event->setId($this->eventId);
            $event->setEventType('EventDouble');
            $event->setEvent(new EventDouble($id));
            $event->setEntityType($entity);
            $event->setEntityId($id);
            $event->setVersion($version);
            $event->setMetadata(array());
            $event->setTime(new \DateTimeImmutable());

            $this->addReference(sprintf('test-event-%s-%s-%s', $entity, $id, $version), $event);
            $manager->persist($event);
        }
    }
}
