<?php

/**
 * Class ActionScheduler_CronSchedule_Test
 * @group schedules
 */
class ActionScheduler_CronSchedule_Test extends ActionScheduler_UnitTestCase {
	public function test_creation() {
		$time = as_get_datetime_object('tomorrow');
		$cron = CronExpression::factory('@daily');
		$schedule = new ActionScheduler_CronSchedule(as_get_datetime_object(), $cron);
		$this->assertEquals( $time, $schedule->next() );
	}

	public function test_next() {
		$time = as_get_datetime_object('2013-06-14');
		$cron = CronExpression::factory('@daily');
		$schedule = new ActionScheduler_CronSchedule($time, $cron);
		$this->assertEquals( as_get_datetime_object('tomorrow'), $schedule->next( as_get_datetime_object() ) );
	}

	public function test_is_recurring() {
		$schedule = new ActionScheduler_CronSchedule(as_get_datetime_object('2013-06-14'), CronExpression::factory('@daily'));
		$this->assertTrue( $schedule->is_recurring() );
	}

	public function test_cron_format() {
		$time = as_get_datetime_object('2014-01-01');
		$cron = CronExpression::factory('0 0 10 10 *');
		$schedule = new ActionScheduler_CronSchedule($time, $cron);
		$this->assertEquals( as_get_datetime_object('2014-10-10'), $schedule->next() );

		$cron = CronExpression::factory('0 0 L 1/2 *');
		$schedule = new ActionScheduler_CronSchedule($time, $cron);
		$this->assertEquals( as_get_datetime_object('2014-01-31'), $schedule->next() );
		$this->assertEquals( as_get_datetime_object('2014-07-31'), $schedule->next( as_get_datetime_object('2014-06-01') ) );
		$this->assertEquals( as_get_datetime_object('2028-11-30'), $schedule->next( as_get_datetime_object('2028-11-01') ) );

		$cron = CronExpression::factory('30 14 * * MON#3 *');
		$schedule = new ActionScheduler_CronSchedule($time, $cron);
		$this->assertEquals( as_get_datetime_object('2014-01-20 14:30:00'), $schedule->next() );
		$this->assertEquals( as_get_datetime_object('2014-05-19 14:30:00'), $schedule->next( as_get_datetime_object('2014-05-01') ) );
	}
}
 