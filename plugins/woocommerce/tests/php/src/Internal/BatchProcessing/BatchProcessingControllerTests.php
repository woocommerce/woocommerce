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
	 * @testdox A mutating enqueue holds the options-table lock while persisting and releases it afterwards.
	 */
	public function test_enqueue_processor_holds_lock_during_write_and_releases_after(): void {
		$held_during_write = null;
		add_filter(
			'pre_update_option_' . BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			function ( $value ) use ( &$held_during_write ) {
				$held_during_write = $this->lock_row_value();
				return $value;
			}
		);

		$this->sut->enqueue_processor( 'Processor\\A' );

		$this->assertNotNull( $held_during_write, 'The lock row must exist while the option is written.' );
		$this->assertNull( $this->lock_row_value(), 'The lock row must be deleted once the mutation completes.' );
	}

	/**
	 * @testdox An unexpired lock claimed by another request forces the mutation onto its best-effort path and is left untouched.
	 */
	public function test_enqueue_processor_proceeds_when_lock_held_by_an_unexpired_lock(): void {
		global $wpdb;

		// Simulate another request holding the lock: insert the lock row with a release time far in the future,
		// so the controller can neither claim it (row exists) nor take it over (not yet stale) and must fall back.
		$foreign_expiry = number_format( microtime( true ) + 60, 6, '.', '' );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				BatchProcessingController::ENQUEUED_PROCESSORS_LOCK_OPTION,
				$foreign_expiry
			)
		);

		try {
			$this->sut->enqueue_processor( 'Processor\\A' );

			$this->assertContains(
				'Processor\\A',
				$this->sut->get_enqueued_processors(),
				'The mutation must still persist when the lock cannot be acquired (best-effort fallback).'
			);
			$this->assertNotNull(
				$this->lock_row_value(),
				'The other request\'s unexpired lock must be left in place, not stolen or released.'
			);
		} finally {
			$wpdb->query(
				$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", BatchProcessingController::ENQUEUED_PROCESSORS_LOCK_OPTION ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
		}
	}

	/**
	 * @testdox A stale (expired) lock left by a crashed request is taken over, and released after the mutation.
	 */
	public function test_enqueue_processor_takes_over_stale_lock(): void {
		global $wpdb;

		// A lock row whose release time is already in the past represents a crashed holder; it must be stealable.
		$stale_expiry = number_format( microtime( true ) - 60, 6, '.', '' );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				BatchProcessingController::ENQUEUED_PROCESSORS_LOCK_OPTION,
				$stale_expiry
			)
		);

		$this->sut->enqueue_processor( 'Processor\\A' );

		$this->assertContains(
			'Processor\\A',
			$this->sut->get_enqueued_processors(),
			'The mutation must proceed after taking over the stale lock.'
		);
		$this->assertNull(
			$this->lock_row_value(),
			'After taking over and using the stale lock, the controller must release (delete) it.'
		);
	}

	/**
	 * @testdox Releasing does not delete a lock whose stored release time no longer matches ours (taken over by another request).
	 */
	public function test_release_leaves_a_lock_taken_over_by_another_request(): void {
		global $wpdb;

		$foreign_value = null;
		add_filter(
			'pre_update_option_' . BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			function ( $value ) use ( &$foreign_value, $wpdb ) {
				// While this request holds the lock, simulate another request expiring and taking it over by
				// overwriting the lock row's release time. Release must then leave that row alone.
				$foreign_value = number_format( microtime( true ) + 999, 6, '.', '' );
				$wpdb->update(
					$wpdb->options,
					array( 'option_value' => $foreign_value ),
					array( 'option_name' => BatchProcessingController::ENQUEUED_PROCESSORS_LOCK_OPTION ),
					array( '%s' ),
					array( '%s' )
				);
				return $value;
			}
		);

		$this->sut->enqueue_processor( 'Processor\\A' );

		$this->assertSame(
			$foreign_value,
			$this->lock_row_value(),
			'Release must be scoped to our own release time, so a lock another request now owns is not deleted.'
		);

		// Clean up the simulated foreign lock so it does not leak into other assertions.
		$wpdb->delete(
			$wpdb->options,
			array( 'option_name' => BatchProcessingController::ENQUEUED_PROCESSORS_LOCK_OPTION ),
			array( '%s' )
		);
	}

	/**
	 * Read the raw stored value of the enqueued-processors lock row, or null when no lock is held.
	 *
	 * @return string|null The lock row's option_value, or null if the row does not exist.
	 */
	private function lock_row_value(): ?string {
		global $wpdb;
		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", BatchProcessingController::ENQUEUED_PROCESSORS_LOCK_OPTION ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		return null === $value ? null : (string) $value;
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

	/**
	 * @testdox The watchdog sanitizes a corrupted enqueued list, scheduling each valid processor once without fataling on non-string or empty entries.
	 */
	public function test_handle_watchdog_action_sanitizes_corrupted_list(): void {
		// A raw option holding duplicates, a non-string, an empty string, and a corrupt array entry: without
		// sanitizing, the non-string would fatal when passed to the strictly-typed is_scheduled(), and the empty
		// string would be scheduled and later fatal in get_processor_instance( '' ).
		update_option(
			BatchProcessingController::ENQUEUED_PROCESSORS_OPTION_NAME,
			array( 'Processor\\A', 'Processor\\A', new \stdClass(), '', array( 'corrupt' ), 'Processor\\B' ),
			false
		);

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_scheduled( 'Processor\\A' ), 'A valid processor should be scheduled by the watchdog.' );
		$this->assertTrue( $this->sut->is_scheduled( 'Processor\\B' ), 'A valid processor should be scheduled by the watchdog.' );
		$this->assertFalse(
			as_has_scheduled_action( $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME, array( '' ) ),
			'An empty-string entry must be dropped by sanitization, not scheduled as a processor.'
		);
		$this->assertCount(
			2,
			as_get_scheduled_actions(
				array(
					'hook'     => $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME,
					'group'    => '',
					'status'   => \ActionScheduler_Store::STATUS_PENDING,
					'per_page' => -1,
				),
				'ids'
			),
			'Only the two valid processors should be scheduled; duplicate, empty-string, and non-string entries must be dropped.'
		);
	}

	/**
	 * @testdox Removing the last enqueued processor sweeps orphaned 'ghost' single-batch actions left for processors no longer enqueued.
	 */
	public function test_remove_processor_sweeps_ghost_actions_when_queue_empties(): void {
		$this->sut->enqueue_processor( get_class( $this->test_process ) );

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		// A leftover single-batch action for a processor that is no longer enqueued, e.g. from the historical corruption bug.
		as_schedule_single_action( time() + HOUR_IN_SECONDS, $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME, array( 'Ghost\\Processor' ) );

		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( as_has_scheduled_action( $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME, array( 'Ghost\\Processor' ) ) );

		$this->sut->remove_processor( get_class( $this->test_process ) );

		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ), 'The removed processor should be unscheduled.' );
		$this->assertFalse(
			as_has_scheduled_action( $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME, array( 'Ghost\\Processor' ) ),
			'Emptying the queue should sweep orphaned ghost single-batch actions.'
		);
		$this->assertCount(
			0,
			as_get_scheduled_actions(
				array(
					'hook'     => $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME,
					'group'    => '',
					'status'   => \ActionScheduler_Store::STATUS_PENDING,
					'per_page' => -1,
				),
				'ids'
			),
			'Emptying the queue should sweep every single-batch action, not just the removed processor.'
		);
		$this->assertTrue(
			as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ),
			'The watchdog should be left scheduled so a concurrent enqueue is not stranded.'
		);
	}

	/**
	 * @testdox Removing a processor while others remain enqueued unschedules only its own action, leaving siblings scheduled.
	 */
	public function test_remove_processor_leaves_siblings_scheduled_when_queue_not_empty(): void {
		$second_processor = $this->get_processor_stub();

		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		$this->sut->enqueue_processor( get_class( $second_processor ) );

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $second_processor ) ) );

		$this->sut->remove_processor( get_class( $this->test_process ) );

		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ), 'The removed processor should be unscheduled.' );
		$this->assertFalse( $this->sut->is_enqueued( get_class( $this->test_process ) ), 'The removed processor should also be dequeued.' );
		$this->assertTrue(
			$this->sut->is_scheduled( get_class( $second_processor ) ),
			'A still-enqueued sibling processor must not have its scheduled action swept.'
		);
		$this->assertTrue( $this->sut->is_enqueued( get_class( $second_processor ) ), 'The sibling processor should remain enqueued.' );
	}
}
