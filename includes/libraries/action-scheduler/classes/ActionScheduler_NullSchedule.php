<?php

/**
 * Class ActionScheduler_NullSchedule
 */
class ActionScheduler_NullSchedule implements ActionScheduler_Schedule {

	public function next( DateTime $after = NULL ) {
		return NULL;
	}

	/**
	 * @return bool
	 */
	public function is_recurring() {
		return false;
	}
}
 