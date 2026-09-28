<?php
/**
 * Unit tests for the WC_Tracker class.
 *
 * @package WooCommerce\Tests\WC_Tracker.
 */

declare(strict_types=1);

use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Utilities\PluginUtil;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- Ignoring test doubles.

/**
 * Mock Address Provider for testing.
 */
class WC_Tracker_Test_MockAddressProvider extends WC_Address_Provider {
	/**
	 * Constructor.
	 *
	 * @param string $id   Provider ID.
	 * @param string $name Provider name.
	 */
	public function __construct( $id = 'mock-address-provider', $name = 'Mock Address Provider' ) {
		$this->id   = $id;
		$this->name = $name;
	}
}

/**
 * Class WC_Tracker_Test
 */
class WC_Tracker_Test extends \WC_Unit_Test_Case {
	// phpcs:enable

	/**
	 * Test the tracking of wc_admin being disabled via filter.
	 */
	public function test_wc_admin_disabled_get_tracking_data() {
		$posted_data = null;

		// Test the case for woocommerce_admin_disabled filter returning true.
		add_filter(
			'woocommerce_admin_disabled',
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			function ( $default_value ) {
				return true;
			}
		);

		add_filter(
			'pre_http_request',
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			function ( $pre, $args, $url ) use ( &$posted_data ) {
				$posted_data = $args;
				return true;
			},
			3,
			10
		);
		WC_Tracker::send_tracking_data( true );
		$tracking_data = json_decode( $posted_data['body'], true );

		// Test the default case of no filter for set for woocommerce_admin_disabled.
		$this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data );
		$this->assertEquals( 'yes', $tracking_data['wc_admin_disabled'] );
	}

	/**
	 * Test the tracking of wc_admin being not disabled via filter.
	 */
	public function test_wc_admin_not_disabled_get_tracking_data() {
		$posted_data = null;
		// Bypass time delay so we can invoke send_tracking_data again.
		update_option( 'woocommerce_tracker_last_send', strtotime( '-2 weeks' ) );

		add_filter(
			'pre_http_request',
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			function ( $pre, $args, $url ) use ( &$posted_data ) {
				$posted_data = $args;
				return true;
			},
			3,
			10
		);
		WC_Tracker::send_tracking_data( true );
		$tracking_data = json_decode( $posted_data['body'], true );

		// Test the default case of no filter for set for woocommerce_admin_disabled.
		$this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data );
		$this->assertEquals( 'no', $tracking_data['wc_admin_disabled'] );
	}

	/**
	 * @testdox Should send a blocking request with a short timeout so the response can be inspected.
	 */
	public function test_send_tracking_data_uses_blocking_request(): void {
		$request_args = null;
		$this->fake_tracker_response( 200, $request_args );

		WC_Tracker::send_tracking_data( true );

		$this->assertTrue( $request_args['blocking'], 'The tracker request must be blocking to read the response.' );
		$this->assertSame( 10, $request_args['timeout'], 'The tracker request timeout should be short.' );
	}

	/**
	 * @testdox Should record the send time and clear the failure counter after a successful delivery.
	 */
	public function test_successful_send_records_last_send_and_clears_failures(): void {
		update_option( 'woocommerce_tracker_send_failures', 2 );
		$this->fake_tracker_response( 204 );

		WC_Tracker::send_tracking_data( true );

		$this->assertEqualsWithDelta( time(), (int) get_option( 'woocommerce_tracker_last_send' ), 5, 'A 2xx response should record the send time.' );
		$this->assertFalse( get_option( 'woocommerce_tracker_send_failures' ), 'A 2xx response should clear the failure counter.' );
	}

	/**
	 * @testdox Should not record the send time and should count the failure when delivery fails with a retryable error.
	 *
	 * @testWith [500]
	 *           [503]
	 *           [429]
	 *           [408]
	 *           [425]
	 *           ["wp_error"]
	 *
	 * @param int|string $status HTTP status, or "wp_error" for a transport failure.
	 */
	public function test_retryable_failure_keeps_snapshot_pending( $status ): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$last_send = strtotime( '-2 weeks' );
		update_option( 'woocommerce_tracker_last_send', $last_send );
		$this->fake_tracker_response( $status );
		$logger = $this->expect_tracker_warning();

		WC_Tracker::send_tracking_data();

		$this->assertSame( $last_send, (int) get_option( 'woocommerce_tracker_last_send' ), 'A retryable failure must leave the last send time unchanged so the next daily run retries.' );
		$this->assertSame( 1, (int) get_option( 'woocommerce_tracker_send_failures' ), 'A retryable failure should increment the failure counter.' );
		$this->assertCount( 1, $logger->warnings, 'A failed delivery should be logged as a warning.' );
		$this->assertSame( 'woocommerce-tracker', $logger->warnings[0]['source'] );
	}

	/**
	 * @testdox Should give up on the snapshot when the server rejects it with a non-retryable status.
	 *
	 * @testWith [400]
	 *           [403]
	 *           [404]
	 *           [410]
	 *
	 * @param int $status HTTP status.
	 */
	public function test_non_retryable_failure_records_last_send( int $status ): void {
		update_option( 'woocommerce_tracker_send_failures', 1 );
		$this->fake_tracker_response( $status );
		$this->expect_tracker_warning();

		WC_Tracker::send_tracking_data( true );

		$this->assertEqualsWithDelta( time(), (int) get_option( 'woocommerce_tracker_last_send' ), 5, 'A non-retryable failure should record the send time so the snapshot is not retried.' );
		$this->assertFalse( get_option( 'woocommerce_tracker_send_failures' ), 'Giving up should clear the failure counter.' );
	}

	/**
	 * @testdox Should give up on the snapshot after the maximum number of consecutive failures.
	 */
	public function test_max_consecutive_failures_records_last_send(): void {
		update_option( 'woocommerce_tracker_send_failures', 2 );
		$this->fake_tracker_response( 503 );
		$this->expect_tracker_warning();

		WC_Tracker::send_tracking_data( true );

		$this->assertEqualsWithDelta( time(), (int) get_option( 'woocommerce_tracker_last_send' ), 5, 'The third consecutive failure should record the send time.' );
		$this->assertFalse( get_option( 'woocommerce_tracker_send_failures' ), 'Giving up should clear the failure counter.' );
	}

	/**
	 * @testdox Should bound consecutive failures for any truthy value of the tracking option.
	 *
	 * @testWith ["yes"]
	 *           ["1"]
	 *
	 * @param string $allow_tracking Stored value of the tracking option.
	 */
	public function test_consecutive_failures_are_bounded_for_any_truthy_tracking_value( string $allow_tracking ): void {
		update_option( 'woocommerce_allow_tracking', $allow_tracking );
		$requests = 0;
		add_filter(
			'pre_http_request',
			function () use ( &$requests ) {
				++$requests;
				return array( 'response' => array( 'code' => 503 ) );
			}
		);
		$this->expect_tracker_warning();

		WC_Tracker::send_tracking_data();
		$this->assertSame( 1, (int) get_option( 'woocommerce_tracker_send_failures' ), 'The first failure should persist a count of one.' );

		WC_Tracker::send_tracking_data();
		$this->assertSame( 2, (int) get_option( 'woocommerce_tracker_send_failures' ), 'The second failure should persist a count of two.' );

		WC_Tracker::send_tracking_data();

		$this->assertSame( 3, $requests, 'Each daily run should retry while the snapshot is still pending.' );
		$this->assertEqualsWithDelta( time(), (int) get_option( 'woocommerce_tracker_last_send' ), 5, 'The third consecutive failure should record the send time so the snapshot is abandoned.' );
		$this->assertFalse( get_option( 'woocommerce_tracker_send_failures' ), 'Giving up should clear the failure counter.' );
	}

	/**
	 * @testdox Should retry on the next daily run after a failed delivery without waiting for the weekly interval.
	 */
	public function test_failed_send_is_retried_on_next_run(): void {
		$requests = 0;
		add_filter(
			'pre_http_request',
			function () use ( &$requests ) {
				++$requests;
				return 1 === $requests ? new WP_Error( 'http_request_failed', 'Timed out' ) : array( 'response' => array( 'code' => 200 ) );
			}
		);
		$this->expect_tracker_warning();

		WC_Tracker::send_tracking_data();
		WC_Tracker::send_tracking_data();

		$this->assertSame( 2, $requests, 'The second run should retry because the first delivery did not record a send time.' );
		$this->assertEqualsWithDelta( time(), (int) get_option( 'woocommerce_tracker_last_send' ), 5 );
	}

	/**
	 * @testdox Should suppress a second override send within an hour of a failed attempt.
	 */
	public function test_override_send_is_suppressed_within_an_hour_of_a_failed_attempt(): void {
		$request_args = null;
		$requests     = 0;
		add_filter(
			'pre_http_request',
			function () use ( &$requests ) {
				++$requests;
				return new WP_Error( 'http_request_failed', 'Timed out' );
			}
		);
		$this->expect_tracker_warning();

		WC_Tracker::send_tracking_data( true );
		WC_Tracker::send_tracking_data( true );

		$this->assertSame( 1, $requests, 'A failed override send must still block a second override send within the hour.' );
		$this->assertEqualsWithDelta( time(), (int) get_option( 'woocommerce_tracker_last_attempt' ), 5, 'Every attempt should record its time.' );
		$this->assertFalse( get_option( 'woocommerce_tracker_last_send' ), 'A failed attempt must not record a send time.' );
	}

	/**
	 * @testdox Should include the payload size in the failure log.
	 */
	public function test_failure_log_includes_payload_size(): void {
		$this->fake_tracker_response( 503 );
		$logger = $this->expect_tracker_warning();

		WC_Tracker::send_tracking_data( true );

		$this->assertGreaterThan( 0, $logger->warnings[0]['body_bytes'], 'The failure log should report the payload size.' );
	}

	/**
	 * @testdox Should report a rejected oversized snapshot distinctly.
	 */
	public function test_too_large_snapshot_is_logged_as_such(): void {
		$this->fake_tracker_response( 413 );
		$logger = $this->expect_tracker_warning();

		WC_Tracker::send_tracking_data( true );

		$this->assertStringContainsString( 'too large', $logger->messages[0] );
		$this->assertEqualsWithDelta( time(), (int) get_option( 'woocommerce_tracker_last_send' ), 5, 'An oversized snapshot is not retried.' );
	}

	/**
	 * @testdox Should clear the failure counter when tracking is turned off.
	 */
	public function test_opting_out_clears_failure_counter(): void {
		update_option( 'woocommerce_tracker_send_failures', 2 );

		WC()->handle_tracking_setting_change( 'yes', 'no' );

		$this->assertFalse( get_option( 'woocommerce_tracker_send_failures' ), 'Opting out should discard pending retry state.' );
	}

	/**
	 * @testdox Should treat a snapshot that cannot be encoded as a non-retryable failure.
	 */
	public function test_unencodable_snapshot_is_logged_and_not_retried(): void {
		$requests = 0;
		add_filter(
			'pre_http_request',
			function () use ( &$requests ) {
				++$requests;
				return array( 'response' => array( 'code' => 200 ) );
			}
		);
		add_filter(
			'woocommerce_tracker_data',
			function ( $data ) {
				$data['unencodable'] = INF;
				return $data;
			}
		);
		update_option( 'woocommerce_tracker_send_failures', 1 );
		$logger = $this->expect_tracker_warning();

		WC_Tracker::send_tracking_data( true );

		$this->assertSame( 0, $requests, 'An unencodable snapshot must not be posted.' );
		$this->assertSame( 'json_encode_failure', $logger->warnings[0]['error_code'] );
		$this->assertEqualsWithDelta( time(), (int) get_option( 'woocommerce_tracker_last_send' ), 5, 'An unencodable snapshot should not be retried daily.' );
		$this->assertFalse( get_option( 'woocommerce_tracker_send_failures' ), 'Giving up should clear the failure counter.' );
	}

	/**
	 * @testdox Should not record retry state when tracking was turned off while the request was in flight.
	 */
	public function test_retry_state_is_not_recorded_after_opt_out_during_request(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		add_filter(
			'pre_http_request',
			function () {
				update_option( 'woocommerce_allow_tracking', 'no' );
				return array( 'response' => array( 'code' => 503 ) );
			}
		);
		$this->expect_tracker_warning();

		WC_Tracker::send_tracking_data( true );

		$this->assertFalse( get_option( 'woocommerce_tracker_send_failures' ), 'Retry state must not be created once tracking is off.' );
	}

	/**
	 * @testdox Should stop rebuilding a snapshot that cannot be built after the same number of attempts a failed delivery gets.
	 *
	 * A Throwable escaping get_tracking_data() never reaches record_send_result(), so nothing
	 * downstream records the outcome. Throwing from a woocommerce_tracker_data callback
	 * reproduces that exact path: a real fatal is uncatchable, but both leave send_tracking_data()
	 * before any result is recorded.
	 */
	public function test_unbuildable_snapshot_is_abandoned_after_max_attempts(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$last_send = strtotime( '-2 weeks' );
		update_option( 'woocommerce_tracker_last_send', $last_send );

		$builds   = 0;
		$requests = 0;
		add_filter(
			'woocommerce_tracker_data',
			function () use ( &$builds ) {
				++$builds;
				throw new RuntimeException( 'Snapshot build failed' );
			}
		);
		add_filter(
			'pre_http_request',
			function () use ( &$requests ) {
				++$requests;
				return array( 'response' => array( 'code' => 200 ) );
			}
		);
		$logger = $this->expect_tracker_warning();

		// Four consecutive scheduled runs, on a store where building the snapshot always fails.
		for ( $run = 0; $run < 4; $run++ ) {
			try {
				WC_Tracker::send_tracking_data();
			} catch ( RuntimeException $e ) {
				continue;
			}
		}

		$this->assertSame( 0, $requests, 'A snapshot that cannot be built is never posted.' );
		$this->assertSame( 3, $builds, 'The build should be attempted the same number of times a failed delivery is retried, then abandoned.' );
		$this->assertEqualsWithDelta( time(), (int) get_option( 'woocommerce_tracker_last_send' ), 5, 'Abandoning the snapshot should record the send time so the next attempt waits for the weekly interval.' );
		$this->assertFalse( get_option( 'woocommerce_tracker_send_failures' ), 'Giving up should clear the failure counter.' );
		$this->assertCount( 1, $logger->warnings, 'Abandoning the snapshot should be logged once, on the run that gives up.' );
		$this->assertSame( 3, $logger->warnings[0]['failures'], 'The warning should report the attempts made, not the run that found the limit exceeded.' );
	}

	/**
	 * Fake the tracker HTTP response.
	 *
	 * @param int|string $status       HTTP status code, or "wp_error" for a transport failure.
	 * @param array|null $request_args Receives the request arguments by reference.
	 */
	private function fake_tracker_response( $status, &$request_args = null ): void {
		add_filter(
			'pre_http_request',
			function ( $pre, $args ) use ( $status, &$request_args ) {
				$request_args = $args;
				if ( 'wp_error' === $status ) {
					return new WP_Error( 'http_request_failed', 'Timed out' );
				}
				return array(
					'headers'  => array(),
					'body'     => '',
					'response' => array(
						'code'    => $status,
						'message' => '',
					),
				);
			},
			10,
			2
		);
	}

	/**
	 * Inject a fake logger that records warning contexts.
	 *
	 * @return object Fake logger with public `warnings` (contexts) and `messages` arrays.
	 */
	private function expect_tracker_warning() {
		$logger = new class() implements WC_Logger_Interface {
			/**
			 * Recorded warning contexts.
			 *
			 * @var array
			 */
			public $warnings = array();

			/**
			 * Recorded warning messages.
			 *
			 * @var array
			 */
			public $messages = array();

			// phpcs:disable Squiz.Commenting.FunctionComment.Missing, Generic.CodeAnalysis.UnusedFunctionParameter.Found
			public function add( $handle, $message, $level = WC_Log_Levels::NOTICE ) {}
			public function log( $level, $message, $context = array() ) {}
			public function emergency( $message, $context = array() ) {}
			public function alert( $message, $context = array() ) {}
			public function critical( $message, $context = array() ) {}
			public function error( $message, $context = array() ) {}
			public function warning( $message, $context = array() ) {
				$this->warnings[] = $context;
				$this->messages[] = $message;
			}
			public function notice( $message, $context = array() ) {}
			public function info( $message, $context = array() ) {}
			public function debug( $message, $context = array() ) {}
			// phpcs:enable
		};
		add_filter(
			'woocommerce_logging_class',
			static function () use ( $logger ) {
				return $logger;
			}
		);
		return $logger;
	}
	/**
	 * @testDox Test the features compatibility data for plugin tracking data.
	 */
	public function test_get_tracking_data_plugin_feature_compatibility() {
		$legacy_mocks = array(
			'get_plugins' => function () {
				return array(
					'plugin1' => array(
						'Name' => 'Plugin 1',
					),
					'plugin2' => array(
						'Name' => 'Plugin 2',
					),
					'plugin3' => array(
						'Name' => 'Plugin 3',
					),
				);
			},
		);
		$this->register_legacy_proxy_function_mocks( $legacy_mocks );

		update_option( 'active_plugins', array( 'plugin1', 'plugin2' ) );

		$pluginutil_mock = $this->createMock( PluginUtil::class );
		$pluginutil_mock->method( 'is_woocommerce_aware_plugin' )
			->willReturnCallback( fn ( $plugin ) => 'plugin1' === $plugin ? false : true );

		$featurescontroller_mock = $this->createMock( FeaturesController::class );
		$featurescontroller_mock
			->method( 'get_compatible_features_for_plugin' )
			->willReturnCallback(
				function ( $plugin_name ) {
					switch ( $plugin_name ) {
						case 'plugin1':
							return array();
						case 'plugin2':
							return array(
								'compatible'   => array( 'feature1' ),
								'incompatible' => array( 'feature2' ),
								'uncertain'    => array( 'feature3' ),
							);
						case 'plugin3':
							return array(
								'compatible'   => array( 'feature2' ),
								'incompatible' => array(),
								'uncertain'    => array(
									'feature1',
									'feature3',
								),
							);
					}
				}
			);

		$container = wc_get_container();
		$container->get( PluginUtil::class ); // Ensure that the class is loaded.
		$container->replace( PluginUtil::class, $pluginutil_mock );
		$container->replace( FeaturesController::class, $featurescontroller_mock );

		$tracking_data = WC_Tracker::get_tracking_data();

		$this->assertEquals(
			array(),
			$tracking_data['active_plugins']['plugin1']['feature_compatibility']
		);
		$this->assertEquals(
			array(
				'compatible'   => array( 'feature1' ),
				'incompatible' => array( 'feature2' ),
				'uncertain'    => array( 'feature3' ),
			),
			$tracking_data['active_plugins']['plugin2']['feature_compatibility']
		);
		$this->assertEquals(
			array(
				'compatible' => array( 'feature2' ),
				'uncertain'  => array( 'feature1', 'feature3' ),
			),
			$tracking_data['inactive_plugins']['plugin3']['feature_compatibility']
		);

		$this->reset_container_replacements();
		$container->reset_all_resolved();
	}

	/**
	 * @testDox Test orders tracking data.
	 */
	public function test_get_tracking_data_orders() {
		$status_entries         = array( OrderInternalStatus::PROCESSING, OrderInternalStatus::COMPLETED, OrderInternalStatus::REFUNDED, OrderInternalStatus::PENDING );
		$created_via_entries    = array( 'api', 'checkout', 'admin' );
		$payment_method_entries = array( WC_Gateway_Paypal::ID, 'stripe', WC_Gateway_COD::ID );

		$order_count = $this->create_tracking_orders( $status_entries, $created_via_entries, $payment_method_entries );

		$order_data = WC_Tracker::get_tracking_data()['orders'];

		foreach ( $status_entries as $status_entry ) {
			$this->assertEquals( $order_count / count( $status_entries ), $order_data[ $status_entry ] );
		}

		// Gross revenue is for wc-completed and wc-refunded status, so we calculate expected revenue per status, multiply by 2, and then multiply by 10 to account for the 10 USD per status.
		$this->assertEquals( ( $order_count / count( $status_entries ) ) * 2 * 10, $order_data['gross'] );

		// Processing gross revenue covers one status, so multiply the orders per status by the fixed 10 USD total.
		$this->assertEquals( ( $order_count / count( $status_entries ) ) * 1 * 10, $order_data['processing_gross'] );

		$orders_per_gateway = count( $created_via_entries ) * 3;
		foreach ( $payment_method_entries as $payment_method_entry ) {
			$gateway_key = 'gateway_' . $payment_method_entry . '_USD';
			$this->assertEquals( $orders_per_gateway, $order_data[ $gateway_key . '_count' ] );
			$this->assertEquals( $orders_per_gateway * 10, $order_data[ $gateway_key . '_total' ] );
		}

		foreach ( $created_via_entries as $created_via_entry ) {
			$this->assertEquals( ( $order_count / count( $created_via_entries ) ), $order_data['created_via'][ $created_via_entry ] );
		}
	}

	/**
	 * Persist the order matrix read by the tracker aggregate queries.
	 *
	 * @param string[] $statuses        Order statuses.
	 * @param string[] $created_via     Order origins.
	 * @param string[] $payment_methods Payment methods.
	 * @return int Number of inserted orders.
	 */
	private function create_tracking_orders( array $statuses, array $created_via, array $payment_methods ): int {
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order_count = 0;
			foreach ( $statuses as $status ) {
				foreach ( $created_via as $origin ) {
					foreach ( $payment_methods as $payment_method ) {
						$order = wc_create_order(
							array(
								'status'      => $status,
								'created_via' => $origin,
							)
						);
						$order->set_payment_method( $payment_method );
						$order->set_total( 10 );
						$order->save();
						++$order_count;
					}
				}
			}

			return $order_count;
		}

		$order_date = gmdate( 'Y-m-d H:i:s' );
		$orders     = array();

		foreach ( $statuses as $status ) {
			foreach ( $created_via as $origin ) {
				foreach ( $payment_methods as $payment_method ) {
					$orders[] = array(
						'status'         => $status,
						'date'           => $order_date,
						'payment_method' => $payment_method,
						'created_via'    => $origin,
						'recorded_sales' => 0,
					);
				}
			}
		}

		return $this->insert_hpos_tracking_orders( $orders );
	}

	/**
	 * Insert minimal HPOS rows consumed by tracker queries.
	 *
	 * @param array[] $orders Order persistence data.
	 * @return int Number of inserted orders.
	 */
	private function insert_hpos_tracking_orders( array $orders ): int {
		global $wpdb;

		$next_order_id = (int) $wpdb->get_var( "SELECT GREATEST(COALESCE((SELECT MAX(id) FROM {$wpdb->prefix}wc_orders), 0), COALESCE((SELECT MAX(ID) FROM {$wpdb->posts}), 0)) + 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are provided by WordPress.
		$order_rows    = array();
		$order_values  = array();
		$detail_rows   = array();
		$detail_values = array();

		foreach ( $orders as $order ) {
			$order_rows[] = '(%d, %s, %s, %s, %f, %s, %s, %s)';
			array_push( $order_values, $next_order_id, $order['status'], 'USD', 'shop_order', 10, $order['date'], $order['date'], $order['payment_method'] );

			$detail_rows[] = '(%d, %s, %s, %d)';
			array_push( $detail_values, $next_order_id, $order['created_via'], WOOCOMMERCE_VERSION, $order['recorded_sales'] );

			++$next_order_id;
		}

		$order_table    = OrdersTableDataStore::get_orders_table_name();
		$order_columns  = 'id, status, currency, type, total_amount, date_created_gmt, date_updated_gmt, payment_method';
		$detail_table   = OrdersTableDataStore::get_operational_data_table_name();
		$detail_columns = 'order_id, created_via, woocommerce_version, recorded_sales';

		$order_query         = $wpdb->prepare(
			"INSERT INTO {$order_table} ({$order_columns}) VALUES " . implode( ', ', $order_rows ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Table and columns are selected above; placeholders are generated above.
			$order_values
		);
		$order_rows_inserted = $wpdb->query( $order_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.
		$this->assertSame( count( $orders ), $order_rows_inserted, 'Expected every tracker order row to be inserted.' );

		$detail_query         = $wpdb->prepare(
			"INSERT INTO {$detail_table} ({$detail_columns}) VALUES " . implode( ', ', $detail_rows ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Table and columns are selected above; placeholders are generated above.
			$detail_values
		);
		$detail_rows_inserted = $wpdb->query( $detail_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.
		$this->assertSame( count( $orders ), $detail_rows_inserted, 'Expected operational data for every tracker order row.' );

		( new OrderCountCache() )->flush( 'shop_order', array_keys( wc_get_order_statuses() ) );

		return count( $orders );
	}

	/**
	 * @testDox Test order snapshot data.
	 */
	public function test_get_tracking_data_order_snapshot() {
		$year     = gmdate( 'Y' );
		$first_20 = array();
		$last_20  = array();

		// Populate order dates.
		for ( $i = 1; $i <= 20; $i++ ) {
			$first_20[] = sprintf( '%d-02-%02d 12:00:00', $year - 2, $i );
			$last_20[]  = sprintf( '%d-02-%02d 12:00:00', $year + 2, $i );
		}

		$this->create_tracking_snapshot_orders( array_merge( $first_20, $last_20 ) );

		$order_snapshot = WC_Tracker::get_tracking_data()['order_snapshot'];

		$this->assertCount( 20, $order_snapshot['first_20_orders'] );
		$this->assertCount( 20, $order_snapshot['last_20_orders'] );

		// Check order rank for first 20 orders.
		$counter = 1;
		foreach ( $order_snapshot['first_20_orders'] as $order_details ) {
			$this->assertEquals( $order_details['order_rank'], $counter++ );
			$this->assertEquals( $order_details['currency'], 'USD' );
			$this->assertEquals( floatval( $order_details['total_amount'] ), 10.0 );
			$this->assertEquals( $order_details['recorded_sales'], 'yes' );
			$this->assertEquals( $order_details['woocommerce_version'], WOOCOMMERCE_VERSION );
		}

		// Check order rank for last 20 orders.
		$counter = 40;
		foreach ( $order_snapshot['last_20_orders'] as $order_details ) {
			$this->assertEquals( $order_details['order_rank'], $counter-- );
			$this->assertEquals( $order_details['currency'], 'USD' );
			$this->assertEquals( floatval( $order_details['total_amount'] ), 10.00 );
			$this->assertEquals( $order_details['recorded_sales'], 'yes' );
			$this->assertEquals( $order_details['woocommerce_version'], WOOCOMMERCE_VERSION );
		}
	}

	/**
	 * Persist orders read by the first/last order snapshot queries.
	 *
	 * @param string[] $order_dates Order creation dates.
	 */
	private function create_tracking_snapshot_orders( array $order_dates ): void {
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			foreach ( $order_dates as $order_date ) {
				$order = wc_create_order(
					array(
						'status' => OrderInternalStatus::COMPLETED,
					)
				);
				$order->set_date_created( $order_date );
				$order->set_total( 10 );
				$order->save();
			}
			return;
		}

		$orders = array_map(
			static fn( $order_date ) => array(
				'status'         => OrderInternalStatus::COMPLETED,
				'date'           => $order_date,
				'payment_method' => '',
				'created_via'    => 'admin',
				'recorded_sales' => 1,
			),
			$order_dates
		);
		$this->insert_hpos_tracking_orders( $orders );
	}

	/**
	 * @testDox Test enabled features tracking data.
	 */
	public function test_get_tracking_data_enabled_features() {
		$tracking_data = WC_Tracker::get_tracking_data();

		$this->assertIsArray( $tracking_data['enabled_features'] );
	}

	/**
	 * @testDox Test store_id is included in tracking data.
	 */
	public function test_get_tracking_data_store_id() {
		update_option( \WC_Install::STORE_ID_OPTION, '12345' );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertArrayHasKey( 'store_id', $tracking_data );
		$this->assertEquals( '12345', $tracking_data['store_id'] );
		delete_option( \WC_Install::STORE_ID_OPTION );
	}

	/**
	 * @testDox Test woocommerce_install_admin_timestamp is included in tracking data.
	 */
	public function test_get_tracking_data_admin_install_timestamp() {
		$time = time();
		update_option( 'woocommerce_admin_install_timestamp', $time );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertArrayHasKey( 'admin_install_timestamp', $tracking_data['settings'] );
		$this->assertEquals( $tracking_data['settings']['admin_install_timestamp'], $time );
		delete_option( 'woocommerce_admin_install_timestamp' );
	}

	/**
	 * @testDox Test tracking data records snapshot generation time.
	 */
	public function test_get_tracking_data_snapshot_generation_time() {
		$this->assertGreaterThan( 0, WC_Tracker::get_tracking_data()['snapshot_generation_time'] );
	}

	/**
	 * @testDox Test woocommerce_allow_tracking related data is included in tracking snapshot.
	 */
	public function test_tracking_data_woocommerce_allow_tracking() {
		$current_woocommerce_allow_tracking = get_option( 'woocommerce_allow_tracking', 'no' );

		// Clear everything.
		update_option( 'woocommerce_allow_tracking', 'no' );
		delete_option( 'woocommerce_allow_tracking_last_modified' );
		delete_option( 'woocommerce_allow_tracking_first_optin' );

		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertArrayHasKey( 'woocommerce_allow_tracking', $tracking_data );
		$this->assertArrayHasKey( 'woocommerce_allow_tracking_last_modified', $tracking_data );
		$this->assertArrayHasKey( 'woocommerce_allow_tracking_first_optin', $tracking_data );

		$this->assertEquals( $tracking_data['woocommerce_allow_tracking'], 'no' );
		$this->assertEquals( $tracking_data['woocommerce_allow_tracking_last_modified'], 'unknown' );
		$this->assertEquals( $tracking_data['woocommerce_allow_tracking_first_optin'], 'unknown' );

		$before = time();
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertEquals( $tracking_data['woocommerce_allow_tracking'], 'yes' );
		$this->assertGreaterThanOrEqual( $before, (int) $tracking_data['woocommerce_allow_tracking_last_modified'] );
		$this->assertGreaterThanOrEqual( $before, (int) $tracking_data['woocommerce_allow_tracking_first_optin'] );

		// first_optin is recorded once on the first opt-in and must never change afterwards.
		$first_optin = (int) get_option( 'woocommerce_allow_tracking_first_optin' );

		// last_modified must be refreshed to the current time on every tracking change. Capturing the
		// time immediately before each update keeps this deterministic without waiting on the clock.
		$before = time();
		update_option( 'woocommerce_allow_tracking', 'no' );
		$tracking_data = WC_Tracker::get_tracking_data();

		$this->assertEquals( $tracking_data['woocommerce_allow_tracking'], 'no' );
		$this->assertGreaterThanOrEqual( $before, (int) $tracking_data['woocommerce_allow_tracking_last_modified'] );
		$this->assertEquals( $first_optin, (int) $tracking_data['woocommerce_allow_tracking_first_optin'] );

		$before = time();
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertEquals( $tracking_data['woocommerce_allow_tracking'], 'yes' );
		$this->assertGreaterThanOrEqual( $before, (int) $tracking_data['woocommerce_allow_tracking_last_modified'] );
		$this->assertEquals( $first_optin, (int) $tracking_data['woocommerce_allow_tracking_first_optin'] );

		// Restore everything as it was.
		update_option( 'woocommerce_allow_tracking', $current_woocommerce_allow_tracking );
		delete_option( 'woocommerce_allow_tracking_last_modified' );
		delete_option( 'woocommerce_allow_tracking_first_optin' );
	}

	/**
	 * @testDox Test address autocomplete tracking data.
	 */
	public function test_get_address_autocomplete_info() {
		// Test when address autocomplete is disabled (default).
		update_option( 'woocommerce_address_autocomplete_enabled', 'no' );
		$data = WC_Tracker::get_address_autocomplete_info();
		$this->assertEquals( 'no', $data['enabled'] );
		$this->assertIsArray( $data['providers'] );
		$this->assertEmpty( $data['providers'] );
		$this->assertEquals( '', $data['preferred_provider'] );

		// Test when address autocomplete is enabled but no providers registered.
		update_option( 'woocommerce_address_autocomplete_enabled', 'yes' );
		$data = WC_Tracker::get_address_autocomplete_info();
		// Should be disabled if no providers are available.
		$this->assertEquals( 'no', $data['enabled'] );
		$this->assertEmpty( $data['providers'] );
		$this->assertEquals( '', $data['preferred_provider'] );

		// Test with a single registered provider and preferred provider set.
		$this->register_mock_address_provider();
		update_option( 'woocommerce_address_autocomplete_provider', 'mock-address-provider' );

		update_option( 'woocommerce_address_autocomplete_enabled', 'yes' );
		$data = WC_Tracker::get_address_autocomplete_info();
		$this->assertEquals( 'yes', $data['enabled'] );
		$this->assertIsArray( $data['providers'] );
		$this->assertCount( 1, $data['providers'] );
		$this->assertContains( 'mock-address-provider', $data['providers'] );
		// Should return the preferred provider we set.
		$this->assertEquals( 'mock-address-provider', $data['preferred_provider'] );

		// Clean up before testing multiple providers.
		remove_all_filters( 'woocommerce_address_providers' );

		// Test with multiple registered providers and different preferred provider.
		$this->register_multiple_mock_address_providers();
		update_option( 'woocommerce_address_autocomplete_provider', 'mock-address-provider-two' );

		$data = WC_Tracker::get_address_autocomplete_info();
		$this->assertEquals( 'yes', $data['enabled'] );
		$this->assertIsArray( $data['providers'] );
		$this->assertCount( 2, $data['providers'] );
		$this->assertContains( 'mock-address-provider', $data['providers'] );
		$this->assertContains( 'mock-address-provider-two', $data['providers'] );
		// Should return the second provider as preferred.
		$this->assertEquals( 'mock-address-provider-two', $data['preferred_provider'] );

		// Test with invalid preferred provider (not in the list).
		update_option( 'woocommerce_address_autocomplete_provider', 'non-existent-provider' );
		$data = WC_Tracker::get_address_autocomplete_info();
		// Should fall back to the first provider when the preferred provider doesn't exist.
		$this->assertEquals( 'mock-address-provider', $data['preferred_provider'] );

		// Test with multiple registered providers but feature disabled.
		$this->register_multiple_mock_address_providers();
		update_option( 'woocommerce_address_autocomplete_enabled', 'no' );
		update_option( 'woocommerce_address_autocomplete_provider', 'mock-address-provider-two' );

		$data = WC_Tracker::get_address_autocomplete_info();
		$this->assertEquals( 'no', $data['enabled'] );
		$this->assertIsArray( $data['providers'] );
		$this->assertCount( 2, $data['providers'] );
		$this->assertContains( 'mock-address-provider', $data['providers'] );
		$this->assertContains( 'mock-address-provider-two', $data['providers'] );
		// Should return the second provider as preferred.
		$this->assertEquals( '', $data['preferred_provider'] );

		// Test with invalid preferred provider (not in the list) when feature is disabled.
		update_option( 'woocommerce_address_autocomplete_provider', 'non-existent-provider' );
		$data = WC_Tracker::get_address_autocomplete_info();
		// Should not fall back to the first provider when the preferred provider doesn't exist.
		$this->assertEquals( '', $data['preferred_provider'] );

		// Clean up.
		delete_option( 'woocommerce_address_autocomplete_enabled' );
		delete_option( 'woocommerce_address_autocomplete_provider' );
		remove_all_filters( 'woocommerce_address_providers' );
		// Re-init address providers to ensure class is clean for other tests.
		wc_get_container()->get( \Automattic\WooCommerce\Internal\AddressProvider\AddressProviderController::class )->init();
	}

	/**
	 * Helper method to register a mock address provider.
	 */
	private function register_mock_address_provider() {
		// Register the provider instance.
		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) {
				$providers[] = new WC_Tracker_Test_MockAddressProvider();
				return $providers;
			}
		);
	}

	/**
	 * Helper method to register multiple mock address providers.
	 */
	private function register_multiple_mock_address_providers() {
		// Register multiple provider instances with different IDs.
		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) {
				$providers[] = new WC_Tracker_Test_MockAddressProvider( 'mock-address-provider', 'Mock Address Provider' );
				$providers[] = new WC_Tracker_Test_MockAddressProvider( 'mock-address-provider-two', 'Mock Address Provider Two' );
				return $providers;
			}
		);
	}
}
