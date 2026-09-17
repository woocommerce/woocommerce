<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Queue;

use Automattic\WooCommerce\Queue\OptionsAwareActionQueue;
use Automattic\WooCommerce\Queue\OptionsAwareQueueInterface;
use WC_Unit_Test_Case;

/**
 * Tests for the OptionsAwareActionQueue class.
 */
class OptionsAwareActionQueueTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OptionsAwareActionQueue
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OptionsAwareActionQueue();
	}

	/**
	 * Read the stored Action Scheduler priority of an action.
	 *
	 * @param int $action_id The action ID.
	 * @return int
	 */
	private function get_stored_priority( int $action_id ): int {
		return \ActionScheduler::store()->fetch_action( (string) $action_id )->get_priority();
	}

	/**
	 * Build a queue that believes an Action Scheduler copy predating the unique argument is loaded.
	 *
	 * @return OptionsAwareActionQueue
	 */
	private function queue_on_old_action_scheduler(): OptionsAwareActionQueue {
		return new class() extends OptionsAwareActionQueue {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			protected function get_action_scheduler_version(): ?string {
				return '3.4.0';
			}
		};
	}

	/**
	 * @testdox Should be usable as both a plain queue and an options-aware queue.
	 */
	public function test_implements_both_queue_interfaces(): void {
		$this->assertInstanceOf( \WC_Queue_Interface::class, $this->sut );
		$this->assertInstanceOf( \WC_Action_Queue::class, $this->sut );
		$this->assertInstanceOf( OptionsAwareQueueInterface::class, $this->sut );
	}

	/**
	 * @testdox Should be the queue WC()->queue() returns by default.
	 */
	public function test_is_the_default_queue(): void {
		$this->assertInstanceOf( OptionsAwareActionQueue::class, WC()->queue() );
	}

	/**
	 * Third-party queues that extend WC_Action_Queue must not pass the capability check, so
	 * that the Scheduler falls back to the plain methods those queues override.
	 *
	 * @testdox Should not make plain WC_Action_Queue subclasses options-aware.
	 */
	public function test_plain_action_queue_is_not_options_aware(): void {
		$this->assertNotInstanceOf( OptionsAwareQueueInterface::class, new \WC_Action_Queue() );
	}

	/**
	 * Overflowing values must be clamped before the int cast: cast first, a float above PHP_INT_MAX
	 * wraps to PHP_INT_MIN and lands at 0, the highest priority, instead of 255. Non-numeric values
	 * must fall back to 10 rather than cast to 0.
	 *
	 * @testdox Should store the requested priority clamped to 0-255, falling back to 10 for non-numeric or non-finite values.
	 * @testWith [1, 1]
	 *           [10, 10]
	 *           [200, 200]
	 *           [-5, 0]
	 *           [300, 255]
	 *           [9223372036854775808, 255]
	 *           ["1e309", 10]
	 *           ["7", 7]
	 *           [3.9, 3]
	 *           [null, 10]
	 *           ["high", 10]
	 *
	 * @param mixed $requested_priority The priority passed to the queue.
	 * @param int   $expected_priority  The priority Action Scheduler is expected to store.
	 */
	public function test_schedule_single_stores_priority( $requested_priority, int $expected_priority ): void {
		$action_id = $this->sut->schedule_single(
			time() + HOUR_IN_SECONDS,
			'wc_oaq_test_single',
			array( 'priority' => is_scalar( $requested_priority ) ? $requested_priority : 'none' ),
			'wc-oaq-test',
			array( 'priority' => $requested_priority )
		);

		$this->assertSame( $expected_priority, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * The base interface has no options parameter, so a stray extra argument was silently discarded
	 * before this class became the default queue. It must not become a type error now.
	 *
	 * @testdox Should ignore a non-array extra argument and schedule at the default priority.
	 */
	public function test_ignores_a_non_array_options_argument(): void {
		$action_id = $this->sut->schedule_single( time() + HOUR_IN_SECONDS, 'wc_oaq_test_stray', array(), 'wc-oaq-test', true );

		$this->assertGreaterThan( 0, $action_id );
		$this->assertSame( 10, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should default to priority 10 when no options are given.
	 */
	public function test_defaults_to_priority_ten(): void {
		$action_id = $this->sut->schedule_single( time() + HOUR_IN_SECONDS, 'wc_oaq_test_default', array(), 'wc-oaq-test' );

		$this->assertSame( 10, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should schedule an immediate action at the requested priority.
	 */
	public function test_add_schedules_now_with_priority(): void {
		$before = time();

		$action_id = $this->sut->add( 'wc_oaq_test_add', array(), 'wc-oaq-test', array( 'priority' => 3 ) );

		$action = \ActionScheduler::store()->fetch_action( (string) $action_id );
		$this->assertSame( 3, $action->get_priority() );
		$this->assertGreaterThanOrEqual( $before, $action->get_schedule()->get_date()->getTimestamp(), 'add() should schedule for now, not the future' );
	}

	/**
	 * @testdox Should store the requested priority on recurring actions.
	 */
	public function test_schedule_recurring_stores_priority(): void {
		$action_id = $this->sut->schedule_recurring( time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'wc_oaq_test_recurring', array(), 'wc-oaq-test', array( 'priority' => 25 ) );

		$this->assertSame( 25, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should store the requested priority on cron actions.
	 */
	public function test_schedule_cron_stores_priority(): void {
		$action_id = $this->sut->schedule_cron( time() + HOUR_IN_SECONDS, '0 0 * * *', 'wc_oaq_test_cron', array(), 'wc-oaq-test', array( 'priority' => 25 ) );

		$this->assertSame( 25, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should schedule non-unique actions by default, so repeat calls do not collapse into one.
	 */
	public function test_non_unique_by_default(): void {
		$timestamp = time() + HOUR_IN_SECONDS;

		$first  = $this->sut->schedule_single( $timestamp, 'wc_oaq_test_non_unique', array(), 'wc-oaq-test' );
		$second = $this->sut->schedule_single( $timestamp, 'wc_oaq_test_non_unique', array(), 'wc-oaq-test' );

		$this->assertGreaterThan( 0, $second, 'Action Scheduler returns 0 when a unique action is rejected as a duplicate' );
		$this->assertNotEquals( $first, $second, 'Identical calls should produce two separate actions' );
	}

	/**
	 * @testdox Should skip a unique action and return 0 when a matching action is already pending.
	 */
	public function test_unique_action_is_skipped_when_a_match_is_pending(): void {
		$timestamp = time() + HOUR_IN_SECONDS;
		$options   = array(
			'priority' => 5,
			'unique'   => true,
		);

		$first  = $this->sut->schedule_single( $timestamp, 'wc_oaq_test_unique', array( 'id' => 1 ), 'wc-oaq-test', $options );
		$second = $this->sut->schedule_single( $timestamp, 'wc_oaq_test_unique', array( 'id' => 1 ), 'wc-oaq-test', $options );

		$this->assertGreaterThan( 0, $first );
		$this->assertSame( 0, $second, 'A unique action with the same hook, args and group as a pending one should be skipped' );
		$this->assertSame( 5, $this->get_stored_priority( $first ) );
	}

	/**
	 * @testdox Should schedule a unique action when the pending one has different args.
	 */
	public function test_unique_action_is_scheduled_when_args_differ(): void {
		$timestamp = time() + HOUR_IN_SECONDS;
		$options   = array( 'unique' => true );

		$first  = $this->sut->schedule_single( $timestamp, 'wc_oaq_test_unique_args', array( 'id' => 1 ), 'wc-oaq-test', $options );
		$second = $this->sut->schedule_single( $timestamp, 'wc_oaq_test_unique_args', array( 'id' => 2 ), 'wc-oaq-test', $options );

		$this->assertGreaterThan( 0, $first );
		$this->assertGreaterThan( 0, $second );
		$this->assertNotEquals( $first, $second );
	}

	/**
	 * @testdox Should honour the unique option on immediate, recurring and cron actions too.
	 */
	public function test_unique_applies_to_every_scheduling_method(): void {
		$timestamp = time() + HOUR_IN_SECONDS;
		$options   = array( 'unique' => true );

		$this->assertGreaterThan( 0, $this->sut->add( 'wc_oaq_test_unique_add', array(), 'wc-oaq-test', $options ) );
		$this->assertSame( 0, $this->sut->add( 'wc_oaq_test_unique_add', array(), 'wc-oaq-test', $options ) );

		$this->assertGreaterThan( 0, $this->sut->schedule_recurring( $timestamp, DAY_IN_SECONDS, 'wc_oaq_test_unique_recurring', array(), 'wc-oaq-test', $options ) );
		$this->assertSame( 0, $this->sut->schedule_recurring( $timestamp, DAY_IN_SECONDS, 'wc_oaq_test_unique_recurring', array(), 'wc-oaq-test', $options ) );

		$this->assertGreaterThan( 0, $this->sut->schedule_cron( $timestamp, '0 0 * * *', 'wc_oaq_test_unique_cron', array(), 'wc-oaq-test', $options ) );
		$this->assertSame( 0, $this->sut->schedule_cron( $timestamp, '0 0 * * *', 'wc_oaq_test_unique_cron', array(), 'wc-oaq-test', $options ) );
	}

	/**
	 * @testdox Should report whether a matching action is pending.
	 */
	public function test_has_scheduled_action(): void {
		$this->assertFalse( $this->sut->has_scheduled_action( 'wc_oaq_test_has', array( 'id' => 1 ), 'wc-oaq-test' ) );

		$this->sut->schedule_single( time() + HOUR_IN_SECONDS, 'wc_oaq_test_has', array( 'id' => 1 ), 'wc-oaq-test' );

		$this->assertTrue( $this->sut->has_scheduled_action( 'wc_oaq_test_has', array( 'id' => 1 ), 'wc-oaq-test' ) );
		$this->assertTrue( $this->sut->has_scheduled_action( 'wc_oaq_test_has', null, 'wc-oaq-test' ), 'Null args should match any args' );
		$this->assertFalse( $this->sut->has_scheduled_action( 'wc_oaq_test_has', array( 'id' => 2 ), 'wc-oaq-test' ) );
	}

	/**
	 * @testdox Should report support for unique and priority on the bundled Action Scheduler.
	 */
	public function test_reports_support_on_bundled_action_scheduler(): void {
		$this->assertTrue( $this->sut->supports( 'unique' ) );
		$this->assertTrue( $this->sut->supports( 'priority' ) );
		$this->assertFalse( $this->sut->supports( 'mystery' ), 'Unknown options are never supported' );
		$this->assertFalse( $this->queue_on_old_action_scheduler()->supports( 'unique' ) );
		$this->assertFalse( $this->queue_on_old_action_scheduler()->supports( 'priority' ) );
	}

	/**
	 * @testdox Should be ready on the bundled Action Scheduler once it is initialised.
	 */
	public function test_is_ready_on_bundled_action_scheduler(): void {
		$this->assertTrue( $this->sut->is_ready() );
	}

	/**
	 * Below Action Scheduler 3.5.0 the unique argument does not exist, so the queue has to check for a
	 * pending match itself before scheduling.
	 *
	 * @testdox Should emulate uniqueness when the loaded Action Scheduler predates the unique argument.
	 */
	public function test_emulates_uniqueness_on_old_action_scheduler(): void {
		$sut       = $this->queue_on_old_action_scheduler();
		$timestamp = time() + HOUR_IN_SECONDS;
		$options   = array( 'unique' => true );

		$first     = $sut->schedule_single( $timestamp, 'wc_oaq_test_emulated', array( 'id' => 1 ), 'wc-oaq-test', $options );
		$duplicate = $sut->schedule_single( $timestamp, 'wc_oaq_test_emulated', array( 'id' => 1 ), 'wc-oaq-test', $options );
		$other     = $sut->schedule_single( $timestamp, 'wc_oaq_test_emulated', array( 'id' => 2 ), 'wc-oaq-test', $options );

		$this->assertGreaterThan( 0, $first );
		$this->assertSame( 0, $duplicate, 'The emulation should skip a unique action that matches a pending one' );
		$this->assertGreaterThan( 0, $other, 'The emulation should let a unique action with different args through' );
	}

	/**
	 * @testdox Should count matching actions in the store without hydrating them.
	 */
	public function test_count_matches_the_store(): void {
		$timestamp = time() + HOUR_IN_SECONDS;
		$this->sut->schedule_single( $timestamp, 'wc_oaq_test_count', array( 'n' => 1 ), 'wc-oaq-count' );
		$this->sut->schedule_single( $timestamp, 'wc_oaq_test_count', array( 'n' => 2 ), 'wc-oaq-count' );
		$this->sut->schedule_single( $timestamp, 'wc_oaq_test_count_other', array(), 'wc-oaq-count' );

		$this->assertSame( 3, $this->sut->count( array( 'group' => 'wc-oaq-count' ) ) );
		$this->assertSame(
			2,
			$this->sut->count(
				array(
					'hook'  => 'wc_oaq_test_count',
					'group' => 'wc-oaq-count',
				)
			)
		);
		$this->assertSame( 0, $this->sut->count( array( 'hook' => 'wc_oaq_test_count_missing' ) ) );
		$this->assertSame( count( $this->sut->search( array( 'group' => 'wc-oaq-count' ), 'ids' ) ), $this->sut->count( array( 'group' => 'wc-oaq-count' ) ), 'count() agrees with an ID search' );
	}
}
