<?php

/**
 * Class ActionScheduler_NullSchedule_Test
 * @group schedules
 */
class ActionScheduler_NullSchedule_Test extends ActionScheduler_UnitTestCase {
	public function test_null_schedule() {
		$schedule = new ActionScheduler_NullSchedule();
		$this->assertNull( $schedule->next() );
	}

	public function test_is_recurring() {
		$schedule = new ActionScheduler_NullSchedule();
		$this->assertFalse( $schedule->is_recurring() );
	}
}
 