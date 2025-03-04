<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\BackgroundScheduler;
use Automattic\WooCommerce\Caching\BackgroundAction;
use PHPUnit\Framework\TestCase;

/**
 * BackgroundSchedulerTest
 */
class BackgroundSchedulerTest extends \WC_Unit_Test_Case {
	/**
	 * BackgroundScheduler instance.
	 *
	 * @var BackgroundScheduler
	 */
	private $background_scheduler;

	/**
	 * Setup the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->background_scheduler = new BackgroundScheduler();
	}

	/**
	 * Test that the background scheduler initializes hooks.
	 */
	public function test_background_scheduler_initializes_hooks() {
		$this->background_scheduler->init();
		$this->assertTrue( as_has_scheduled_action( BackgroundScheduler::BACKGROUND_HOOK_NAME ) );
	}

	/**
	 * Test that the background scheduler schedules an action in action scheduler.
	 */
	public function test_background_scheduler_schedules_action() {
		$action = new BackgroundAction(
			array(
				'hook'                => 'test_action',
				'interval_in_seconds' => 21600,
				'callback'            => function() {
					return 'test_callback';
				}
			)
		);
		$this->background_scheduler->register_action( $action );
		$this->background_scheduler->schedule_actions_from_registered_actions();

		$next_scheduled_action = as_next_scheduled_action( $action->get_hook() );
		$this->assertNotFalse( $next_scheduled_action, 'Action should be scheduled' );
	}

	/**
	 * Test that the background scheduler throws an exception if the action is not a BackgroundAction.
	 */
	public function test_background_scheduler_schedules_action_validates_input() {
		$this->expectException( \InvalidArgumentException::class );
		$this->background_scheduler->register_action( null );
	}

	/**
	 * Test that the background scheduler cleans up unregistered actions.
	 */
	public function test_background_scheduler_cleans_up_unregistered_actions() {
		as_schedule_single_action( time() + MINUTE_IN_SECONDS, 'test_action', array(), BackgroundScheduler::BACKGROUND_GROUP_NAME );

		$this->background_scheduler->schedule_actions_from_registered_actions();

		$scheduled_actions = as_get_scheduled_actions( array(
			'group'  => BackgroundScheduler::BACKGROUND_GROUP_NAME,
			'status' => \ActionScheduler_Store::STATUS_PENDING,
		) );
		$this->assertCount( 0, $scheduled_actions, 'Unregistered action should not be scheduled' );
	}
} 
