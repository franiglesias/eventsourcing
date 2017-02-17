<?php

namespace Milhojas\EventSourcing\EventStore;

use Doctrine\DBAL\Connection;
use Milhojas\EventSourcing\EventStream\EventStream;
use Milhojas\EventSourcing\EventStream\EventMessage;
use Milhojas\EventSourcing\DTO\EntityDTO;
use Milhojas\EventSourcing\DTO\EventDTO;
use Milhojas\EventSourcing\Exceptions as Exception;

class DBALEventStore extends EventStore
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function loadStream(EntityDTO $entity)
    {
        $stream = new EventStream();
        foreach ($this->getStoredData($entity) as $dto) {
            $stream->recordThat(EventMessage::fromEventDTO($dto));
        }

        return $stream;
    }

    public function saveStream(EventStream $stream)
    {
        foreach ($stream as $message) {
            $this->checkVersion($message->getEntity());
            $this->saveEvent($message);
        }
    }

    public function saveEvent(EventMessage $message)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->update('events')
        ->set('events.id', $message->getId())
        ->set('events.type', get_class($message->getEvent()))
        ->set('events.event', ':event')
        ->set('events.entity_type', $message->getEntity()->getType())
        ->set('events.entity_id', $message->getEntity()->getId())
        ->set('events.version', $message->getEntity()->getVersion())
        ->set('events.timestamp', ':time')
        ->set('events.metadata', ':metadata')
        ->setParameter(':event', $message->getEvent(), 'object')
        ->setParameter(':time', $message->getEnvelope()->getTime(), 'datetimetz')
        ->setParameter(':metadata', $message->getMetadata(), \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
        ;
        $builder->execute();
    }

    private function getStoredData(EntityDTO $entity)
    {
        $dtos = $this->connection
            ->createQuery($this->buildDQL($entity))
            ->setParameters($this->buildParameters($entity))
            ->getResult();

        if (!$dtos) {
            throw new Exception\EntityNotFound(sprintf('No events found for entity: %s', $entity->getType()), 2);
        }

        return $dtos;
    }

    private function buildDQL(EntityDTO $entity)
    {
        $query = 'SELECT events FROM EventStore:EventDTO events WHERE events.entity_type = :entity AND events.entity_id = :id';
        if ($entity->getVersion()) {
            $query .= ' AND events.version <= :version';
        }
        $query .= ' ORDER BY events.entity_type, events.entity_id, events.version';

        return $query;
    }

    private function buildParameters(EntityDTO $entity)
    {
        $params = array(
            'entity' => $entity->getType(),
            'id' => $entity->getPlainId(),
        );
        if ($entity->getVersion()) {
            $params['version'] = $entity->getVersion();
        }

        return $params;
    }

    public function countEntitiesOfType($type)
    {
        return $this->connection
            ->createQuery('SELECT COUNT(events.id) FROM EventStore:EventDTO events WHERE events.entity_type = :entity AND events.version = 1')
            ->setParameter('entity', $type)
            ->getSingleScalarResult();
    }

    public function count(EntityDTO $entity)
    {
        return $this->connection
            ->createQuery('SELECT COUNT(events.id) FROM EventStore:EventDTO events WHERE events.entity_type = :entity AND events.entity_id = :id')
            ->setParameter('entity', $entity->getType())
            ->setParameter('id', $entity->getPlainId())
            ->getSingleScalarResult();
    }

    protected function getStoredVersion(EntityDTO $entity)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->select('MAX(events.version) AS stored_version')
            ->from('events')
            ->where($builder->expr()->andX(
                $builder->expr()->eq('events.entity_type', ':entity'),
                $builder->expr()->eq('events.entity_id', ':id')
            ))
            ->setParameter('entity', $entity->getType())
            ->setParameter('id', $entity->getPlainId());
        $result = $builder->execute()->fetchColumn();

        return (int) $result;
    }
}
