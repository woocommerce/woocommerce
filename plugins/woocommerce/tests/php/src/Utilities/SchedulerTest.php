<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Enums\SchedulerQueue;
use Automattic\WooCommerce\Utilities\Scheduler;
use WC_Unit_Test_Case;

/**
 * Tests for the Scheduler class.
 */
class SchedulerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Scheduler
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( Scheduler::class );
	}

	/**
	 * Build a plain queue double that records every call and answers get_next() with a fixed value.
	 *
	 * @param mixed $next What get_next() returns. The base interface has no return type, so this may be false.
	 * @return \WC_Queue_Interface
	 */
	private function plain_queue( $next = null ): \WC_Queue_Interface {
		return new class( $next ) implements \WC_Queue_Interface {
			// phpcs:disable Squiz.Commenting.FunctionComment.Missing, Squiz.Commenting.VariableComment.Missing
			public $calls = array();
			private $next;
			public function __construct( $next ) {
				$this->next = $next;
			}
			public function add( $hook, $args = array(), $group = '' ) {
				$this->calls[] = array( 'add', func_get_args() );
				return 11;
			}
			public function schedule_single( $timestamp, $hook, $args = array(), $group = '' ) {
				$this->calls[] = array( 'schedule_single', func_get_args() );
				return 12;
			}
			public function schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '' ) {
				$this->calls[] = array( 'schedule_recurring', func_get_args() );
				return 13;
			}
			public function schedule_cron( $timestamp, $cron_schedule, $hook, $args = array(), $group = '' ) {
				$this->calls[] = array( 'schedule_cron', func_get_args() );
				return 14;
			}
			public function cancel( $hook, $args = array(), $group = '' ) {
				$this->calls[] = array( 'cancel', func_get_args() );
			}
			public function cancel_all( $hook, $args = array(), $group = '' ) {
				$this->calls[] = array( 'cancel_all', func_get_args() );
			}
			public function get_next( $hook, $args = null, $group = '' ) {
				$this->calls[] = array( 'get_next', func_get_args() );
				return $this->next;
			}
			public function search( $args = array(), $return_format = OBJECT ) {
				$this->calls[] = array( 'search', func_get_args() );
				return array( 'searched' );
			}
			// phpcs:enable
		};
	}

	/**
	 * Build an options-aware queue double that records every call, including the options it receives.
	 *
	 * @return \WC_Options_Aware_Queue_Interface
	 */
	private function options_aware_queue(): \WC_Options_Aware_Queue_Interface {
		return new class() implements \WC_Options_Aware_Queue_Interface {
			// phpcs:disable Squiz.Commenting.FunctionComment.Missing, Squiz.Commenting.VariableComment.Missing
			public $calls = array();
			public function add( $hook, $args = array(), $group = '', $options = array() ) {
				$this->calls[] = array( 'add', func_get_args() );
				return 21;
			}
			public function schedule_single( $timestamp, $hook, $args = array(), $group = '', $options = array() ) {
				$this->calls[] = array( 'schedule_single', func_get_args() );
				return 22;
			}
			public function schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', $options = array() ) {
				$this->calls[] = array( 'schedule_recurring', func_get_args() );
				return 23;
			}
			public function schedule_cron( $timestamp, $cron_schedule, $hook, $args = array(), $group = '', $options = array() ) {
				$this->calls[] = array( 'schedule_cron', func_get_args() );
				return 24;
			}
			public function has_scheduled_action( $hook, $args = null, $group = '' ) {
				$this->calls[] = array( 'has_scheduled_action', func_get_args() );
				return true;
			}
			public function cancel( $hook, $args = array(), $group = '' ) {
				$this->calls[] = array( 'cancel', func_get_args() );
			}
			public function cancel_all( $hook, $args = array(), $group = '' ) {
				$this->calls[] = array( 'cancel_all', func_get_args() );
			}
			public function get_next( $hook, $args = null, $group = '' ) {
				$this->calls[] = array( 'get_next', func_get_args() );
				return null;
			}
			public function search( $args = array(), $return_format = OBJECT ) {
				$this->calls[] = array( 'search', func_get_args() );
				return array();
			}
			// phpcs:enable
		};
	}

	/**
	 * Make the given queue the active one.
	 *
	 * @param \WC_Queue_Interface $queue The queue double.
	 */
	private function use_queue( \WC_Queue_Interface $queue ): void {
		$this->register_legacy_proxy_class_mocks( array( \WC_Queue_Interface::class => $queue ) );
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
	 * @testdox Should pass priority and unique to an options-aware queue and never the queue key.
	 */
	public function test_passes_options_to_an_options_aware_queue(): void {
		$queue = $this->options_aware_queue();
		$this->use_queue( $queue );

		$action_id = $this->sut->schedule_single(
			123,
			'wc_scheduler_test_hook',
			array( 'a' => 1 ),
			'wc-scheduler-test',
			array(
				'priority' => 3,
				'unique'   => true,
				'queue'    => SchedulerQueue::ACTIVE,
			)
		);

		$this->assertSame( 22, $action_id );
		$this->assertCount( 1, $queue->calls );
		$this->assertSame( 'schedule_single', $queue->calls[0][0] );
		$this->assertSame(
			array(
				123,
				'wc_scheduler_test_hook',
				array( 'a' => 1 ),
				'wc-scheduler-test',
				array(
					'priority' => 3,
					'unique'   => true,
				),
			),
			$queue->calls[0][1]
		);
	}

	/**
	 * @testdox Should call the plain four-argument methods on a plain queue and drop the options.
	 */
	public function test_calls_plain_methods_on_a_plain_queue(): void {
		$queue = $this->plain_queue();
		$this->use_queue( $queue );

		$single    = $this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array( 'a' => 1 ), 'wc-scheduler-test', array( 'priority' => 1 ) );
		$recurring = $this->sut->schedule_recurring( 123, 60, 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', array( 'priority' => 1 ) );
		$cron      = $this->sut->schedule_cron( 123, '0 0 * * *', 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', array( 'priority' => 1 ) );

		$this->assertSame( array( 12, 13, 14 ), array( $single, $recurring, $cron ) );
		$this->assertSame( array( 'schedule_single', 'schedule_recurring', 'schedule_cron' ), array_column( $queue->calls, 0 ) );
		$this->assertSame( array( 123, 'wc_scheduler_test_hook', array( 'a' => 1 ), 'wc-scheduler-test' ), $queue->calls[0][1], 'A plain queue must receive exactly the base interface arguments' );
		$this->assertCount( 5, $queue->calls[1][1] );
		$this->assertCount( 5, $queue->calls[2][1] );
	}

	/**
	 * @testdox Should emulate uniqueness on a plain queue by returning 0 when get_next() reports a pending match.
	 */
	public function test_emulates_unique_on_a_plain_queue_with_a_pending_match(): void {
		$queue = $this->plain_queue( new \WC_DateTime( '@' . ( time() + 60 ) ) );
		$this->use_queue( $queue );

		$action_id = $this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array( 'a' => 1 ), 'wc-scheduler-test', array( 'unique' => true ) );

		$this->assertSame( 0, $action_id );
		$this->assertSame( array( 'get_next' ), array_column( $queue->calls, 0 ), 'Nothing should be scheduled when a match is pending' );
		$this->assertSame( array( 'wc_scheduler_test_hook', array( 'a' => 1 ), 'wc-scheduler-test' ), $queue->calls[0][1] );
	}

	/**
	 * @testdox Should schedule a unique action on a plain queue when get_next() reports no match.
	 */
	public function test_schedules_unique_on_a_plain_queue_without_a_match(): void {
		$queue = $this->plain_queue( null );
		$this->use_queue( $queue );

		$action_id = $this->sut->add( 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', array( 'unique' => true ) );

		$this->assertSame( 12, $action_id, 'add() delegates to schedule_single() at the current time' );
		$this->assertSame( array( 'get_next', 'schedule_single' ), array_column( $queue->calls, 0 ) );
	}

	/**
	 * A custom queue may answer false from get_next() when nothing is scheduled, as wp_next_scheduled() does.
	 *
	 * @testdox Should treat only a WC_DateTime from get_next() as a scheduled action on a plain queue.
	 */
	public function test_only_a_datetime_from_get_next_counts_as_scheduled(): void {
		$queue = $this->plain_queue( false );
		$this->use_queue( $queue );

		$action_id = $this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', array( 'unique' => true ) );

		$this->assertSame( 12, $action_id, 'A false from get_next() must not block a unique action' );
		$this->assertFalse( $this->sut->has_scheduled_action( 'wc_scheduler_test_hook' ) );
	}

	/**
	 * @testdox Should not consult get_next() on a plain queue when unique is not requested.
	 */
	public function test_does_not_check_get_next_when_not_unique(): void {
		$queue = $this->plain_queue( new \WC_DateTime( '@' . ( time() + 60 ) ) );
		$this->use_queue( $queue );

		$this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), 'wc-scheduler-test' );

		$this->assertSame( array( 'schedule_single' ), array_column( $queue->calls, 0 ) );
	}

	/**
	 * @testdox Should bypass the active queue and store a real action when the queue option selects the default queue.
	 */
	public function test_default_queue_option_bypasses_the_active_queue(): void {
		$queue = $this->plain_queue();
		$this->use_queue( $queue );

		$action_id = $this->sut->schedule_single(
			time() + HOUR_IN_SECONDS,
			'wc_scheduler_test_default',
			array(),
			'wc-scheduler-test',
			array(
				'priority' => 4,
				'queue'    => SchedulerQueue::DEFAULT,
			)
		);

		$this->assertGreaterThan( 0, $action_id );
		$this->assertSame( 4, $this->get_stored_priority( $action_id ) );
		$this->assertSame( array(), $queue->calls, 'The active queue must not see a call routed to the default queue' );
	}

	/**
	 * Overflowing values must be clamped before the int cast: cast first, a float above PHP_INT_MAX
	 * wraps to PHP_INT_MIN and lands at 0, the highest priority, instead of 255.
	 *
	 * @testdox Should clamp the priority to 0-255 and fall back to 10 for non-numeric or non-finite values.
	 * @testWith [-5, 0]
	 *           [300, 255]
	 *           [9223372036854775808, 255]
	 *           ["1e309", 10]
	 *           ["7", 7]
	 *           [3.9, 3]
	 *           [null, 10]
	 *           ["high", 10]
	 *
	 * @param mixed $requested The priority given by the caller.
	 * @param int   $expected  The priority the queue should receive.
	 */
	public function test_normalizes_priority( $requested, int $expected ): void {
		$queue = $this->options_aware_queue();
		$this->use_queue( $queue );

		$this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), '', array( 'priority' => $requested ) );

		$this->assertSame( $expected, $queue->calls[0][1][4]['priority'] );
	}

	/**
	 * @testdox Should default to priority 10, non-unique, and drop unknown option keys.
	 */
	public function test_default_options(): void {
		$queue = $this->options_aware_queue();
		$this->use_queue( $queue );

		$this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), '', array( 'mystery' => true ) );

		$this->assertSame(
			array(
				'priority' => 10,
				'unique'   => false,
			),
			$queue->calls[0][1][4]
		);
	}

	/**
	 * @testdox Should fall back to the active queue and raise a notice for an unknown queue option value.
	 */
	public function test_unknown_queue_option_falls_back_to_active(): void {
		$queue = $this->options_aware_queue();
		$this->use_queue( $queue );
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Utilities\Scheduler::schedule_single' );

		$action_id = $this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), '', array( 'queue' => 'elsewhere' ) );

		$this->assertSame( 22, $action_id, 'The call should reach the active queue' );
	}

	/**
	 * @testdox Should pass cancel, cancel_all, get_next and search straight through to the selected queue.
	 */
	public function test_passthrough_methods(): void {
		$next  = new \WC_DateTime( '@' . ( time() + 60 ) );
		$queue = $this->plain_queue( $next );
		$this->use_queue( $queue );

		$this->sut->cancel( 'wc_scheduler_test_hook', array( 'a' => 1 ), 'wc-scheduler-test' );
		$this->sut->cancel_all( 'wc_scheduler_test_hook', array(), 'wc-scheduler-test' );
		$got_next = $this->sut->get_next( 'wc_scheduler_test_hook', null, 'wc-scheduler-test' );
		$results  = $this->sut->search( array( 'hook' => 'wc_scheduler_test_hook' ), 'ids' );

		$this->assertSame( $next, $got_next );
		$this->assertSame( array( 'searched' ), $results );
		$this->assertSame(
			array(
				array( 'cancel', array( 'wc_scheduler_test_hook', array( 'a' => 1 ), 'wc-scheduler-test' ) ),
				array( 'cancel_all', array( 'wc_scheduler_test_hook', array(), 'wc-scheduler-test' ) ),
				array( 'get_next', array( 'wc_scheduler_test_hook', null, 'wc-scheduler-test' ) ),
				array( 'search', array( array( 'hook' => 'wc_scheduler_test_hook' ), 'ids' ) ),
			),
			$queue->calls
		);
	}

	/**
	 * @testdox Should answer has_scheduled_action() from get_next() on a plain queue and from the queue itself when options-aware.
	 */
	public function test_has_scheduled_action(): void {
		$this->use_queue( $this->plain_queue( new \WC_DateTime( '@' . ( time() + 60 ) ) ) );
		$this->assertTrue( $this->sut->has_scheduled_action( 'wc_scheduler_test_hook' ) );

		$this->use_queue( $this->plain_queue( null ) );
		$this->assertFalse( $this->sut->has_scheduled_action( 'wc_scheduler_test_hook' ) );

		$aware = $this->options_aware_queue();
		$this->use_queue( $aware );
		$this->assertTrue( $this->sut->has_scheduled_action( 'wc_scheduler_test_hook', array( 'a' => 1 ), 'g' ) );
		$this->assertSame( array( array( 'has_scheduled_action', array( 'wc_scheduler_test_hook', array( 'a' => 1 ), 'g' ) ) ), $aware->calls );
	}

	/**
	 * @testdox Should report a real pending action through the stock queue.
	 */
	public function test_has_scheduled_action_on_the_stock_queue(): void {
		$this->assertFalse( $this->sut->has_scheduled_action( 'wc_scheduler_test_stock', array( 'a' => 1 ), 'wc-scheduler-test' ) );

		$this->sut->schedule_single( time() + HOUR_IN_SECONDS, 'wc_scheduler_test_stock', array( 'a' => 1 ), 'wc-scheduler-test' );

		$this->assertTrue( $this->sut->has_scheduled_action( 'wc_scheduler_test_stock', array( 'a' => 1 ), 'wc-scheduler-test' ) );
	}

	/**
	 * @testdox Should report which options take effect on the active path.
	 */
	public function test_supports(): void {
		$this->assertTrue( $this->sut->supports( 'priority' ), 'The stock queue on the bundled Action Scheduler supports priority' );
		$this->assertTrue( $this->sut->supports( 'unique' ) );
		$this->assertFalse( $this->sut->supports( 'mystery' ) );

		$this->use_queue( $this->options_aware_queue() );
		$this->assertTrue( $this->sut->supports( 'priority' ) );
		$this->assertTrue( $this->sut->supports( 'unique' ) );

		$this->use_queue( $this->plain_queue() );
		$this->assertFalse( $this->sut->supports( 'priority' ), 'A plain queue drops the priority' );
		$this->assertTrue( $this->sut->supports( 'unique' ), 'Uniqueness is emulated on a plain queue' );
		$this->assertTrue( $this->sut->supports( 'priority', array( 'queue' => SchedulerQueue::DEFAULT ) ), 'The default queue supports priority even when a plain queue is active' );
	}

	/**
	 * @testdox Should report the loaded Action Scheduler version.
	 */
	public function test_get_version(): void {
		$version = $this->sut->get_version();

		$this->assertIsString( $version );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+/', $version );
	}

	/**
	 * @testdox Should be ready in the test environment, whichever kind of queue is active.
	 */
	public function test_is_ready(): void {
		$this->assertTrue( $this->sut->is_ready(), 'Stock queue: Action Scheduler is initialised in the test environment' );

		$this->use_queue( $this->plain_queue() );
		$this->assertTrue( $this->sut->is_ready(), 'A queue that does not extend WC_Action_Queue only needs plugins_loaded' );

		$this->use_queue( new class() extends \WC_Action_Queue {} );
		$this->assertTrue( $this->sut->is_ready(), 'A WC_Action_Queue subclass is checked against Action Scheduler, which is initialised here' );
	}
}
