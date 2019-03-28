<?php

/**
 * Class ActionScheduler_SimpleSchedule_Test
 * @group schedules
 */
class ActionScheduler_SimpleSchedule_Test extends ActionScheduler_UnitTestCase {
	public function test_creation() {
		$time = as_get_datetime_object();
		$schedule = new ActionScheduler_SimpleSchedule($time);
		$this->assertEquals( $time, $schedule->next() );
	}

	public function test_past_date() {
		$time = as_get_datetime_object('-1 day');
		$schedule = new ActionScheduler_SimpleSchedule($time);
		$this->assertEquals( $time, $schedule->next() );
	}

	public function test_future_date() {
		$time = as_get_datetime_object('+1 day');
		$schedule = new ActionScheduler_SimpleSchedule($time);
		$this->assertEquals( $time, $schedule->next() );
	}

	public function test_grace_period_for_next() {
		$time = as_get_datetime_object('3 seconds ago');
		$schedule = new ActionScheduler_SimpleSchedule($time);
		$this->assertEquals( $time, $schedule->next() );
	}

	public function test_is_recurring() {
		$schedule = new ActionScheduler_SimpleSchedule(as_get_datetime_object('+1 day'));
		$this->assertFalse( $schedule->is_recurring() );
	}
}
 