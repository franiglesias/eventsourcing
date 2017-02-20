<?php

namespace Milhojas\EventSourcing\EventStore;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Milhojas\EventSourcing\EventStream\EventStream;
use Milhojas\EventSourcing\EventStream\EventMessage;
use Milhojas\EventSourcing\EventStream\Entity;
use Milhojas\EventSourcing\Exceptions as Exception;

class DBALEventStore extends EventStore
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;
    private $table;

    public function __construct(Connection $connection, $table = 'events')
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * Prepares the events table for the passed DBAL connection.
     */
    public function setUpStore()
    {
        if (!$this->tableExists()) {
            $queries = $this->getSchema()->toSql($this->connection->getDatabasePlatform());
            $this->executeSchemaQueries($queries);
        }
    }

    /**
     * Destroys the events table for the passed DBAL connection.
     */
    public function tearDownStore()
    {
        if ($this->tableExists()) {
            $queries = $this->getSchema()->toDropSql($this->connection->getDatabasePlatform());
            $this->executeSchemaQueries($queries);
        }
    }

    /**
     * Stores a single event.
     *
     * @param EventMessage $message
     */
    protected function saveEvent(EventMessage $message)
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

    /**
     * Retrieves the data for an Entity.
     *
     * @param Entity $entity
     */
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

    private function getSchema()
    {
        $schema = new Schema();
        $table = $schema->createTable($this->table);
        $table->addColumn('id', 'string');
        $table->addColumn('event_type', 'string');
        $table->addColumn('event', 'object');
        $table->addColumn('timestamp', 'datetimetz');
        $table->addColumn('version', 'integer', array('unsigned' => true));
        $table->addColumn('entity_type', 'string');
        $table->addColumn('entity_id', 'string');
        $table->addColumn('metadata', 'array');
        $table->addIndex(['entity_type', 'entity_id']);

        $table->setPrimaryKey(array('id'));

        return $schema;
    }

    /**
     * Checks if Events table exists.
     */
    private function tableExists()
    {
        return $this->connection->getSchemaManager()->tablesExist($this->table);
    }

    /**
     * Executes an array of queries.
     *
     * @param array $queries
     */
    private function executeSchemaQueries(array $queries)
    {
        array_walk($queries, function ($query) {
            $this->connection->query($query);
        });
    }
}
