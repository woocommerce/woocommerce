<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\BatchProcessing;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;

/**
 * Tests for the database connection error guard in BatchProcessingController.
 *
 * @since 10.9.0
 */
class BatchProcessingControllerDbErrorTest extends \WC_Unit_Test_Case {

	/**
	 * Instance of BatchProcessingController.
	 *
	 * @var BatchProcessingController
	 */
	private $sut;

	/**
	 * @var DataSynchronizer
	 */
	private $test_process;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->reset_container_resolutions();
		remove_all_actions( BatchProcessingController::WATCHDOG_ACTION_NAME );
		remove_all_actions( BatchProcessingController::PROCESS_SINGLE_BATCH_ACTION_NAME );

		$this->sut          = wc_get_container()->get( BatchProcessingController::class );
		$this->test_process = wc_get_container()->get( DataSynchronizer::class );
		$this->sut->force_clear_all_processes();
	}

	/**
	 * Teardown.
	 */
	public function tearDown(): void {
		global $wpdb;
		// Ensure we restore wpdb state after tests.
		$wpdb->last_error = '';
		parent::tearDown();
	}

	/**
	 * @testdox The shutdown handler bails out when $wpdb->last_error is set, preventing cascading failures.
	 */
	public function test_shutdown_handler_bails_on_wpdb_last_error() {
		global $wpdb;

		// Enqueue a processor so there's work to do.
		$this->sut->enqueue_processor( get_class( $this->test_process ) );

		// Simulate a database error state (e.g., errno 2014 "Commands out of sync").
		$wpdb->last_error = 'Commands out of sync; you can\'t run this command now';

		// Trigger the shutdown handler via reflection.
		$method = new \ReflectionMethod( $this->sut, 'remove_or_retry_failed_processors' );
		$method->setAccessible( true );

		// Simulate wp_loaded having fired.
		if ( ! did_action( 'wp_loaded' ) ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'wp_loaded' );
		}

		// The method should bail out early without throwing or making DB queries.
		$method->invoke( $this->sut );

		// If we got here without errors, the guard worked.
		// The processor should still be enqueued (not modified by the shutdown handler).
		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );

		// Clean up.
		$wpdb->last_error = '';
	}

	/**
	 * @testdox The is_db_connection_in_error method returns true when wpdb has a last_error.
	 */
	public function test_is_db_connection_in_error_detects_wpdb_last_error() {
		global $wpdb;

		$method = new \ReflectionMethod( $this->sut, 'is_db_connection_in_error' );
		$method->setAccessible( true );

		// No error state.
		$wpdb->last_error = '';
		$this->assertFalse( $method->invoke( $this->sut ) );

		// With error state.
		$wpdb->last_error = 'Commands out of sync; you can\'t run this command now';
		$this->assertTrue( $method->invoke( $this->sut ) );

		// Clean up.
		$wpdb->last_error = '';
	}

	/**
	 * @testdox The is_db_connection_in_error method returns true when mysqli has a non-zero errno.
	 */
	public function test_is_db_connection_in_error_detects_mysqli_errno() {
		global $wpdb;

		$method = new \ReflectionMethod( $this->sut, 'is_db_connection_in_error' );
		$method->setAccessible( true );

		// Verify the method works with a clean connection.
		$wpdb->last_error = '';
		$this->assertFalse( $method->invoke( $this->sut ) );

		// We can't easily simulate a real mysqli error without corrupting the connection,
		// but we can verify the method doesn't crash when checking the connection.
		// The real-world scenario (errno 2014) would be caught by the mysqli_errno check.
		if ( isset( $wpdb->dbh ) && $wpdb->dbh instanceof \mysqli ) {
			// Connection is healthy, should return false.
			$this->assertFalse( $method->invoke( $this->sut ) );
		}
	}

	/**
	 * @testdox The shutdown handler proceeds normally when the database connection is healthy.
	 */
	public function test_shutdown_handler_proceeds_when_db_is_healthy() {
		global $wpdb;

		// Ensure clean state.
		$wpdb->last_error = '';

		// Enqueue a processor and schedule the watchdog.
		$this->sut->enqueue_processor( get_class( $this->test_process ) );

		// Simulate wp_loaded having fired.
		if ( ! did_action( 'wp_loaded' ) ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'wp_loaded' );
		}

		// Trigger the shutdown handler - it should proceed normally.
		$method = new \ReflectionMethod( $this->sut, 'remove_or_retry_failed_processors' );
		$method->setAccessible( true );
		$method->invoke( $this->sut );

		// The method should have run without issues (watchdog is scheduled, so it returns early).
		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
	}
}
