<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Products\DataStore as ProductsDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductSales;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductSales class.
 */
class ProductSalesTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductSales
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// Orders other tests left in the Analytics tables would count too.
		\WC_Helper_Reports::reset_stats_dbs();
		update_option( 'woocommerce_analytics_enabled', 'yes' );
		$this->sut = new ProductSales();
	}

	/**
	 * @testdox Sales add up per product over the last 90 days, a variation counting toward its parent, leaving out statuses Analytics excludes and older orders.
	 */
	public function test_last_days_sums_sales_per_product(): void {
		$mug      = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 10 ) );
		$vase     = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 30 ) );
		$variable = WC_Helper_Product::create_variation_product();
		$children = $variable->get_children();
		$variant  = wc_get_product( $children[0] );

		$this->order( array( array( $mug, 2 ), array( $variant, 1 ) ), OrderStatus::COMPLETED );
		$this->order( array( array( $vase, 1 ) ), OrderStatus::CANCELLED );
		$this->order( array( array( $vase, 1 ) ), OrderStatus::PROCESSING, time() - 120 * DAY_IN_SECONDS );

		$sales = $this->sut->last_days();

		$this->assertIsArray( $sales );
		$this->assertSame( 90, $sales['days'] );
		$this->assertSame( 1, $sales['orders'], 'Only the completed order in the period counts' );
		$this->assertEqualsWithDelta( 20.0, $sales['by_product'][ $mug->get_id() ] ?? 0.0, 0.01 );
		$this->assertGreaterThan( 0.0, (float) $variant->get_price(), 'The variation has a price, so its sales show' );
		$this->assertArrayHasKey( $variable->get_id(), $sales['by_product'], 'A variation counts toward its parent' );
		$this->assertEqualsWithDelta( (float) $variant->get_price(), $sales['by_product'][ $variable->get_id() ], 0.01 );
		$this->assertArrayNotHasKey( $variant->get_id(), $sales['by_product'] );
		$this->assertArrayNotHasKey( $vase->get_id(), $sales['by_product'], 'Cancelled and older orders are left out' );
		$this->assertEqualsWithDelta( 20.0 + (float) $variant->get_price(), $sales['total'], 0.01 );
	}

	/**
	 * @testdox A refund takes its amount off the product's sales, as Analytics nets it, and doesn't count as another order.
	 */
	public function test_last_days_nets_refunds(): void {
		$mug    = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 10 ) );
		$order  = $this->order( array( array( $mug, 2 ) ), OrderStatus::COMPLETED );
		$items  = $order->get_items();
		$item   = reset( $items );
		$refund = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 10,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 1,
						'refund_total' => 10,
					),
				),
			)
		);
		$this->assertInstanceOf( \WC_Order_Refund::class, $refund );
		OrdersStatsDataStore::sync_order( $refund->get_id() );
		ProductsDataStore::sync_order_products( $refund->get_id() );

		$sales = $this->sut->last_days();

		$this->assertEqualsWithDelta( 10.0, $sales['by_product'][ $mug->get_id() ] ?? 0.0, 0.01, 'Two sold, one refunded' );
		$this->assertSame( 1, $sales['orders'] );
	}

	/**
	 * @testdox With no orders the total is zero; with Analytics off there are no sales to read.
	 */
	public function test_last_days_without_sales_or_analytics(): void {
		$sales = $this->sut->last_days();
		$this->assertIsArray( $sales );
		$this->assertSame( 0, $sales['orders'] );
		$this->assertSame( 0.0, $sales['total'] );

		update_option( 'woocommerce_analytics_enabled', 'no' );
		$this->assertNull( $this->sut->last_days() );
	}

	/**
	 * @testdox A filter that excludes no statuses counts every order, and statuses past the column width are cut to match it.
	 */
	public function test_last_days_with_no_or_long_excluded_statuses(): void {
		$mug = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 10 ) );
		$this->order( array( array( $mug, 1 ) ), OrderStatus::COMPLETED );
		$this->order( array( array( $mug, 1 ) ), OrderStatus::CANCELLED );
		add_filter( 'woocommerce_analytics_excluded_order_statuses', '__return_empty_array' );

		$this->assertSame( 2, $this->sut->last_days()['orders'], 'Nothing excluded' );

		remove_filter( 'woocommerce_analytics_excluded_order_statuses', '__return_empty_array' );
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'wc_order_stats', array( 'status' => 'wc-awaiting-collecti' ), array( 'status' => 'wc-cancelled' ) );
		$long = static fn(): array => array( 'awaiting-collection-at-store' );
		add_filter( 'woocommerce_analytics_excluded_order_statuses', $long );
		try {
			$this->assertSame( 1, $this->sut->last_days()['orders'], 'The long status, stored cut short, is still left out' );
		} finally {
			remove_filter( 'woocommerce_analytics_excluded_order_statuses', $long );
		}
	}

	/**
	 * @testdox The 90-day window follows the site's time zone, as the lookup tables store local dates.
	 */
	public function test_last_days_window_in_site_time_zone(): void {
		$mug = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 10 ) );
		try {
			update_option( 'timezone_string', 'Pacific/Auckland' );
			$this->order( array( array( $mug, 1 ) ), OrderStatus::COMPLETED, time() - 90 * DAY_IN_SECONDS - 6 * HOUR_IN_SECONDS );
			$this->order( array( array( $mug, 2 ) ), OrderStatus::COMPLETED, time() - 90 * DAY_IN_SECONDS + 6 * HOUR_IN_SECONDS );

			$sales = $this->sut->last_days();

			$this->assertSame( 1, $sales['orders'], 'The older order is out, though its local date reads later than the UTC boundary' );
			$this->assertEqualsWithDelta( 20.0, $sales['by_product'][ $mug->get_id() ] ?? 0.0, 0.01 );
		} finally {
			delete_option( 'timezone_string' );
		}
	}

	/**
	 * Create an order and sync it into the Analytics lookup tables.
	 *
	 * @param array<int, array{0:\WC_Product,1:int}> $lines   Products and quantities.
	 * @param string                                 $status  Order status.
	 * @param int|null                               $created When the order was created, or now.
	 * @return \WC_Order
	 */
	private function order( array $lines, string $status, ?int $created = null ): \WC_Order {
		$order = wc_create_order();
		foreach ( $lines as $line ) {
			$order->add_product( $line[0], $line[1] );
		}
		$order->set_status( $status );
		if ( null !== $created ) {
			$order->set_date_created( $created );
		}
		$order->calculate_totals();
		$order->save();
		OrdersStatsDataStore::sync_order( $order->get_id() );
		ProductsDataStore::sync_order_products( $order->get_id() );
		return $order;
	}
}
