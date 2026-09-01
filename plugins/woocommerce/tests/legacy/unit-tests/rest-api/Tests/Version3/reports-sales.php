<?php
/**
 * Tests for the reports sales REST API.
 *
 * @package WooCommerce\Tests\API
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * WC_Tests_API_Reports_Sales.
 */
class WC_Tests_API_Reports_Sales extends WC_REST_Unit_Test_Case {

	use HPOSToggleTrait;

	/**
	 * Original HPOS usage state.
	 *
	 * @var bool
	 */
	private $original_hpos_usage;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_hpos_usage = OrderUtil::custom_orders_table_usage_is_enabled();
		$this->user                = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		$this->clear_sales_report_cache();
	}

	/**
	 * Restore HPOS state and clear report caches.
	 */
	public function tearDown(): void {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "TRUNCATE {$wpdb->prefix}wc_orders" );
		$wpdb->query( "TRUNCATE {$wpdb->prefix}wc_orders_meta" );
		$wpdb->query( "TRUNCATE {$wpdb->prefix}wc_order_operational_data" );
		$wpdb->query( "TRUNCATE {$wpdb->prefix}wc_order_addresses" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		remove_all_actions( 'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION );
		OrderHelper::toggle_cot_feature_and_usage( $this->original_hpos_usage );

		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		$this->clear_sales_report_cache();
		parent::tearDown();
	}

	/**
	 * Clear the sales report transient and in-memory cache.
	 */
	private function clear_sales_report_cache(): void {
		delete_transient( 'wc_report_sales_by_date' );

		if ( ! class_exists( 'WC_Admin_Report' ) ) {
			return;
		}

		$reflection = new ReflectionClass( WC_Admin_Report::class );
		$property   = $reflection->getProperty( 'cached_results' );
		$property->setAccessible( true );
		$property->setValue( array() );
	}

	/**
	 * @testdox Should return sales totals from HPOS data when sync is disabled.
	 */
	public function test_get_sales_report_with_hpos_enabled_and_sync_off(): void {
		$this->setup_cot();
		$this->disable_cot_sync();

		wp_set_current_user( $this->user );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '100' );
		$product->save();

		for ( $i = 0; $i < 2; $i++ ) {
			$order = wc_create_order();
			$order->add_product( $product, 1 );
			$order->calculate_totals();
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/reports/sales' );
		$request->set_query_params(
			array(
				'date_min' => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
				'date_max' => gmdate( 'Y-m-d', strtotime( '+1 day' ) ),
			)
		);

		$response = $this->server->dispatch( $request );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $report );
		$this->assertSame( 2, $report[0]['total_orders'], 'Sales report should count HPOS orders when sync is disabled.' );
		$this->assertSame( 2, $report[0]['total_items'], 'Sales report should count HPOS order items when sync is disabled.' );
		$this->assertSame( wc_format_decimal( 200, 2 ), $report[0]['total_sales'], 'Sales report total should come from HPOS order totals.' );
	}
}
