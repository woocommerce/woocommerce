<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_Shipping_Rate;
use WC_Tax;

/**
 * Tests for cache priming in OrdersTableDataStore.
 */
class OrdersTableDataStoreCachePrimingTest extends \HposTestCase {
	use HPOSToggleTrait;

	/**
	 * The System Under Test.
	 *
	 * @var OrdersTableDataStore
	 */
	private $sut;

	/**
	 * Whether COT was enabled before the test.
	 *
	 * @var bool
	 */
	private $cot_state;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

		$this->setup_cot();
		$this->cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		$this->toggle_cot_feature_and_usage( true );

		$container = wc_get_container();
		$container->reset_all_resolved();
		$this->sut = $container->get( OrdersTableDataStore::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->toggle_cot_feature_and_usage( $this->cot_state );
		$this->clean_up_cot_setup();

		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
		parent::tearDown();
	}

	/**
	 * @testdox Cache priming populates refund total and tax caches with correct values.
	 */
	public function test_prime_caches_for_orders_primes_refund_totals(): void {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate'          => '20',
				'tax_rate_name'     => 'tax',
				'tax_rate_order'    => '1',
				'tax_rate_shipping' => '1',
			)
		);

		$rate = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);

		$order = new WC_Order();
		$order->save();
		$order->add_product( WC_Helper_Product::create_simple_product(), 10 );
		$order->add_item( $item );
		$order->calculate_totals();
		$order->save();

		$product_item_id  = current( $order->get_items() )->get_id();
		$shipping_item_id = current( $order->get_items( 'shipping' ) )->get_id();

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					$product_item_id  => array(
						'id'           => $product_item_id,
						'qty'          => 1,
						'refund_total' => 10,
						'refund_tax'   => array( 1 => 2 ),
					),
					$shipping_item_id => array(
						'id'           => $shipping_item_id,
						'qty'          => 1,
						'refund_total' => 10,
						'refund_tax'   => array( 1 => 3 ),
					),
				),
			)
		);

		// Clear all caches so prime_caches_for_orders has to do real work.
		wp_cache_flush();
		\WC_Cache_Helper::invalidate_cache_group( 'orders' );

		$this->sut->prime_caches_for_orders(
			array( $order->get_id() ),
			array(
				'fields' => 'all',
				'type'   => 'shop_order',
			)
		);

		// Verify refund total caches were primed with correct values.
		$cache_prefix = \WC_Cache_Helper::get_cache_prefix( 'orders' );
		$order_id     = $order->get_id();

		$cached_total_refunded = wp_cache_get( $cache_prefix . 'total_refunded' . $order_id, 'orders' );
		$cached_tax_refunded   = wp_cache_get( $cache_prefix . 'total_tax_refunded' . $order_id, 'orders' );

		$this->assertNotFalse( $cached_total_refunded, 'Total refunded should be cached after priming' );
		$this->assertNotFalse( $cached_tax_refunded, 'Total tax refunded should be cached after priming' );
		$this->assertIsFloat( $cached_total_refunded, 'Cached total refunded should be a float' );
		$this->assertEquals( 5.0, $cached_tax_refunded, 'Cached tax refunded should equal sum of product tax (2) + shipping tax (3)' );
	}

	/**
	 * @testdox Cache priming populates order item meta caches so item access does not trigger additional queries.
	 */
	public function test_prime_caches_for_orders_primes_item_meta(): void {
		$order = new WC_Order();
		$order->save();
		$order->add_product( WC_Helper_Product::create_simple_product(), 2 );
		$order->calculate_totals();
		$order->save();

		// Clear all caches.
		wp_cache_flush();
		\WC_Cache_Helper::invalidate_cache_group( 'orders' );

		$this->sut->prime_caches_for_orders(
			array( $order->get_id() ),
			array(
				'fields' => 'all',
				'type'   => 'shop_order',
			)
		);

		// Reload order and access items — meta should already be cached.
		$reloaded_order = wc_get_order( $order->get_id() );
		$items          = $reloaded_order->get_items();

		$this->assertNotEmpty( $items, 'Order should have line items' );

		foreach ( $items as $item ) {
			$this->assertGreaterThan( 0, $item->get_product_id(), 'Item should have a product ID from cached meta' );
		}
	}
}
