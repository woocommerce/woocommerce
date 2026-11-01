<?php
/**
 * Unit tests for wc-order-step-logger-functions.php.
 *
 * @package WooCommerce\Tests\Functions
 */

declare(strict_types=1);
use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class WC_Order_Step_Logger_Functions_Test
 */
class WC_Order_Step_Logger_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Original REQUEST_METHOD value to restore after tests.
	 *
	 * @var string|null
	 */
	private $original_request_method;

	/**
	 * Whether HPOS was in use before a test changed the order storage, or null if it did not.
	 *
	 * @var bool|null
	 */
	private $prev_hpos_enabled = null;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Clean up any previous log files.
		$this->clean_up_log_files();

		// Save the original REQUEST_METHOD and set it to POST for the test.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Test setup, not user input.
		$this->original_request_method = $_SERVER['REQUEST_METHOD'] ?? null;
		$_SERVER['REQUEST_METHOD']     = 'POST';
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		if ( null !== $this->prev_hpos_enabled ) {
			OrderHelper::toggle_cot_feature_and_usage( $this->prev_hpos_enabled );
			$this->prev_hpos_enabled = null;
		}

		// Clean up log files.
		$this->clean_up_log_files();

		// Clear the WC_LOG_THRESHOLD constant if set.
		Constants::clear_single_constant( 'WC_LOG_THRESHOLD' );

		// Reset logging settings.
		delete_option( 'woocommerce_logs_level_threshold' );

		// Restore the original REQUEST_METHOD.
		if ( null === $this->original_request_method ) {
			unset( $_SERVER['REQUEST_METHOD'] );
		} else {
			$_SERVER['REQUEST_METHOD'] = $this->original_request_method;
		}

		parent::tearDown();
	}

	/**
	 * Clean up log files created during tests.
	 */
	private function clean_up_log_files(): void {
		$log_dir = \Automattic\WooCommerce\Utilities\LoggingUtil::get_log_directory();

		if ( is_dir( $log_dir ) ) {
			$files = glob( $log_dir . 'place-order-debug-*.log' );
			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					if ( is_file( $file ) ) {
						wp_delete_file( $file );
					}
				}
			}
		}
	}

	/**
	 * Get log files matching the pattern.
	 *
	 * @return array Array of log file paths.
	 */
	private function get_log_files(): array {
		$log_dir = \Automattic\WooCommerce\Utilities\LoggingUtil::get_log_directory();

		$files = glob( $log_dir . 'place-order-debug-*.log' );
		return $files ? $files : array();
	}

	/**
	 * @testdox Order step logging should respect the site's logging level configuration when set to CRITICAL.
	 */
	public function test_wc_log_order_step_respects_critical_level_threshold(): void {
		// Set the logging level to CRITICAL only.
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::CRITICAL );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Test message - should not be logged',
			array( 'order_object' => $order ),
			false,
			true
		);

		// End logging.
		wc_log_order_step(
			'Final step - should not be logged',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Get log files.
		$log_files = $this->get_log_files();

		// Assert that no log files were created since DEBUG is below CRITICAL threshold.
		$this->assertEmpty(
			$log_files,
			'Expected no log files to be created when logging level is set to CRITICAL'
		);

		// Clean up the order.
		$order->delete( true );
	}

	/**
	 * @testdox Order step logging should write logs when logging level is set to DEBUG.
	 */
	public function test_wc_log_order_step_writes_logs_at_debug_level(): void {
		// Set the logging level to DEBUG.
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::DEBUG );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Test message - should be logged',
			array( 'order_object' => $order ),
			false,
			true
		);

		// Add another step.
		wc_log_order_step(
			'Another step - should be logged',
			array( 'order_object' => $order ),
			false,
			false
		);

		// End logging.
		wc_log_order_step(
			'Final step - should be logged',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Get log files.
		$log_files = $this->get_log_files();

		// Assert that log files were created.
		$this->assertNotEmpty(
			$log_files,
			'Expected log files to be created when logging level is set to DEBUG'
		);

		// Verify that the log file contains the expected messages.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- The file is created in this function and only read to verify contents in the test.
		$log_content = file_get_contents( $log_files[0] );
		$this->assertStringContainsString( 'Test message - should be logged', $log_content );
		$this->assertStringContainsString( 'Another step - should be logged', $log_content );
		$this->assertStringContainsString( 'Final step - should be logged', $log_content );

		// Clean up the order.
		$order->delete( true );
	}

	/**
	 * @testdox Order step logging should respect the site's logging level configuration when set via option.
	 */
	public function test_wc_log_order_step_respects_option_level_threshold(): void {
		// Set the logging level to ERROR via option (not constant).
		update_option( 'woocommerce_logs_level_threshold', WC_Log_Levels::ERROR );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Test message - should not be logged',
			array( 'order_object' => $order ),
			false,
			true
		);

		// End logging.
		wc_log_order_step(
			'Final step - should not be logged',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Get log files.
		$log_files = $this->get_log_files();

		// Assert that no log files were created since DEBUG is below ERROR threshold.
		$this->assertEmpty(
			$log_files,
			'Expected no log files to be created when logging level is set to ERROR via option'
		);

		// Clean up the order.
		$order->delete( true );
	}

	/**
	 * @testdox Order step logging should clean up logs after successful checkout.
	 */
	public function test_wc_log_order_step_cleanup_on_final_step(): void {
		// Set the logging level to DEBUG.
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::DEBUG );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Step 1',
			array( 'order_object' => $order ),
			false,
			true
		);

		// Add another step with unique message.
		wc_log_order_step(
			'Step 2',
			array( 'order_object' => $order ),
			false,
			false
		);

		// End logging with unique final step.
		wc_log_order_step(
			'Step 3 - Final',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Verify that _debug_log_source_pending_deletion meta is set.
		$order_meta = $order->get_meta( '_debug_log_source_pending_deletion' );
		$this->assertNotEmpty(
			$order_meta,
			'Expected _debug_log_source_pending_deletion meta to be set after final step'
		);

		// Clean up the order.
		$order->delete( true );
	}

	/**
	 * @testdox Order step logging should not clean up logs when steps are repeated (race condition detected).
	 */
	public function test_wc_log_order_step_no_cleanup_on_repeated_steps(): void {
		// Set the logging level to DEBUG.
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::DEBUG );

		// Create an order for testing.
		$order = WC_Helper_Order::create_order();

		// Start logging.
		wc_log_order_step(
			'Step 1',
			array( 'order_object' => $order ),
			false,
			true
		);

		// Add the same step twice (simulating a race condition or recursion).
		wc_log_order_step(
			'Step 1',
			array( 'order_object' => $order ),
			false,
			false
		);

		// End logging.
		wc_log_order_step(
			'Step 2 - Final',
			array( 'order_object' => $order ),
			true,
			false
		);

		// Verify that _debug_log_source_pending_deletion meta is NOT set due to duplicate steps.
		$order_meta = $order->get_meta( '_debug_log_source_pending_deletion' );
		$this->assertEmpty(
			$order_meta,
			'Expected _debug_log_source_pending_deletion NOT to be set when steps are repeated'
		);

		// Clean up the order.
		$order->delete( true );
	}

	/**
	 * @testdox Order step logging can be disabled entirely via the woocommerce_order_step_logging_enabled filter.
	 */
	public function test_wc_log_order_step_can_be_disabled_via_filter(): void {
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::DEBUG );

		add_filter( 'woocommerce_order_step_logging_enabled', '__return_false' );

		$order = WC_Helper_Order::create_order();

		wc_log_order_step(
			'Step 1 - should not be logged',
			array( 'order_object' => $order ),
			false,
			true
		);

		wc_log_order_step(
			'Step 2 - Final - should not be logged',
			array( 'order_object' => $order ),
			true,
			false
		);

		remove_filter( 'woocommerce_order_step_logging_enabled', '__return_false' );

		$this->assertEmpty(
			$this->get_log_files(),
			'Expected no log files to be created when order step logging is disabled via filter'
		);

		$this->assertEmpty(
			$order->get_meta( '_debug_log_source' ),
			'Expected no debug log meta to be added to the order when order step logging is disabled via filter'
		);

		$order->delete( true );
	}

	/**
	 * @testdox Order step logging stores its meta without a full order save, so woocommerce_update_order does not fire.
	 *
	 * @testWith [true, false]
	 *           [true, true]
	 *           [false, false]
	 *           [false, true]
	 *
	 * @param bool $hpos_enabled Whether orders are stored in the HPOS tables.
	 * @param bool $sync_enabled Whether HPOS and posts data sync is on.
	 */
	public function test_wc_log_order_step_does_not_fire_update_order_hook( bool $hpos_enabled, bool $sync_enabled ): void {
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::DEBUG );
		$this->use_order_storage( $hpos_enabled, $sync_enabled );
		$order = WC_Helper_Order::create_order();
		// HPOS saves the whole order after a meta change unless the order was modified in the current second.
		$order->set_date_modified( time() - HOUR_IN_SECONDS );
		$order->save();
		$update_order_count = 0;
		add_action(
			'woocommerce_update_order',
			function () use ( &$update_order_count ) {
				++$update_order_count;
			}
		);

		wc_log_order_step( 'Step 1', array( 'order_object' => $order ), false, true );
		wc_log_order_step( 'Step 2', array( 'order_object' => $order ) );

		$log_source = wc_get_order( $order->get_id() )->get_meta( '_debug_log_source' );
		$this->assertStringStartsWith( 'place-order-debug-', $log_source, 'Each logged step should store the log source on the order' );

		wc_log_order_step( 'Step 3 - Final', array( 'order_object' => $order ), true );

		$this->assertSame( 0, $update_order_count, 'Logging checkout steps should not fire woocommerce_update_order' );
		$saved_order = wc_get_order( $order->get_id() );
		$this->assertSame( '', $saved_order->get_meta( '_debug_log_source' ), 'The final step should remove the log source meta' );
		$this->assertSame( $log_source, $saved_order->get_meta( '_debug_log_source_pending_deletion' ), 'The final step should mark the log for deletion' );
		if ( $hpos_enabled && $sync_enabled ) {
			$this->assertSame( $log_source, get_post_meta( $order->get_id(), '_debug_log_source_pending_deletion', true ), 'The pending-deletion meta should be backfilled to the posts table when sync is on' );
			$this->assertSame( '', get_post_meta( $order->get_id(), '_debug_log_source', true ), 'The log source meta should be removed from the posts table when sync is on' );
		}
	}

	/**
	 * @testdox Order step logging does not store meta for an order that has not been saved yet.
	 */
	public function test_wc_log_order_step_skips_meta_for_unsaved_order(): void {
		Constants::set_constant( 'WC_LOG_THRESHOLD', WC_Log_Levels::DEBUG );
		$order = new WC_Order();

		wc_log_order_step( 'Step 1', array( 'order_object' => $order ), false, true );
		wc_log_order_step( 'Step 2 - Final', array( 'order_object' => $order ), true );

		$this->assertSame( 0, $order->get_id(), 'Logging should not save the order' );
		$this->assertSame( '', $order->get_meta( '_debug_log_source' ), 'Logging should not add meta to an unsaved order' );
	}

	/**
	 * Store orders in the HPOS tables or in posts for the current test.
	 *
	 * @param bool $hpos_enabled Whether orders are stored in the HPOS tables.
	 * @param bool $sync_enabled Whether HPOS and posts data sync is on.
	 */
	private function use_order_storage( bool $hpos_enabled, bool $sync_enabled ): void {
		if ( null === $this->prev_hpos_enabled ) {
			$this->prev_hpos_enabled = OrderUtil::custom_orders_table_usage_is_enabled();
		}
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		add_filter(
			'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION,
			function () use ( $sync_enabled ) {
				return wc_bool_to_string( $sync_enabled );
			}
		);
		OrderHelper::toggle_cot_feature_and_usage( $hpos_enabled );
	}
}
