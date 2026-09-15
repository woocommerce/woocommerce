<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Products;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Products\DataStore as ProductsDataStore;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for Products report DataStore.
 */
class DataStoreTest extends WC_Unit_Test_Case {

	/**
	 * Custom status slug that exceeds the 20-char status column once 'wc-' prefixed.
	 *
	 * @var string
	 */
	private $long_status = 'competition-completed';

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		register_post_status( 'wc-' . $this->long_status, array( 'public' => true ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_custom_status' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'wc_order_statuses', array( $this, 'add_custom_status' ) );
		delete_option( 'woocommerce_excluded_report_order_statuses' );
		unset( $GLOBALS['wp_post_statuses'][ 'wc-' . $this->long_status ] );
		parent::tearDown();
	}

	/**
	 * Register the custom status with WooCommerce.
	 *
	 * @param array $statuses Registered order statuses.
	 * @return array
	 */
	public function add_custom_status( $statuses ) {
		$statuses[ 'wc-' . $this->long_status ] = 'Competition Completed';
		return $statuses;
	}

	/**
	 * Create a synced order with one product in the custom status.
	 *
	 * @return int Product ID.
	 */
	private function create_synced_custom_status_order() {
		// Core warns when saving a status longer than the 20-char column; that
		// truncated storage is the exact scenario under test.
		$this->setExpectedIncorrectUsage( 'Abstract_WC_Order_Data_Store_CPT::get_post_status' );

		$product = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$order->add_product( $product, 2 );
		$order->calculate_totals();
		$order->set_status( $this->long_status );
		$order->save();

		OrdersStatsDataStore::sync_order( $order->get_id() );
		ProductsDataStore::sync_order_products( $order->get_id() );

		return $product->get_id();
	}

	/**
	 * Query the products report for a single product.
	 *
	 * @param int $product_id Product ID.
	 * @return object Report data.
	 */
	private function get_product_report_data( $product_id ) {
		Cache::invalidate();
		$data_store = new ProductsDataStore();
		return $data_store->get_data(
			array(
				'after'    => gmdate( 'Y-m-d', strtotime( '-1 day' ) ) . 'T00:00:00',
				'before'   => gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . 'T23:59:59',
				'products' => array( $product_id ),
			)
		);
	}

	/**
	 * @testdox Products report should exclude orders whose custom status is excluded, even when the status slug exceeds the 20-char storage limit.
	 */
	public function test_excluded_long_custom_status_is_not_counted(): void {
		$product_id = $this->create_synced_custom_status_order();

		update_option( 'woocommerce_excluded_report_order_statuses', array( 'pending', 'failed', 'cancelled', $this->long_status ) );

		$data       = $this->get_product_report_data( $product_id );
		$items_sold = array_sum( array_map( 'absint', array_column( $data->data, 'items_sold' ) ) );

		$this->assertSame( 0, $items_sold, 'Orders in an excluded custom status should not be counted in the products report' );
	}

	/**
	 * @testdox Products report should count orders in a long custom status when it is not excluded.
	 */
	public function test_non_excluded_long_custom_status_is_counted(): void {
		$product_id = $this->create_synced_custom_status_order();

		update_option( 'woocommerce_excluded_report_order_statuses', array( 'pending', 'failed', 'cancelled' ) );

		$data       = $this->get_product_report_data( $product_id );
		$items_sold = array_sum( array_map( 'absint', array_column( $data->data, 'items_sold' ) ) );

		$this->assertSame( 2, $items_sold, 'Orders in a non-excluded custom status should be counted in the products report' );
	}
}
