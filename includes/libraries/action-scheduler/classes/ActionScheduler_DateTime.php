<?php

/**
 * ActionScheduler DateTime class.
 *
 * This is a custom extension to DateTime that
 */
class ActionScheduler_DateTime extends DateTime {

	/**
	 * Get the unix timestamp of the current object.
	 *
	 * Missing in PHP 5.2 so just here so it can be supported consistently.
	 *
	 * @return int
	 */
	public function getTimestamp() {
		return method_exists( 'DateTime', 'getTimestamp' ) ? parent::getTimestamp() : $this->format( 'U' );
	}
}
