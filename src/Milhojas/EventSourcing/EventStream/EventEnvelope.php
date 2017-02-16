<?php

namespace Milhojas\Library\EventSourcing\EventStream;

use Rhumsaa\Uuid\Uuid;
use Milhojas\Library\EventSourcing\DTO\EventDTO;
/**
* Contains metadata for event messages
*/
class EventEnvelope
{
	private $id;
	private $time;
	private $metadata;
	
	private function __construct($id, $time, $metadata)
	{
		$this->id = $id;
		$this->time = $time;
		$this->metadata = $metadata;
	}
	
	static public function now()
	{
		return new static(
			self::autoAssignIdentity(),
			new \DateTimeImmutable(),
			array()
		);
	}
	
	static public function fromEventDTO(EventDTO $dto)
	{
		return new static(
			$dto->getId(),
			$dto->getTime(),
			$dto->getMetadata()
		);
	}
	
	static private function autoAssignIdentity()
	{
		return Uuid::uuid4()->toString();
	}
	
	public function addMetaData($key, $value = null)
	{
		$data = $key;
		if (!is_array($key)) {
			$data = array($key => $value);
		}
		$this->metadata += $data;
	}
	
	public function getMessageId()
	{
		return $this->id;
	}
	public function getTime()
	{
		return $this->time;
	}
	public function getMetaData()
	{
		return $this->metadata;
	}
	
}

?>
