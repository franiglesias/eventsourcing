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
    /**
     * The events table name, defaults to events.
     *
     * @var string
     */
    private $table;

    /**
     * @param Connection $connection
     * @param mixed      $table
     */
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
            foreach ($this->getDataForEntity($entity) as $dto) {
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
                $this->storeSingleEvent($message);
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
    protected function storeSingleEvent(EventMessage $message)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->insert($this->table)
            ->values([
                'id' => ':id',
                'event_type' => ':type',
                'event' => ':event',
                'entity_type' => ':entity_type',
                'entity_id' => ':entity_id',
                'version' => ':version',
                'timestamp' => ':timestamp',
                'metadata' => ':metadata',
            ])
            ->setParameter('id', $message->getId())
            ->setParameter('type', get_class($message->getEvent()))
            ->setParameter('event', $message->getEvent(), 'object')
            ->setParameter('entity_type', $message->getEntity()->getType())
            ->setParameter('entity_id', $message->getEntity()->getId())
            ->setParameter('version', $message->getEntity()->getVersion())
            ->setParameter('timestamp', $message->getEnvelope()->getTime(), 'datetimetz')
            ->setParameter('metadata', $message->getMetadata(), 'array')
        ;
        $builder->execute();
    }

    /**
     * Retrieves the data for an Entity.
     *
     * @param Entity $entity
     */
    private function getDataForEntity(Entity $entity)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
        ->select('*')
        ->from($this->table)
        ->where('entity_type = :entity_type')
        ->andWhere('entity_id = :entity_id')
        ->setParameters([
            'entity_type' => $entity->getType(),
            'entity_id' => $entity->getId(),
        ])
        ->orderBy('entity_type', 'ASC')
        ->addOrderBy('entity_id', 'ASC')
        ->addOrderBy('version', 'ASC')
        ;
        if ($entity->getVersion()) {
            $builder
                ->andWhere('version = :version')
                ->setParameter('version', $entity->getVersion())
            ;
        }
        $dtos = $builder->execute()->fetchAll();

        if (!$dtos) {
            throw new Exception\EntityNotFound(sprintf('No events found for entity: %s', $entity->getType()), 2);
        }

        return $dtos;
    }

    public function countEntitiesOfType($type)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->select('COUNT(id) AS entities')
            ->from('events')
            ->where('entity_type = :entity')->andWhere('version = 1')
            ->setParameter('entity', $type)
        ;
        $result = $builder->execute()->fetchColumn();

        return (int) $result;
    }

    public function count(Entity $entity)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->select('COUNT(id) AS entities')
            ->from($this->table)
            ->where('entity_type = :entity')->andWhere('entity_id = :id')
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
            ->select('MAX(version) AS stored_version')
            ->from($this->table)
            ->where('entity_type = :entity')
            ->andWhere('entity_id = :id')
            ->setParameter('entity', $entity->getType())
            ->setParameter('id', $entity->getPlainId());
        $result = $builder->execute()->fetchColumn();

        return (int) $result;
    }

    /**
     * Generates the events table schema.
     *
     * @return Schema The schema
     */
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
     * Checks if the events table exists.
     */
    private function tableExists()
    {
        return $this->connection->getSchemaManager()->tablesExist($this->table);
    }

    /**
     * Utility method to execute an array of queries.
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
