<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\BatchProcessing;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;

/**
 * Class BatchProcessingControllerTests.
 */
class BatchProcessingControllerTests extends \WC_Unit_Test_Case {

	/**
	 * Instance of BatchProcessingController.
	 *
	 * @var BatchProcessingController;
	 */
	private $sut;

	/**
	 * @var DataSynchronizer
	 */
	private $test_process;

	/**
	 * Setup.
	 */
	public function setUp() : void {
		parent::setUp();

		// BatchProcessingController hooks onto actions when instantiated (at bootstrap), so we need to do a reset.
		$this->reset_container_resolutions();
		remove_all_actions( BatchProcessingController::WATCHDOG_ACTION_NAME );
		remove_all_actions( BatchProcessingController::PROCESS_SINGLE_BATCH_ACTION_NAME );

		$this->sut          = wc_get_container()->get( BatchProcessingController::class );
		$this->test_process = wc_get_container()->get( DataSynchronizer::class );
		$this->sut->force_clear_all_processes();
	}

	/**
	 * @testdox Processors are enqueued correctly.
	 */
	public function test_enqueue_processor() {
		$this->assertFalse( $this->sut->is_enqueued( get_class( $this->test_process ) ) );

		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
	}

	/**
	 * @testdox Enqueuing the same processor repeatedly keeps a single entry.
	 */
	public function test_enqueue_processor_is_idempotent(): void {
		$processor = get_class( $this->test_process );

		$this->sut->enqueue_processor( $processor );
		$this->sut->enqueue_processor( $processor );
		$this->sut->enqueue_processor( $processor );

		$enqueued = $this->sut->get_enqueued_processors();
		$this->assertCount( 1, $enqueued, 'Repeated enqueues of the same processor must not create duplicates.' );
		$this->assertContains( $processor, $enqueued, 'The enqueued processor should still be present.' );
		$this->assertCount( 1, get_option( BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME ), 'The persisted option must not contain duplicates.' );
	}

	/**
	 * @testdox Enqueuing collapses a pre-existing list bloated with duplicates and persists the cleanup.
	 */
	public function test_enqueue_processor_collapses_preexisting_duplicates(): void {
		$processor = get_class( $this->test_process );

		// Simulate an option bloated by the historical duplicate bug.
		update_option(
			BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			array_fill( 0, 5, $processor ),
			false
		);

		$this->sut->enqueue_processor( $processor );

		$this->assertCount( 1, $this->sut->get_enqueued_processors(), 'Existing duplicates should collapse to a single entry.' );
		$persisted = get_option( BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME );
		$this->assertCount( 1, $persisted, 'The de-duplicated list should be persisted.' );
		$this->assertContains( $processor, $persisted, 'The persisted list should still contain the processor.' );
	}

	/**
	 * @testdox Enqueuing a processor collapses duplicates of other processors without dropping them.
	 */
	public function test_enqueue_processor_collapses_duplicates_without_dropping_others(): void {
		update_option(
			BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			array( 'Processor\\A', 'Processor\\A', 'Processor\\A', 'Processor\\B' ),
			false
		);

		$this->sut->enqueue_processor( 'Processor\\C' );

		$this->assertSame(
			array( 'Processor\\A', 'Processor\\B', 'Processor\\C' ),
			$this->sut->get_enqueued_processors(),
			'Collapsing duplicates must preserve other processors and append the new one, in order.'
		);
	}

	/**
	 * @testdox Re-enqueuing an already-present processor on a clean list does not rewrite the option.
	 */
	public function test_enqueue_processor_skips_write_when_unchanged(): void {
		$processor = get_class( $this->test_process );
		$this->sut->enqueue_processor( $processor );

		$writes = 0;
		add_filter(
			'pre_update_option_' . BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			function ( $value ) use ( &$writes ) {
				++$writes;
				return $value;
			}
		);

		$this->sut->enqueue_processor( $processor );

		$this->assertSame( 0, $writes, 'A no-op enqueue must not trigger an option write.' );
	}

	/**
	 * @testdox Enqueuing strips non-string values from a corrupted option without fataling.
	 */
	public function test_enqueue_processor_strips_non_string_values(): void {
		$processor = get_class( $this->test_process );

		// A corrupted option containing non-string values would otherwise make array_unique() fatal.
		update_option(
			BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			array( $processor, new \stdClass(), array( 'corrupt' ), 12345 ),
			false
		);

		$this->sut->enqueue_processor( $processor );

		$this->assertSame( array( $processor ), $this->sut->get_enqueued_processors(), 'Non-string values must be stripped, leaving only valid processor names.' );
	}

	/**
	 * @testdox Enqueuing collapses a heavily bloated list of thousands of duplicates to a single entry.
	 */
	public function test_enqueue_processor_collapses_heavily_bloated_list(): void {
		$processor = get_class( $this->test_process );

		// Mirror the reported production case (thousands of identical entries).
		update_option(
			BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			array_fill( 0, 3000, $processor ),
			false
		);

		$this->sut->enqueue_processor( $processor );

		$this->assertSame( array( $processor ), $this->sut->get_enqueued_processors(), 'A heavily bloated list must collapse to a single entry.' );
	}

	/**
	 * @testdox 'remove_processor' dequeues and unschedules a processor, but the watchdog is kept alive if more processors are still enqueued.
	 */
	public function test_remove_processor_when_others_are_still_enqueued() {
		$second_processor = $this->get_processor_stub();

		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		$this->sut->enqueue_processor( get_class( $second_processor ) );

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_enqueued( get_class( $second_processor ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $second_processor ) ) );
		$this->assertTrue( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );

		$this->sut->remove_processor( get_class( $second_processor ) );

		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_enqueued( get_class( $second_processor ) ) );
		$this->assertFalse( $this->sut->is_scheduled( get_class( $second_processor ) ) );
		$this->assertTrue( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );
	}

	/**
	 * @testdox 'remove_processor' dequeues and unschedules a processor, and leaves the watchdog to self-terminate when no more processors are enqueued.
	 */
	public function test_remove_processor_when_no_others_remain_enqueued() {
		$this->sut->enqueue_processor( get_class( $this->test_process ) );

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );

		$this->sut->remove_processor( get_class( $this->test_process ) );

		$this->assertFalse( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ) );

		/*
		 * The watchdog is intentionally left scheduled rather than force-unscheduled: handle_watchdog_action()
		 * returns without rescheduling itself once the queue is empty (so it self-terminates after one more run),
		 * and keeping it in place means a processor enqueued concurrently with this removal is still picked up
		 * instead of being stranded with no watchdog.
		 */
		$this->assertTrue(
			as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ),
			'The watchdog should be left to self-terminate, not force-unscheduled, so concurrent enqueues are not stranded.'
		);
	}

	/**
	 * @testdox Enqueuing re-reads the freshest persisted list inside the critical section, merging with a concurrent write instead of clobbering it.
	 */
	public function test_enqueue_processor_merges_with_concurrent_write(): void {
		global $wpdb;

		// This request enqueues A, which also primes the request cache with array( A ).
		$this->sut->enqueue_processor( 'Processor\\A' );

		/*
		 * Simulate a concurrent request that appended B and committed it to the database after this request
		 * had already cached array( A ). Writing through $wpdb (rather than update_option()) deliberately leaves
		 * the stale request cache in place, reproducing the cross-request read-modify-write race.
		 */
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => maybe_serialize( array( 'Processor\\A', 'Processor\\B' ) ) ),
			array( 'option_name' => BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME )
		);

		$this->sut->enqueue_processor( 'Processor\\C' );

		$this->assertSame(
			array( 'Processor\\A', 'Processor\\B', 'Processor\\C' ),
			$this->sut->get_enqueued_processors(),
			'Enqueuing must merge with the concurrently-added processor (fresh read) rather than dropping it.'
		);
	}

	/**
	 * @testdox Removing a processor re-reads the freshest list, so a concurrently-added processor is not dropped.
	 */
	public function test_remove_processor_uses_fresh_state(): void {
		global $wpdb;

		update_option(
			BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			array( 'Processor\\A', 'Processor\\B' ),
			false
		);

		// A concurrent request appended C and committed it after this request cached array( A, B ).
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => maybe_serialize( array( 'Processor\\A', 'Processor\\B', 'Processor\\C' ) ) ),
			array( 'option_name' => BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME )
		);

		$this->sut->remove_processor( 'Processor\\A' );

		$this->assertSame(
			array( 'Processor\\B', 'Processor\\C' ),
			$this->sut->get_enqueued_processors(),
			'Removal must operate on the fresh list, preserving the concurrently-added processor.'
		);
	}

	/**
	 * @testdox Removing a processor from a corrupted list strips non-string values instead of fataling on array_diff().
	 */
	public function test_remove_processor_strips_non_string_values_without_fataling(): void {
		// array_diff() string-casts its operands, so an object entry would fatal in PHP 8 without sanitizing first.
		update_option(
			BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			array( 'Processor\\A', new \stdClass(), array( 'corrupt' ), 'Processor\\B' ),
			false
		);

		$this->assertTrue( $this->sut->remove_processor( 'Processor\\A' ) );

		$this->assertSame(
			array( 'Processor\\B' ),
			$this->sut->get_enqueued_processors(),
			'Removal must drop the target and strip non-string values, leaving only valid processor names.'
		);
	}

	/**
	 * @testdox Dequeuing a processor from a corrupted list strips non-string values instead of fataling on array_diff().
	 */
	public function test_dequeue_processor_strips_non_string_values_without_fataling(): void {
		// Mirror of the remove_processor corruption test for the dequeue path, which shares sanitize_processor_list().
		update_option(
			BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			array( 'Processor\\A', new \stdClass(), array( 'corrupt' ), 'Processor\\B' ),
			false
		);

		$dequeue = new \ReflectionMethod( $this->sut, 'dequeue_processor' );
		$dequeue->setAccessible( true );
		$dequeue->invoke( $this->sut, 'Processor\\A' );

		$this->assertSame(
			array( 'Processor\\B' ),
			$this->sut->get_enqueued_processors(),
			'Dequeuing must drop the target and strip non-string values, leaving only valid processor names.'
		);
	}

	/**
	 * @testdox Dequeuing a finished processor re-reads the freshest list, so a concurrently-added processor is not dropped.
	 */
	public function test_dequeue_processor_uses_fresh_state(): void {
		global $wpdb;

		update_option(
			BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			array( 'Processor\\A', 'Processor\\B' ),
			false
		);

		// A concurrent request appended C and committed it after this request cached array( A, B ).
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => maybe_serialize( array( 'Processor\\A', 'Processor\\B', 'Processor\\C' ) ) ),
			array( 'option_name' => BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME )
		);

		// dequeue_processor() is private; it is reached in production when a batch finishes. Invoke it directly.
		$dequeue = new \ReflectionMethod( $this->sut, 'dequeue_processor' );
		$dequeue->setAccessible( true );
		$dequeue->invoke( $this->sut, 'Processor\\A' );

		$this->assertSame(
			array( 'Processor\\B', 'Processor\\C' ),
			$this->sut->get_enqueued_processors(),
			'Dequeuing must operate on the fresh list, preserving the concurrently-added processor.'
		);
	}

	/**
	 * @testdox Removing the last processor deletes that processor's stored state.
	 */
	public function test_remove_processor_clears_state_when_no_others_remain(): void {
		$processor = get_class( $this->test_process );

		$this->sut->enqueue_processor( $processor );

		// Seed processor state so we can prove it gets cleared on removal.
		$state_option = $this->get_processor_state_option_name( $processor );
		update_option( $state_option, array( 'total_time_spent' => 5 ), false );

		$this->sut->remove_processor( $processor );

		$this->assertFalse(
			get_option( $state_option ),
			'Removing the last processor must delete its stored state, not leave it orphaned.'
		);
	}

	/**
	 * @testdox A mutating enqueue holds the named lock while persisting and releases it afterwards.
	 */
	public function test_enqueue_processor_holds_lock_during_write_and_releases_after(): void {
		global $wpdb;

		$lock_name = $this->get_lock_name();

		$free_during_write = null;
		add_filter(
			'pre_update_option_' . BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			function ( $value ) use ( &$free_during_write, $wpdb, $lock_name ) {
				// On this same DB session, IS_FREE_LOCK() returns '0' while the lock is held by this session.
				$free_during_write = $wpdb->get_var( $wpdb->prepare( 'SELECT IS_FREE_LOCK(%s)', $lock_name ) );
				return $value;
			}
		);

		$this->sut->enqueue_processor( 'Processor\\A' );

		$this->assertSame( '0', (string) $free_during_write, 'The named lock must be held while the option is written.' );
		$this->assertSame(
			'1',
			(string) $wpdb->get_var( $wpdb->prepare( 'SELECT IS_FREE_LOCK(%s)', $lock_name ) ),
			'The named lock must be released once the mutation completes.'
		);
	}

	/**
	 * @testdox When the named lock is held by another connection, the mutation still proceeds (best-effort) after the timeout.
	 */
	public function test_enqueue_processor_proceeds_when_lock_held_by_another_connection(): void {
		global $wpdb;
		$lock_name = $this->get_lock_name();

		// Open a second, independent database connection and hold the lock there so the controller's own
		// GET_LOCK on the request connection cannot acquire it within ENQUEUED_PROCESSORS_LOCK_TIMEOUT.
		$other = new \wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		$other->query( $other->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, 30 ) );

		try {
			/*
			 * Fail fast if the second connection did not actually claim the lock: otherwise the controller would
			 * acquire it normally and this test would silently pass without ever exercising the best-effort
			 * fallback. IS_USED_LOCK() returns the connection id holding the lock (null if free), so assert it is
			 * held by a different session than the request connection.
			 */
			$lock_holder    = $other->get_var( $other->prepare( 'SELECT IS_USED_LOCK(%s)', $lock_name ) );
			$request_thread = (string) $wpdb->get_var( 'SELECT CONNECTION_ID()' );
			$this->assertNotNull( $lock_holder, 'Precondition: the second connection must hold the lock.' );
			$this->assertNotSame(
				$request_thread,
				(string) $lock_holder,
				'Precondition: the lock must be held by the other connection, not the request connection, so the controller is forced onto its best-effort path.'
			);

			$this->sut->enqueue_processor( 'Processor\\A' );

			$this->assertContains(
				'Processor\\A',
				$this->sut->get_enqueued_processors(),
				'The mutation must still persist when the lock cannot be acquired (best-effort fallback).'
			);
		} finally {
			$other->query( $other->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			$other->close();
		}
	}

	/**
	 * Resolve the controller's named-lock identifier via reflection so the test never drifts from production.
	 *
	 * @return string Lock name.
	 */
	private function get_lock_name(): string {
		$method = new \ReflectionMethod( $this->sut, 'get_enqueued_processors_lock_name' );
		$method->setAccessible( true );
		return $method->invoke( $this->sut );
	}

	/**
	 * Resolve the option name where a processor's state is stored, via reflection.
	 *
	 * @param string $processor_class_name Fully qualified processor class name.
	 * @return string Option name.
	 */
	private function get_processor_state_option_name( string $processor_class_name ): string {
		$method = new \ReflectionMethod( $this->sut, 'get_processor_state_option_name' );
		$method->setAccessible( true );
		return $method->invoke( $this->sut, $processor_class_name );
	}

	/**
	 * Get a no-op batch processor.
	 *
	 * @return BatchProcessorInterface
	 */
	private function get_processor_stub(): BatchProcessorInterface {
		//phpcs:disable Squiz.Commenting
		return new class() implements BatchProcessorInterface {
			public function get_name(): string {
				return '';
			}

			public function get_description(): string {
				return '';
			}

			public function get_total_pending_count(): int {
				return 1;
			}

			public function get_next_batch_to_process( int $size ): array {
				return array();
			}

			public function process_batch( array $batch ): void {
			}

			public function get_default_batch_size(): int {
				return 1;
			}
		};
		//phpcs:enable Squiz.Commenting
	}

	/**
	 * @testdox Processors are scheduled via action scheduler as expected.
	 */
	public function test_schedule_processes() {
		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ) );

		$this->sut->enqueue_processor( get_class( $this->test_process ) );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
	}

	/**
	 * @testdox When an action is run, then batch processing takes place. Also another instance is scheduled when there are pending actions.
	 */
	public function test_process_single_update_unfinished() {
		$test_process_mock = $this->getMockBuilder( get_class( $this->test_process ) )->getMock();
		$test_process_mock->method( 'get_total_pending_count' )->willReturn( 10 );
		$test_process_mock->expects( $this->exactly( 2 ) )->method( 'get_next_batch_to_process' )->willReturn( array( 'dummy_id' ) );

		add_filter(
			'woocommerce_get_batch_processor',
			function() use ( $test_process_mock ) {
				return $test_process_mock;
			}
		);
		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		do_action( $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME, get_class( $this->test_process ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
	}

	/**
	 * @testdox When an action is run, then batch processing takes place. Also no further actions are scheduled when batch completes.
	 */
	public function test_process_single_update_finished() {
		$test_process_mock = $this->getMockBuilder( get_class( $this->test_process ) )->getMock();
		$test_process_mock->method( 'get_total_pending_count' )->willReturn( 0 );
		$test_process_mock
			->expects( $this->exactly( 2 ) )
			->method( 'get_next_batch_to_process' )
			->willReturnCallback(
				function ( $batch_size ) {
					return 1 === $batch_size ? array() : array( 'dummy_id' );
				}
			);
		add_filter(
			'woocommerce_get_batch_processor',
			function() use ( $test_process_mock ) {
				return $test_process_mock;
			}
		);
		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		do_action( $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME, get_class( $this->test_process ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
	}

	/**
	 * @testdox 'test_force_clear_all_processes' dequeues and unschedules all the processors, and unschedules the watchdog.
	 */
	public function test_force_clear_all_processes() {
		$second_processor = $this->get_processor_stub();

		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		$this->sut->enqueue_processor( get_class( $second_processor ) );

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_enqueued( get_class( $second_processor ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $second_processor ) ) );
		$this->assertTrue( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );

		$this->sut->force_clear_all_processes();

		$this->assertFalse( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_enqueued( get_class( $second_processor ) ) );
		$this->assertFalse( $this->sut->is_scheduled( get_class( $second_processor ) ) );
		$this->assertFalse( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );
	}
}
