<?php
/**
 * Tests for WC_Priority_Action_Queue.
 *
 * @package WooCommerce\Tests\Queue
 */

declare( strict_types = 1 );

/**
 * Tests for the WC_Priority_Action_Queue class.
 */
class WC_Priority_Action_Queue_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Priority_Action_Queue
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Priority_Action_Queue();
	}

	/**
	 * Read the stored Action Scheduler priority of an action.
	 *
	 * @param int $action_id The action ID.
	 * @return int
	 */
	private function get_stored_priority( int $action_id ): int {
		return ActionScheduler::store()->fetch_action( (string) $action_id )->get_priority();
	}

	/**
	 * @testdox Should be the queue WC()->queue() returns by default.
	 */
	public function test_is_the_default_queue(): void {
		$this->assertInstanceOf( WC_Priority_Action_Queue::class, WC()->queue() );
	}

	/**
	 * @testdox Should be usable as both a plain queue and a priority queue.
	 */
	public function test_implements_both_queue_interfaces(): void {
		$this->assertInstanceOf( WC_Queue_Interface::class, $this->sut );
		$this->assertInstanceOf( WC_Priority_Queue_Interface::class, $this->sut );
	}

	/**
	 * Third-party queues that extend WC_Action_Queue must not pass the capability check, so
	 * that callers fall back to the plain methods those queues override.
	 *
	 * @testdox Should not make plain WC_Action_Queue subclasses priority queues.
	 */
	public function test_plain_action_queue_is_not_a_priority_queue(): void {
		$this->assertNotInstanceOf( WC_Priority_Queue_Interface::class, new WC_Action_Queue() );
	}

	/**
	 * @testdox Should pass the requested priority to Action Scheduler, which clamps it to the 0-255 range it accepts.
	 * @testWith [1, 1]
	 *           [10, 10]
	 *           [200, 200]
	 *           [-5, 0]
	 *           [300, 255]
	 *
	 * @param int $requested_priority The priority passed to the queue.
	 * @param int $expected_priority  The priority Action Scheduler is expected to store.
	 */
	public function test_schedule_single_with_priority_stores_priority( int $requested_priority, int $expected_priority ): void {
		$action_id = $this->sut->schedule_single_with_priority(
			time() + HOUR_IN_SECONDS,
			'wc_priority_queue_test_single',
			array( 'priority' => $requested_priority ),
			'wc-priority-queue-test',
			$requested_priority
		);

		$this->assertSame( $expected_priority, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should default to priority 10 when no priority is given.
	 */
	public function test_priority_methods_default_to_ten(): void {
		$action_id = $this->sut->schedule_single_with_priority(
			time() + HOUR_IN_SECONDS,
			'wc_priority_queue_test_default',
			array(),
			'wc-priority-queue-test'
		);

		$this->assertSame( 10, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should schedule an immediate action at the requested priority.
	 */
	public function test_add_with_priority_schedules_now_with_priority(): void {
		$before = time();

		$action_id = $this->sut->add_with_priority( 'wc_priority_queue_test_add', array(), 'wc-priority-queue-test', 3 );

		$action = ActionScheduler::store()->fetch_action( (string) $action_id );
		$this->assertSame( 3, $action->get_priority() );
		$this->assertGreaterThanOrEqual( $before, $action->get_schedule()->get_date()->getTimestamp(), 'add_with_priority() should schedule for now, not the future' );
	}

	/**
	 * @testdox Should store the requested priority on recurring actions.
	 */
	public function test_schedule_recurring_with_priority_stores_priority(): void {
		$action_id = $this->sut->schedule_recurring_with_priority(
			time() + HOUR_IN_SECONDS,
			DAY_IN_SECONDS,
			'wc_priority_queue_test_recurring',
			array(),
			'wc-priority-queue-test',
			25
		);

		$this->assertSame( 25, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should store the requested priority on cron actions.
	 */
	public function test_schedule_cron_with_priority_stores_priority(): void {
		$action_id = $this->sut->schedule_cron_with_priority(
			time() + HOUR_IN_SECONDS,
			'0 0 * * *',
			'wc_priority_queue_test_cron',
			array(),
			'wc-priority-queue-test',
			25
		);

		$this->assertSame( 25, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should schedule non-unique actions, so repeat calls do not collapse into one.
	 */
	public function test_priority_methods_schedule_non_unique_actions(): void {
		$timestamp = time() + HOUR_IN_SECONDS;

		$first  = $this->sut->schedule_single_with_priority( $timestamp, 'wc_priority_queue_test_unique', array(), 'wc-priority-queue-test', 5 );
		$second = $this->sut->schedule_single_with_priority( $timestamp, 'wc_priority_queue_test_unique', array(), 'wc-priority-queue-test', 5 );

		$this->assertNotSame( 0, $second, 'Action Scheduler returns 0 when a unique action is rejected as a duplicate' );
		$this->assertNotEquals( $first, $second, 'Identical calls should produce two separate actions, as the plain queue methods do' );
	}

	/**
	 * @testdox Should leave the plain scheduling methods at their inherited priority of 10.
	 */
	public function test_plain_methods_still_schedule_at_default_priority(): void {
		$action_id = $this->sut->schedule_single( time() + HOUR_IN_SECONDS, 'wc_priority_queue_test_plain', array(), 'wc-priority-queue-test' );

		$this->assertSame( 10, $this->get_stored_priority( $action_id ) );
	}
}
