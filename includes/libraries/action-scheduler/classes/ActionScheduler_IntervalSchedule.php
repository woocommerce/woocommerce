<?php

/**
 * Class ActionScheduler_IntervalSchedule
 */
class ActionScheduler_IntervalSchedule implements ActionScheduler_Schedule {
	/** @var DateTime */
	private $start = NULL;
	private $start_timestamp = 0;
	private $interval_in_seconds = 0;

	public function __construct( DateTime $start, $interval ) {
		$this->start = $start;
		$this->interval_in_seconds = (int)$interval;
	}

	/**
	 * @param DateTime $after
	 *
	 * @return DateTime|null
	 */
	public function next( DateTime $after = NULL ) {
		$after = empty($after) ? as_get_datetime_object('@0') : clone $after;
		if ( $after > $this->start ) {
			$after->modify('+'.$this->interval_in_seconds.' seconds');
			return $after;
		}
		return clone $this->start;
	}

	/**
	 * @return bool
	 */
	public function is_recurring() {
		return true;
	}

	/**
	 * @return int
	 */
	public function interval_in_seconds() {
		return $this->interval_in_seconds;
	}

	/**
	 * For PHP 5.2 compat, since DateTime objects can't be serialized
	 * @return array
	 */
	public function __sleep() {
		$this->start_timestamp = $this->start->getTimestamp();
		return array(
			'start_timestamp',
			'interval_in_seconds'
		);
	}

	public function __wakeup() {
		$this->start = as_get_datetime_object($this->start_timestamp);
	}
}
