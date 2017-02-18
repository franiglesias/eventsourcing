<?php

namespace Milhojas\EventSourcing\EventStore;

use Doctrine\DBAL\Connection;
use Milhojas\EventSourcing\EventStream\EventStream;
use Milhojas\EventSourcing\EventStream\EventMessage;
use Milhojas\EventSourcing\EventStream\Entity;
use Milhojas\EventSourcing\Exceptions as Exception;

class DBALEventStore extends EventStore
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function loadStream(Entity $entity)
    {
        $this->connection->beginTransaction();
        try {
            $stream = new EventStream();
            foreach ($this->getStoredData($entity) as $dto) {
                $stream->recordThat(EventMessage::fromDtoArray($dto));
            }
            $this->connection->commit();

            return $stream;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function saveStream(EventStream $stream)
    {
        $this->connection->beginTransaction();
        try {
            foreach ($stream as $message) {
                $this->checkVersion($message->getEntity());
                $this->saveEvent($message);
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function saveEvent(EventMessage $message)
    {
        $sql = 'INSERT events SET events.id = :id, events.event_type = :type, events.event = :event, events.entity_type = :entity_type, events.entity_id = :entity_id, events.version = :version, events.timestamp = :timestamp, events.metadata = :metadata';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $message->getId());
        $stmt->bindValue('type', get_class($message->getEvent()));
        $stmt->bindValue('event', $message->getEvent(), 'object');
        $stmt->bindValue('entity_type', $message->getEntity()->getType());
        $stmt->bindValue('entity_id', $message->getEntity()->getId());
        $stmt->bindValue('version', $message->getEntity()->getVersion());
        $stmt->bindValue('timestamp', $message->getEnvelope()->getTime(), 'datetimetz');
        $stmt->bindValue('metadata', $message->getMetadata(), 'array');
        $stmt->execute();
    }

    private function getStoredData(Entity $entity)
    {
        $sql = $this->buildDQL($entity);
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('entity_type', $entity->getType());
        $stmt->bindValue('entity_id', $entity->getId());
        if ($entity->getVersion()) {
            $stmt->bindValue('version', $entity->getVersion());
        }
        $stmt->execute();
        $dtos = $stmt->fetchAll();

        if (!$dtos) {
            throw new Exception\EntityNotFound(sprintf('No events found for entity: %s', $entity->getType()), 2);
        }

        return $dtos;
    }

    private function buildDQL(Entity $entity)
    {
        $query = 'SELECT * FROM events WHERE events.entity_type = :entity_type AND events.entity_id = :entity_id';
        if ($entity->getVersion()) {
            $query .= ' AND events.version <= :version';
        }
        $query .= ' ORDER BY events.entity_type, events.entity_id, events.version';

        return $query;
    }

    public function countEntitiesOfType($type)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->select('COUNT(events.id) AS entities')
            ->from('events')
            ->where('events.entity_type = :entity')->andWhere('events.version = 1')
            ->setParameter('entity', $type)
        ;
        $result = $builder->execute()->fetchColumn();

        return (int) $result;
    }

    public function count(Entity $entity)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->select('COUNT(events.id) AS entities')
            ->from('events')
            ->where('events.entity_type = :entity')->andWhere('events.entity_id = :id')
            ->setParameter('entity', $entity->getType())
            ->setParameter('id', $entity->getPlainId())
        ;
        $result = $builder->execute()->fetchColumn();

        return (int) $result;
    }

    protected function getStoredVersion(Entity $entity)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->select('MAX(events.version) AS stored_version')
            ->from('events')
            ->where('events.entity_type = :entity')
            ->andWhere('events.entity_id = :id')
            ->setParameter('entity', $entity->getType())
            ->setParameter('id', $entity->getPlainId());
        $result = $builder->execute()->fetchColumn();

        return (int) $result;
    }
}
