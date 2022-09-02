<?php
/**
 * Test for the queue class.
 * @package WooCommerce\Tests\Queue
 */

 /**
  * WC_Tests_Discounts.
  */
class WC_Tests_Queue extends WC_Unit_Test_Case {

	/**
	 * Test scheduling and retrieving actions.
	 */
	public function test_schedule_and_get_actions() {
		$queue = WC_Queue::instance();

		// Set up action arguments.
		$current_time = time();
		$timestamp    = $current_time + HOUR_IN_SECONDS;
		$args         = range( 100, 130 );
		$args         = array_flip( $args );
		$unique_hash  = md5( wp_json_encode( $args ) );
		$args[119]    = $unique_hash;
		$group        = 'wc-unit-tests';

		// Schedule a single action.
		$hook   = 'single_test_action';
		$single = $queue->schedule_single( $timestamp, $hook, $args, $group );

		// Test next schedule is specified timestamp.
		$schedule = $queue->get_next( $hook, $args, $group );
		$this->assertEquals( $schedule->getTimestamp(), $timestamp );

		// Test that the action can be found.
		$action_ids = $queue->search(
			array(
				'hook' => $hook,
				'args' => $args,
				'group' => $group,
			),
			'ids'
		);
		$this->assertContains( $single, $action_ids );
		$action_ids = $queue->search(
			array(
				'hook' => $hook,
				'search' => $unique_hash,
				'group' => $group,
			),
			'ids'
		);
		$this->assertContains( $single, $action_ids );

		// Schedule a recurring action.
		$hook      = 'recurring_test_action';
		$recurring = $queue->schedule_recurring( $timestamp, DAY_IN_SECONDS, $hook, $args, $group );

		// Test next schedule is specified timestamp.
		$schedule = $queue->get_next( $hook, $args, $group );
		$this->assertEquals( $schedule->getTimestamp(), $timestamp );

		// Test that the action can be found.
		$action_ids = $queue->search(
			array(
				'hook' => $hook,
				'args' => $args,
				'group' => $group,
			),
			'ids'
		);
		$this->assertContains( $recurring, $action_ids );
		$action_ids = $queue->search(
			array(
				'hook' => $hook,
				'search' => $unique_hash,
				'group' => $group,
			),
			'ids'
		);
		$this->assertContains( $recurring, $action_ids );

		// Schedule a cron action on a daily midnight schedule starting at the next midnight.
		$hook          = 'recurring_cron_action';
		$cron_schedule = '0 0 * * *';
		$timestamp     = $current_time + DAY_IN_SECONDS - ( $current_time % DAY_IN_SECONDS );
		$cron_action   = $queue->schedule_cron( $timestamp - HOUR_IN_SECONDS, $cron_schedule, $hook, $args, $group );

		// Test next schedule is specified timestamp.
		$schedule = $queue->get_next( $hook, $args, $group );
		$this->assertEquals( $schedule->getTimestamp(), $timestamp );

		// Test that the action can be found.
		$action_ids = $queue->search(
			array(
				'hook' => $hook,
				'args' => $args,
				'group' => $group,
			),
			'ids'
		);
		$this->assertContains( $cron_action, $action_ids );
		$action_ids = $queue->search(
			array(
				'hook' => $hook,
				'search' => $unique_hash,
				'group' => $group,
			),
			'ids'
		);
		$this->assertContains( $cron_action, $action_ids );

		// Test wildcard search.
		$action_ids = $queue->search( array( 'search' => $unique_hash ), 'ids' );
		$this->assertEquals( count( $action_ids ), 3 );
	}
}
