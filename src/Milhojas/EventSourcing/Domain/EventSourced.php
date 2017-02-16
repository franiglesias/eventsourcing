<?php

namespace Milhojas\Library\EventSourcing\Domain;

/**
 * An entity that can be Event Sourced
 *
 * @package milhojas.library.eventsourcing
 * @author Francisco Iglesias Gómez
 */

interface EventSourced {

	/**
	 * Return the stream of uncommitted events
	 *
	 * @return EventStream object
	 * @author Fran Iglesias
	 */
	public function getEventStream();


	/**
	 * Return the identity of the entity
	 *
	 * @return Id object
	 * @author Fran Iglesias
	 */
	public function getId();

	/**
	 * Returns the version number for the entity
	 *
	 * -1: the entity has no events applied
	 *  0: initial version
	 *
	 * @return integer
	 * @author Fran Iglesias
	 */
	public function getVersion();

	/**
	 * Clear remaining events
	 *
	 * @return void
	 * @author Francisco Iglesias Gómez
	 */
	public function clearEvents();
}

?>
