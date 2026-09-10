<?php
/**
 * Tests for WC_Action_Queue.
 *
 * @package WooCommerce\Tests\Queue
 */

declare( strict_types = 1 );

/**
 * Tests for the WC_Action_Queue class.
 */
class WC_Action_Queue_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Action_Queue
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Action_Queue();
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
	 * @testdox Should be usable as both a plain queue and a priority-aware queue.
	 */
	public function test_implements_both_queue_interfaces(): void {
		$this->assertInstanceOf( WC_Queue_Interface::class, $this->sut );
		$this->assertInstanceOf( WC_Queue_Priority_Interface::class, $this->sut );
	}

	/**
	 * @testdox Should store the requested priority on a single action, clamped to the 0-255 range Action Scheduler accepts.
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
			'wc_action_queue_test_single',
			array( 'priority' => $requested_priority ),
			'wc-action-queue-test',
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
			'wc_action_queue_test_default',
			array(),
			'wc-action-queue-test'
		);

		$this->assertSame( 10, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should schedule an immediate action at the requested priority.
	 */
	public function test_add_with_priority_schedules_now_with_priority(): void {
		$before = time();

		$action_id = $this->sut->add_with_priority( 'wc_action_queue_test_add', array(), 'wc-action-queue-test', 3 );

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
			'wc_action_queue_test_recurring',
			array(),
			'wc-action-queue-test',
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
			'wc_action_queue_test_cron',
			array(),
			'wc-action-queue-test',
			25
		);

		$this->assertSame( 25, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * Third-party queues subclass WC_Action_Queue and override these methods with their
	 * original signatures. Appending a parameter to any of them is a fatal error for those
	 * subclasses, which is why the priority variants are separate methods.
	 *
	 * @testdox Should not add parameters to the pre-existing scheduling methods.
	 * @testWith ["add", 3]
	 *           ["schedule_single", 4]
	 *           ["schedule_recurring", 5]
	 *           ["schedule_cron", 5]
	 *
	 * @param string $method         The method name.
	 * @param int    $expected_count The number of parameters the method has always had.
	 */
	public function test_existing_scheduling_methods_keep_their_signatures( string $method, int $expected_count ): void {
		$reflection = new ReflectionMethod( WC_Action_Queue::class, $method );

		$this->assertCount(
			$expected_count,
			$reflection->getParameters(),
			"WC_Action_Queue::{$method}() is overridden by third-party queues; changing its signature fatals them."
		);
	}
}
