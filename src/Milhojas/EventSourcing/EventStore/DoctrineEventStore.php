<?php

namespace Milhojas\Library\EventSourcing\EventStore;

use Milhojas\Library\EventSourcing\EventStream\EventStream;
use Milhojas\Library\EventSourcing\EventStream\EventMessage;

use Milhojas\Library\EventSourcing\EventStore\EventStore;
use Milhojas\Library\EventSourcing\DTO\EntityDTO;
use Milhojas\Library\EventSourcing\DTO\EventDTO;

use Milhojas\Library\EventSourcing\Exceptions as Exception;

use Doctrine\ORM\Entitymanager;

class DoctrineEventStore extends EventStore
{
	private $em;
	
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}
	
	public function loadStream(EntityDTO $entity) 
	{
		$stream = new EventStream();
		foreach ($this->getStoredData($entity) as $dto) {
			$stream->recordThat(EventMessage::fromEventDTO($dto));
		}
		return $stream;
	}
	
	private function getStoredData(EntityDTO $entity)
	{
		$dtos = $this->em
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
		return $query;
	}
	
	private function buildParameters(EntityDTO $entity)
	{
		$params = array(
			'entity' => $entity->getType(),
			'id' => $entity->getPlainId()
		);
		if ($entity->getVersion()) {
			$params['version'] = $entity->getVersion();
		}
		return $params;
	}
	
	public function saveStream(EventStream $stream)
	{
		foreach ($stream as $message) {
			$this->checkVersion($message->getEntity());
			$this->em->persist(EventDTO::fromEventMessage($message));
		}
		$this->em->flush();
		$this->em->clear();
	}
	
	public function countEntitiesOfType($type)
	{
		return $this->em
			->createQuery('SELECT COUNT(events.id) FROM EventStore:EventDTO events WHERE events.entity_type = :entity AND events.version = 1')
			->setParameter('entity', $type)
			->getSingleScalarResult();
	}
	
	
	public function count(EntityDTO $entity)
	{
		return $this->em
			->createQuery('SELECT COUNT(events.id) FROM EventStore:EventDTO events WHERE events.entity_type = :entity AND events.entity_id = :id')
			->setParameter('entity', $entity->getType())
			->setParameter('id', $entity->getPlainId())
			->getSingleScalarResult();
	}
	
	protected function getStoredVersion(EntityDTO $entity)
	{
		return $this->em
			->createQuery('SELECT MAX(events.version) FROM EventStore:EventDTO events WHERE events.entity_type = :entity AND events.entity_id = :id')
			->setParameter('entity', $entity->getType())
			->setParameter('id', $entity->getPlainId())
			->getSingleScalarResult();
	}
	

}


?>
