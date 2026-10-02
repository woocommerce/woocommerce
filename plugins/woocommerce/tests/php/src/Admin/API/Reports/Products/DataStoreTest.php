<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Products;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Products\DataStore as ProductsDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
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
				'after'         => gmdate( 'Y-m-d', strtotime( '-1 day' ) ) . 'T00:00:00',
				'before'        => gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . 'T23:59:59',
				'products'      => array( $product_id ),
				'extended_info' => true,
			)
		);
	}

	/**
	 * Create a simple product.
	 *
	 * @param string $name Product name.
	 * @return WC_Product_Simple
	 */
	private function create_product( $name ) {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( $name );
		$product->save();

		return $product;
	}

	/**
	 * Create a completed order holding the given products, and sync it into the report tables.
	 *
	 * @param WC_Product $product    Product to sell.
	 * @param int        $quantity   Optional. Quantity of the product in the order.
	 * @param string     $created_on Optional. Creation date of the order, in a date() format.
	 * @return WC_Order
	 */
	private function create_synced_completed_order( $product, $quantity = 2, $created_on = 'now' ) {
		$order = wc_create_order();
		$order->add_product( $product, $quantity );
		$order->calculate_totals();
		$order->set_date_created( strtotime( $created_on ) );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		OrdersStatsDataStore::sync_order( $order->get_id() );
		ProductsDataStore::sync_order_products( $order->get_id() );

		return $order;
	}

	/**
	 * Query the products report for the unsold products of a period.
	 *
	 * @param string $after  Period start, in a date() format.
	 * @param string $before Period end, in a date() format.
	 * @param array  $extra  Optional. Extra query arguments.
	 * @return object Report data.
	 */
	private function get_unsold_report_data( $after, $before, array $extra = array() ) {
		Cache::invalidate();
		$data_store = new ProductsDataStore();

		return $data_store->get_data(
			array_merge(
				array(
					'after'    => gmdate( 'Y-m-d', strtotime( $after ) ) . 'T00:00:00',
					'before'   => gmdate( 'Y-m-d', strtotime( $before ) ) . 'T23:59:59',
					'unsold'   => true,
					'orderby'  => 'product_name',
					'order'    => 'asc',
					'per_page' => 100,
				),
				$extra
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

	/**
	 * @testdox Products report with the unsold filter should return products with no sales in the period.
	 */
	public function test_unsold_filter_returns_products_without_sales_in_the_period(): void {
		$sold   = $this->create_product( 'Alpha Widget' );
		$unsold = $this->create_product( 'Beta Widget' );

		$this->create_synced_completed_order( $sold );

		$data  = $this->get_unsold_report_data( '-1 day', '+1 day' );
		$by_id = array_column( $data->data, null, 'product_id' );

		$this->assertSame( 1, $data->total, 'Only the product without sales should be reported' );
		$this->assertArrayHasKey( $unsold->get_id(), $by_id, 'The product without sales should be reported' );
		$this->assertArrayNotHasKey( $sold->get_id(), $by_id, 'The product with sales should be left out' );

		$row = $by_id[ $unsold->get_id() ];

		$this->assertSame( 0, $row['items_sold'], 'An unsold product should report zero items sold' );
		$this->assertSame( 0.0, $row['net_revenue'], 'An unsold product should report zero net revenue' );
		$this->assertSame( 0, $row['orders_count'], 'An unsold product should report zero orders' );
		$this->assertNull( $row['last_sold'], 'An unsold product should report no last sold date' );
	}

	/**
	 * @testdox Products report with the unsold filter should leave out a product whose sale falls outside the period.
	 */
	public function test_unsold_filter_ignores_sales_outside_the_period(): void {
		$product = $this->create_product( 'Alpha Widget' );

		$this->create_synced_completed_order( $product, 2, '-10 days' );

		$data = $this->get_unsold_report_data( '-1 day', '+1 day' );

		$this->assertSame( 1, $data->total, 'A product sold outside the period should be reported as unsold' );
		$this->assertSame( $product->get_id(), (int) $data->data[0]['product_id'] );
		$this->assertNull( $data->data[0]['last_sold'], 'A product sold outside the period should report no last sold date for the period' );
	}

	/**
	 * @testdox Products report should return the last sold date and lifetime sales of a product with sales.
	 *
	 * The first order falls outside the report period, so its units count toward lifetime sales
	 * only.
	 */
	public function test_last_sold_and_lifetime_sales_are_reported_for_sold_products(): void {
		$product = $this->create_product( 'Alpha Widget' );

		$this->create_synced_completed_order( $product, 3, '-10 days' );
		$this->create_synced_completed_order( $product, 5, '-1 day' );

		$data = $this->get_product_report_data( $product->get_id() );
		$row  = $data->data[0];

		$this->assertSame( 5, $row['items_sold'], 'Only the order inside the period should be counted' );
		$this->assertSame(
			gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
			gmdate( 'Y-m-d', strtotime( $row['last_sold'] ) ),
			'The last sold date should be the date of the latest order in the period'
		);
		$this->assertSame(
			8,
			$row['extended_info']['total_sales'],
			'Lifetime sales should count every unit ever sold, not just the ones in the period'
		);
	}

	/**
	 * @testdox Products report should not report a refund as the last sold date.
	 *
	 * The refund lands after the order, on the refund's own date, so a bare MAX over the
	 * period's rows would report the refund as the last sale.
	 */
	public function test_last_sold_ignores_refund_rows(): void {
		$product = $this->create_product( 'Alpha Widget' );

		$order      = $this->create_synced_completed_order( $product, 2, '-3 days' );
		$order_item = reset( $order->get_items() );

		$refund = wc_create_refund(
			array(
				'amount'     => 10,
				'order_id'   => $order->get_id(),
				'line_items' => array(
					$order_item->get_id() => array(
						'qty'          => 1,
						'refund_total' => 10,
					),
				),
			)
		);

		$this->assertNotWPError( $refund, 'Refund creation should succeed' );

		OrdersStatsDataStore::sync_order( $refund->get_id() );
		ProductsDataStore::sync_order_products( $refund->get_id() );

		Cache::invalidate();
		$data_store = new ProductsDataStore();
		$data       = $data_store->get_data(
			array(
				// Covers both the order and the refund.
				'after'  => gmdate( 'Y-m-d', strtotime( '-4 days' ) ) . 'T00:00:00',
				'before' => gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . 'T23:59:59',
			)
		);

		$this->assertSame(
			gmdate( 'Y-m-d', strtotime( '-3 days' ) ),
			gmdate( 'Y-m-d', strtotime( $data->data[0]['last_sold'] ) ),
			'The refund row should not count as the last sale'
		);
	}

	/**
	 * @testdox Products report with the unsold filter should leave out a product sold in an excluded order status.
	 */
	public function test_unsold_filter_respects_excluded_order_statuses(): void {
		$product = $this->create_product( 'Alpha Widget' );

		$order = wc_create_order();
		$order->add_product( $product, 2 );
		$order->calculate_totals();
		$order->set_status( OrderStatus::CANCELLED );
		$order->save();

		OrdersStatsDataStore::sync_order( $order->get_id() );
		ProductsDataStore::sync_order_products( $order->get_id() );

		$data  = $this->get_unsold_report_data( '-1 day', '+1 day' );
		$by_id = array_column( $data->data, null, 'product_id' );

		$this->assertArrayHasKey( $product->get_id(), $by_id, 'A product whose only orders were cancelled should still be reported as unsold' );
	}

	/**
	 * @testdox Products report with the unsold filter should page through the products without sales.
	 */
	public function test_unsold_filter_pagination(): void {
		// The report orders by title, so the expected page contents follow the names, not the
		// order the products were created in.
		$unsold_by_name = array(
			'Alpha Widget' => null,
			'Beta Widget'  => null,
			'Delta Widget' => null,
			'Gamma Widget' => null,
		);
		foreach ( $unsold_by_name as $name => $unused ) {
			$unsold_by_name[ $name ] = $this->create_product( $name )->get_id();
		}

		$data = $this->get_unsold_report_data(
			'-1 day',
			'+1 day',
			array(
				'per_page' => 2,
				'page'     => 2,
			)
		);

		$this->assertSame( 4, $data->total, 'All products without sales should be counted' );
		$this->assertSame( 2, $data->pages );
		$this->assertSame( 2, count( $data->data ) );
		$this->assertSame( array_slice( array_values( $unsold_by_name ), 2 ), array_map( 'absint', array_column( $data->data, 'product_id' ) ), 'The second page should hold the remaining products in title order' );
	}
}
