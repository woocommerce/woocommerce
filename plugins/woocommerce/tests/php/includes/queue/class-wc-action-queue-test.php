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
	 * @testdox Should schedule non-unique actions, so repeat calls do not collapse into one.
	 */
	public function test_priority_methods_schedule_non_unique_actions(): void {
		$timestamp = time() + HOUR_IN_SECONDS;

		$first  = $this->sut->schedule_single_with_priority( $timestamp, 'wc_action_queue_test_unique', array(), 'wc-action-queue-test', 5 );
		$second = $this->sut->schedule_single_with_priority( $timestamp, 'wc_action_queue_test_unique', array(), 'wc-action-queue-test', 5 );

		$this->assertNotSame( 0, $second, 'Action Scheduler returns 0 when a unique action is rejected as a duplicate' );
		$this->assertNotEquals( $first, $second, 'Identical calls should produce two separate actions, as the plain queue methods do' );
	}

	/**
	 * Third-party queues override the classic scheduling methods to intercept everything the
	 * queue schedules. The priority methods have to route through them rather than around them.
	 *
	 * @testdox Should route through the overridable scheduling methods a subclass may have replaced.
	 */
	public function test_priority_methods_go_through_overridable_methods(): void {
		// Anonymous so an incompatible signature would fail this test alone, not the whole suite.
		$subclass = new class() extends WC_Action_Queue {
			/**
			 * Hooks intercepted by this override.
			 *
			 * @var array
			 */
			public $intercepted = array();

			/**
			 * Record the call instead of scheduling anything.
			 *
			 * @param int    $timestamp When the job would run.
			 * @param string $hook The hook to trigger.
			 * @param array  $args Arguments to pass when the hook triggers.
			 * @param string $group The group to assign this job to.
			 * @return int
			 */
			public function schedule_single( $timestamp, $hook, $args = array(), $group = '' ) {
				$this->intercepted[] = $hook;
				return 0;
			}
		};

		$subclass->schedule_single_with_priority( time() + HOUR_IN_SECONDS, 'intercepted_single', array(), 'wc-action-queue-test', 5 );
		$subclass->add_with_priority( 'intercepted_add', array(), 'wc-action-queue-test', 5 );

		$this->assertSame(
			array( 'intercepted_single', 'intercepted_add' ),
			$subclass->intercepted,
			'The priority methods must not bypass a subclass override of schedule_single()'
		);
	}

	/**
	 * @testdox Should leave the default priority in place after a priority-scoped call.
	 */
	public function test_priority_does_not_leak_into_later_calls(): void {
		$this->sut->schedule_single_with_priority( time() + HOUR_IN_SECONDS, 'wc_action_queue_test_leak', array( 'first' => true ), 'wc-action-queue-test', 1 );

		$action_id = $this->sut->schedule_single( time() + HOUR_IN_SECONDS, 'wc_action_queue_test_leak', array( 'second' => true ), 'wc-action-queue-test' );

		$this->assertSame( 10, $this->get_stored_priority( $action_id ) );
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
