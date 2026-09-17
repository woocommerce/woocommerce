<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Queue;

use Automattic\WooCommerce\Enums\QueueCapability;
use Automattic\WooCommerce\Enums\SchedulerQueue;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Queue\OptionsAwareActionQueue;
use Automattic\WooCommerce\Queue\OptionsAwareQueueInterface;
use Automattic\WooCommerce\Queue\Scheduler;
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
	 * @param string[] $unsupported Options the double reports as unsupported.
	 * @param bool     $ready       What is_ready() reports.
	 * @return OptionsAwareQueueInterface
	 */
	private function options_aware_queue( array $unsupported = array(), bool $ready = true ): OptionsAwareQueueInterface {
		return new class( $unsupported, $ready ) implements OptionsAwareQueueInterface {
			// phpcs:disable Squiz.Commenting.FunctionComment.Missing, Squiz.Commenting.VariableComment.Missing
			public $calls = array();
			private $unsupported;
			private $ready;
			public function __construct( array $unsupported, bool $ready ) {
				$this->unsupported = $unsupported;
				$this->ready       = $ready;
			}
			public function supports( $option ) {
				return in_array( $option, array( 'priority', 'unique' ), true ) && ! in_array( $option, $this->unsupported, true );
			}
			public function is_ready() {
				return $this->ready;
			}
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
	 * Build a stock queue that believes an Action Scheduler copy predating both options is loaded.
	 *
	 * @return OptionsAwareActionQueue
	 */
	private function stock_queue_on_old_action_scheduler(): OptionsAwareActionQueue {
		return new class() extends OptionsAwareActionQueue {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			protected function get_action_scheduler_version(): ?string {
				return '3.4.0';
			}
		};
	}

	/**
	 * Build a stock queue that reports Action Scheduler as not ready.
	 *
	 * @return OptionsAwareActionQueue
	 */
	private function not_ready_stock_queue(): OptionsAwareActionQueue {
		return new class() extends OptionsAwareActionQueue {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function is_ready(): bool {
				return false;
			}
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
	 * Build a scheduler whose stock queue is the given one, for the paths that bypass the active queue.
	 *
	 * @param OptionsAwareActionQueue $default_queue The stock queue to inject.
	 * @return Scheduler
	 */
	private function scheduler_with_default_queue( OptionsAwareActionQueue $default_queue ): Scheduler {
		$scheduler = new Scheduler();
		$scheduler->init( wc_get_container()->get( LegacyProxy::class ), $default_queue );

		return $scheduler;
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
	 * @testdox Should pass priority and unique to an options-aware queue and never the scheduler's own keys.
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
				'strict'   => true,
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

		$this->assertSame( 11, $action_id, 'add() calls the queue\'s own add() after the pending-action check' );
		$this->assertSame( array( 'get_next', 'add' ), array_column( $queue->calls, 0 ) );
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
	 * The queue owns validation and defaults, so the scheduler must not touch the value.
	 *
	 * @testdox Should forward the priority to an options-aware queue exactly as the caller gave it.
	 * @testWith [300]
	 *           ["high"]
	 *           [null]
	 *           [3.9]
	 *
	 * @param mixed $requested The priority given by the caller.
	 */
	public function test_forwards_priority_as_given( $requested ): void {
		$queue = $this->options_aware_queue();
		$this->use_queue( $queue );

		$this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), '', array( 'priority' => $requested ) );

		$this->assertSame( $requested, $queue->calls[0][1][4]['priority'] );
	}

	/**
	 * @testdox Should forward only unique when no priority is given, and drop unknown option keys.
	 */
	public function test_default_options(): void {
		$queue = $this->options_aware_queue();
		$this->use_queue( $queue );

		$this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), '', array( 'mystery' => true ) );

		$this->assertSame( array( 'unique' => false ), $queue->calls[0][1][4], 'No priority key means the queue applies its own default' );
	}

	/**
	 * @testdox Should let the stock queue clamp a priority that reaches it through the scheduler.
	 */
	public function test_stock_queue_clamps_priority_end_to_end(): void {
		$action_id = $this->sut->schedule_single( time() + HOUR_IN_SECONDS, 'wc_scheduler_test_clamp', array(), 'wc-scheduler-test', array( 'priority' => 300 ) );

		$this->assertSame( 255, $this->get_stored_priority( $action_id ) );
	}

	/**
	 * @testdox Should fall back to the active queue and raise a notice for an unknown queue option value.
	 */
	public function test_unknown_queue_option_falls_back_to_active(): void {
		$queue = $this->options_aware_queue();
		$this->use_queue( $queue );
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Queue\Scheduler::schedule_single' );

		$action_id = $this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), '', array( 'queue' => 'elsewhere' ) );

		$this->assertSame( 22, $action_id, 'The call should reach the active queue' );
	}

	/**
	 * @testdox Should list every queue option value in SchedulerQueue::get_all().
	 */
	public function test_scheduler_queue_enum_lists_every_value(): void {
		$this->assertSame( array( SchedulerQueue::ACTIVE, SchedulerQueue::DEFAULT ), SchedulerQueue::get_all() );
	}

	/**
	 * @testdox Should list every capability in QueueCapability::get_all().
	 */
	public function test_queue_capability_enum_lists_every_value(): void {
		$this->assertSame( array( QueueCapability::PRIORITY, QueueCapability::UNIQUE ), QueueCapability::get_all() );
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
	 * @testdox Should report native support only: both options on the stock and options-aware queues, neither on a plain queue.
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
		$this->assertFalse( $this->sut->supports( 'unique' ), 'A plain queue only gets emulated uniqueness, which is not native support' );
		$this->assertTrue( $this->sut->supports( 'priority', array( 'queue' => SchedulerQueue::DEFAULT ) ), 'The default queue supports priority even when a plain queue is active' );
	}

	/**
	 * @testdox Should report no native support on a stock queue whose Action Scheduler predates both options.
	 */
	public function test_supports_follows_the_action_scheduler_version(): void {
		$scheduler = $this->scheduler_with_default_queue( $this->stock_queue_on_old_action_scheduler() );

		$this->assertFalse( $scheduler->supports( 'unique', array( 'queue' => SchedulerQueue::DEFAULT ) ) );
		$this->assertFalse( $scheduler->supports( 'priority', array( 'queue' => SchedulerQueue::DEFAULT ) ) );
	}

	/**
	 * @testdox Should throw in strict mode when unique is requested on a plain queue.
	 */
	public function test_strict_unique_on_a_plain_queue_throws(): void {
		$queue = $this->plain_queue();
		$this->use_queue( $queue );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'The unique scheduling option cannot take effect' );

		try {
			$options = array(
				'unique' => true,
				'strict' => true,
			);

			$this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', $options );
		} finally {
			$this->assertSame( array(), $queue->calls, 'Nothing should reach the queue when strict mode throws' );
		}
	}

	/**
	 * @testdox Should throw in strict mode when a non-default priority is requested on a plain queue.
	 */
	public function test_strict_priority_on_a_plain_queue_throws(): void {
		$this->use_queue( $this->plain_queue() );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'does not implement OptionsAwareQueueInterface' );

		$options = array(
			'priority' => 1,
			'strict'   => true,
		);

		$this->sut->schedule_recurring( 123, 60, 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', $options );
	}

	/**
	 * @testdox Should schedule in strict mode on a plain queue when no capability is requested.
	 */
	public function test_strict_with_default_options_on_a_plain_queue_schedules(): void {
		$queue = $this->plain_queue();
		$this->use_queue( $queue );

		$options = array(
			'strict' => true,
			'unique' => false,
		);

		$action_id = $this->sut->schedule_cron( 123, '0 0 * * *', 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', $options );

		$this->assertSame( 14, $action_id );
		$this->assertSame( array( 'schedule_cron' ), array_column( $queue->calls, 0 ) );
	}

	/**
	 * @testdox Should throw in strict mode on a stock queue whose Action Scheduler predates unique actions.
	 */
	public function test_strict_unique_on_old_action_scheduler_throws(): void {
		$scheduler = $this->scheduler_with_default_queue( $this->stock_queue_on_old_action_scheduler() );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'predates unique support, which needs 3.5.0' );

		$options = array(
			'unique' => true,
			'strict' => true,
			'queue'  => SchedulerQueue::DEFAULT,
		);

		$scheduler->schedule_single( 123, 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', $options );
	}

	/**
	 * @testdox Should keep best-effort behaviour when strict is not set, even where support is missing.
	 */
	public function test_non_strict_falls_back_where_support_is_missing(): void {
		$queue = $this->plain_queue( null );
		$this->use_queue( $queue );

		$options = array(
			'unique'   => true,
			'priority' => 1,
		);

		$action_id = $this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', $options );

		$this->assertSame( 12, $action_id );
		$this->assertSame( array( 'get_next', 'schedule_single' ), array_column( $queue->calls, 0 ), 'Uniqueness is emulated and the priority dropped' );
	}

	/**
	 * @testdox Should report the loaded Action Scheduler version.
	 */
	public function test_get_action_scheduler_version(): void {
		$version = $this->sut->get_action_scheduler_version();

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
		$this->assertTrue( $this->sut->is_ready( array( 'queue' => SchedulerQueue::DEFAULT ) ), 'The default queue can be asked directly' );
	}

	/**
	 * @testdox Should throw in strict mode when the priority key is present on a plain queue, even at the default value.
	 */
	public function test_strict_priority_key_on_a_plain_queue_throws_even_at_default(): void {
		$this->use_queue( $this->plain_queue() );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'The priority scheduling option cannot take effect' );

		$options = array(
			'priority' => 10,
			'strict'   => true,
		);

		$this->sut->schedule_single( 123, 'wc_scheduler_test_hook', array(), 'wc-scheduler-test', $options );
	}

	/**
	 * @testdox Should return neutral values with a notice, and touch nothing, when the stock queue is not ready.
	 */
	public function test_not_ready_stock_queue_returns_neutral_values_with_a_notice(): void {
		$stock     = $this->not_ready_stock_queue();
		$scheduler = $this->scheduler_with_default_queue( $stock );
		$this->use_queue( $stock );

		$this->assertFalse( $scheduler->is_ready() );
		$this->assertFalse( $scheduler->is_ready( array( 'queue' => SchedulerQueue::DEFAULT ) ) );

		foreach ( array( 'schedule_single', 'schedule_recurring', 'schedule_cron', 'cancel', 'cancel_all', 'get_next', 'search', 'has_scheduled_action' ) as $method ) {
			$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Queue\Scheduler::' . $method );
		}

		$this->assertSame( 0, $scheduler->schedule_single( 123, 'wc_scheduler_test_hook' ) );
		$this->assertSame( 0, $scheduler->schedule_recurring( 123, 60, 'wc_scheduler_test_hook' ) );
		$this->assertSame( 0, $scheduler->schedule_cron( 123, '0 0 * * *', 'wc_scheduler_test_hook' ) );
		$scheduler->cancel( 'wc_scheduler_test_hook' );
		$scheduler->cancel_all( 'wc_scheduler_test_hook' );
		$this->assertNull( $scheduler->get_next( 'wc_scheduler_test_hook' ) );
		$this->assertSame( array(), $scheduler->search( array( 'hook' => 'wc_scheduler_test_hook' ) ) );
		$this->assertFalse( $scheduler->has_scheduled_action( 'wc_scheduler_test_hook' ) );
	}

	/**
	 * @testdox Should throw in strict mode when the queue is not ready.
	 */
	public function test_not_ready_in_strict_mode_throws(): void {
		$stock     = $this->not_ready_stock_queue();
		$scheduler = $this->scheduler_with_default_queue( $stock );
		$this->use_queue( $stock );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'not ready to accept calls yet' );

		$scheduler->schedule_single( 123, 'wc_scheduler_test_hook', array(), '', array( 'strict' => true ) );
	}

	/**
	 * @testdox Should honour an options-aware queue that reports itself not ready.
	 */
	public function test_options_aware_queue_reporting_not_ready_is_honoured(): void {
		$queue = $this->options_aware_queue( array(), false );
		$this->use_queue( $queue );
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Queue\Scheduler::schedule_single' );

		$this->assertFalse( $this->sut->is_ready() );
		$this->assertSame( 0, $this->sut->schedule_single( 123, 'wc_scheduler_test_hook' ) );
		$this->assertSame( array(), $queue->calls, 'A queue that is not ready must not be called' );
	}

	/**
	 * @testdox Should judge a plain WC_Action_Queue subclass by the stock queue's readiness.
	 */
	public function test_plain_action_queue_subclass_follows_stock_readiness(): void {
		$scheduler = $this->scheduler_with_default_queue( $this->not_ready_stock_queue() );
		$this->use_queue( new class() extends \WC_Action_Queue {} );

		$this->assertFalse( $scheduler->is_ready(), 'A WC_Action_Queue subclass delegates to Action Scheduler, so it is as ready as the stock queue' );

		$this->use_queue( $this->plain_queue() );
		$this->assertTrue( $scheduler->is_ready(), 'A queue that does not extend WC_Action_Queue only needs plugins_loaded' );
	}

	/**
	 * @testdox Should ask an options-aware queue which options it honours instead of assuming all of them.
	 */
	public function test_supports_asks_the_options_aware_queue(): void {
		$this->register_legacy_proxy_class_mocks( array( \WC_Queue_Interface::class => $this->options_aware_queue( array( 'priority' ) ) ) );

		$this->assertTrue( $this->sut->supports( 'unique' ) );
		$this->assertFalse( $this->sut->supports( 'priority' ), 'The queue reported no native priority support' );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'reports no native support for priority' );
		$this->sut->schedule_single(
			time() + HOUR_IN_SECONDS,
			'wc_scheduler_test_oaq_strict',
			array(),
			'wc-scheduler-test',
			array(
				'priority' => 1,
				'strict'   => true,
			)
		);
	}

	/**
	 * @testdox Should call the queue's own add() so a queue that overrides it keeps intercepting.
	 */
	public function test_add_calls_the_queue_add_method(): void {
		$queue = $this->options_aware_queue();
		$this->register_legacy_proxy_class_mocks( array( \WC_Queue_Interface::class => $queue ) );

		$this->assertSame( 21, $this->sut->add( 'wc_scheduler_test_add', array( 'k' => 1 ), 'wc-scheduler-test', array( 'unique' => true ) ) );
		$this->assertSame( 'add', $queue->calls[0][0] );
		$this->assertSame( array( 'unique' => true ), $queue->calls[0][1][3] );

		$plain = $this->plain_queue();
		$this->register_legacy_proxy_class_mocks( array( \WC_Queue_Interface::class => $plain ) );

		$this->sut->add( 'wc_scheduler_test_add', array(), 'wc-scheduler-test' );
		$this->assertSame( 'add', $plain->calls[0][0] );
	}
}
