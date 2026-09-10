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

	/** Export must not turn an incomplete subtotal into a complete revenue figure. */
	public function test_export_marks_incomplete_revenue(): void {
		$controller = new \Automattic\WooCommerce\Admin\API\Reports\Products\Controller();
		$item       = array(
			'extended_info'            => array(
				'name'         => 'Fixture',
				'sku'          => '',
				'category_ids' => array(),
				'manage_stock' => false,
			),
			'items_sold'               => 2,
			'net_revenue'              => 41.9,
			'orders_count'             => 2,
			'reporting_missing_orders' => 1,
		);
		$export     = $controller->prepare_item_for_export( $item );
		$this->assertSame( 'Unavailable', $export['net_revenue'] );
		$this->assertSame( 2, $export['items_sold'] );
		$item['reporting_missing_orders'] = 0;
		$this->assertSame( 41.9, $controller->prepare_item_for_export( $item )['net_revenue'] );
	}

	/** Completeness survives query projections and preserves nonmonetary counts. */
	public function test_currency_completeness_survives_product_queries(): void {
		$previous_currency = get_option( 'woocommerce_currency' );
		update_option( 'woocommerce_currency', 'EUR' );
		$product = WC_Helper_Product::create_simple_product();
		$unsold  = WC_Helper_Product::create_simple_product();
		$orders  = array();
		try {
			foreach ( array( 'EUR', 'GBP' ) as $currency ) {
				$order = wc_create_order();
				$order->set_currency( $currency );
				$order->add_product( $product, 1 );
				$order->calculate_totals();
				$order->set_status( 'completed' );
				$order->save();
				$orders[] = $order;
				OrdersStatsDataStore::sync_order( $order->get_id() );
				ProductsDataStore::sync_order_products( $order->get_id() );
				if ( 'GBP' === $currency ) {
					// Converted order stats alone cannot qualify a native product lookup.
					global $wpdb;
					$wpdb->update(
						$wpdb->prefix . 'wc_order_stats',
						array(
							'reporting_currency'      => 'EUR',
							'reporting_basis'         => 'historical_processor_rate',
							'reporting_exchange_rate' => 1.2,
						),
						array( 'order_id' => $order->get_id() )
					);
				}
			}
			foreach ( array(
				array(),
				array( 'product_includes' => array( $product->get_id() ) ),
				array(
					'product_includes' => array( $product->get_id() ),
					'fields'           => array( 'product_id', 'items_sold' ),
				),
			) as $selection ) {
				Cache::invalidate();
				$result = ( new ProductsDataStore() )->get_data( $selection );
				$rows   = array_values(
					array_filter(
						$result->data,
						static function ( $row ) use ( $product ) {
							return $product->get_id() === $row['product_id'];
						}
					)
				);
				$this->assertCount( 1, $rows );
				$this->assertSame( 1, $rows[0]['reporting_missing_orders'] );
				$this->assertSame( 2, $rows[0]['items_sold'] );
			}
			Cache::invalidate();
			$result = ( new ProductsDataStore() )->get_data( array( 'product_includes' => array( $unsold->get_id() ) ) );
			$this->assertCount( 1, $result->data );
			$this->assertSame(
				0,
				$result->data[0]['reporting_missing_orders'],
				wp_json_encode(
					array(
						'unsold' => $unsold->get_id(),
						'result' => $result,
					)
				)
			);
			$this->assertSame( 0, $result->data[0]['items_sold'] );
			foreach ( array(
				array(),
				array(
					'fields'    => array( 'net_revenue', 'items_sold' ),
					'segmentby' => 'product',
				),
			) as $selection ) {
				Cache::invalidate();
				$stats = ( new \Automattic\WooCommerce\Admin\API\Reports\Products\Stats\DataStore() )->get_data( $selection );
				$this->assertSame( 1, $stats->totals->reporting_missing_orders );
				$this->assertSame( 2, $stats->totals->items_sold );
				$this->assertNotEmpty( $stats->intervals );
				$this->assertSame( 1, $stats->intervals[0]['subtotals']->reporting_missing_orders );
				if ( isset( $selection['segmentby'] ) ) {
					$this->assertCount( 2, $stats->totals->segments );
					$missing = array_map(
						static function ( $segment ) {
							return ( (array) $segment['subtotals'] )['reporting_missing_orders'];
						},
						$stats->totals->segments
					);
					sort( $missing );
					$this->assertSame( array( 0, 1 ), $missing );
				}
			}
		} finally {
			foreach ( $orders as $order ) {
				$order->delete( true );
			}
			$product->delete( true );
			$unsold->delete( true );
			update_option( 'woocommerce_currency', $previous_currency );
			Cache::invalidate();
		}
	}

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
