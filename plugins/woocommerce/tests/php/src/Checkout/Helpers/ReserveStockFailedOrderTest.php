<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Checkout\Helpers;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Product;
use WC_Order;
use WC_Product;
use WC_Product_Simple;
use WC_Unit_Test_Case;

/**
 * Tests for the failed order stock rule and reservation on ReserveStock.
 */
class ReserveStockFailedOrderTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	private const OUT_OF_STOCK_CODE = 'woocommerce_rest_out_of_stock';

	private const PAYABLE_STATUS = 'awaiting-pay';

	/**
	 * The System Under Test.
	 *
	 * @var ReserveStock
	 */
	private $sut;

	/**
	 * Whether HPOS was enabled before the test.
	 *
	 * @var bool
	 */
	private $prev_hpos_enabled;

	/**
	 * Ensure permanent HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::setup_cot_tables();
	}

	/**
	 * Set up a store that manages and holds stock.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->prev_hpos_enabled = OrderUtil::custom_orders_table_usage_is_enabled();
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		update_option( 'woocommerce_manage_stock', 'yes' );
		update_option( 'woocommerce_schema_version', 430 );
		update_option( 'woocommerce_hold_stock_minutes', 60 );

		$this->sut = new ReserveStock();
	}

	/**
	 * Leave no hold row behind and put the orders storage back.
	 */
	public function tearDown(): void {
		global $wpdb;

		try {
			$wpdb->query( "DELETE FROM {$wpdb->wc_reserved_stock}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			$this->clean_up_cot_setup();
			$this->toggle_cot_feature_and_usage( $this->prev_hpos_enabled );
			remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Both orders storages.
	 *
	 * @return array<string, array{bool}>
	 */
	public function provider_hpos(): array {
		return array(
			'hpos on'  => array( true ),
			'hpos off' => array( false ),
		);
	}

	/**
	 * @testdox A hold on a failed order counts for other orders and not for the order itself.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_hold_on_failed_order_counts_for_other_orders( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 5 );
		$order   = $this->create_order( $product, 3 );

		$this->sut->reserve_stock_for_order( $order );

		$this->assertSame( 3, wc_get_held_stock_quantity( $product ) );
		$this->assertSame( 0, wc_get_held_stock_quantity( $product, $order->get_id() ) );
	}

	/**
	 * @testdox The failed status releases the hold of a pending order.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_failed_status_releases_hold( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 5 );
		$order   = $this->create_order( $product, 3, OrderStatus::PENDING );
		$this->sut->reserve_stock_for_order( $order );
		$this->assertCount( 1, $this->get_hold_rows( $order->get_id() ), 'Setup: the pending order holds stock.' );

		$order->update_status( OrderStatus::FAILED );

		$this->assertCount( 0, $this->get_hold_rows( $order->get_id() ) );
		$this->assertSame( 0, wc_get_held_stock_quantity( $product ) );
	}

	/**
	 * @testdox The rule returns the out of stock error when the stock is gone.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_rule_returns_error_when_stock_is_gone( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 1 );
		$order   = $this->create_order( $product, 2 );

		$error = $this->sut->get_pay_order_stock_error( $order );

		$this->assertIsArray( $error );
		$this->assertSame( self::OUT_OF_STOCK_CODE, $error['code'] );
		$this->assertStringContainsString( 'in stock to fulfill your order (1 available)', $error['message'] );
	}

	/**
	 * @testdox The rule counts the holds of other orders against the stock.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_rule_counts_other_holds( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 5 );
		$order_a = $this->create_order( $product, 3 );
		$order_b = $this->create_order( $product, 3, OrderStatus::PENDING );
		$this->sut->reserve_stock_for_order( $order_b );

		$error = $this->sut->get_pay_order_stock_error( $order_a );

		$this->assertIsArray( $error );
		$this->assertSame( self::OUT_OF_STOCK_CODE, $error['code'] );
		$this->assertStringContainsString( '(2 available)', $error['message'] );
	}

	/**
	 * @testdox The rule ignores a pending order.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_rule_ignores_pending_order( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 5 );
		$order   = $this->create_order( $product, 9, OrderStatus::PENDING );

		$this->assertNull( $this->sut->get_pay_order_stock_error( $order ) );
	}

	/**
	 * @testdox The rule runs for a custom payable status but the reservation does not.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_rule_runs_for_custom_payable_status( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$this->register_payable_status( self::PAYABLE_STATUS );
		$product = $this->create_product( 5 );
		$order   = $this->create_order( $product, 9, self::PAYABLE_STATUS );
		$this->assertTrue( $order->has_status( self::PAYABLE_STATUS ), 'Setup: the order keeps the custom status.' );

		$error = $this->sut->get_pay_order_stock_error( $order );
		$this->assertIsArray( $error );
		$this->assertSame( self::OUT_OF_STOCK_CODE, $error['code'] );

		$this->assert_reserve_throws( $order );

		$this->set_stock( $product, 9 );
		$this->sut->reserve_stock_for_failed_order( wc_get_order( $order->get_id() ) );

		$this->assertCount( 0, $this->get_hold_rows( $order->get_id() ) );
	}

	/**
	 * @testdox The rule and the reservation skip an order whose stock was reduced.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_rule_skips_order_with_reduced_stock( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 5 );
		$order   = $this->create_order( $product, 9 );
		$order->get_data_store()->set_stock_reduced( $order->get_id(), true );
		$order = wc_get_order( $order->get_id() );

		$this->assertNull( $this->sut->get_pay_order_stock_error( $order ) );

		$this->sut->reserve_stock_for_failed_order( $order );

		$this->assertCount( 0, $this->get_hold_rows( $order->get_id() ) );
	}

	/**
	 * @testdox The reservation inserts one hold row for a failed order and keeps its status.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_reserve_inserts_hold_for_failed_order( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 5 );
		$order   = $this->create_order( $product, 3 );

		$this->sut->reserve_stock_for_failed_order( $order );

		$rows = $this->get_hold_rows( $order->get_id() );
		$this->assertCount( 1, $rows );
		$this->assertSame( $product->get_id(), (int) $rows[0]['product_id'] );
		$this->assertSame( 3, (int) $rows[0]['stock_quantity'] );
		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::FAILED ) );
	}

	/**
	 * @testdox The reservation throws and inserts nothing when the rule fails.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_reserve_throws_and_inserts_nothing_when_rule_fails( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 1 );
		$order   = $this->create_order( $product, 2 );

		$exception = $this->assert_reserve_throws( $order );

		$this->assertSame( 400, $exception->getCode() );
		$this->assertCount( 0, $this->get_hold_rows( $order->get_id() ) );
	}

	/**
	 * @testdox A second failed order is refused once the first one holds the stock.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_second_failed_order_is_refused_after_first_reserves( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 5 );
		$order_a = $this->create_order( $product, 5 );
		$order_b = $this->create_order( $product, 5 );

		$this->sut->reserve_stock_for_failed_order( $order_a );
		$this->assertCount( 1, $this->get_hold_rows( $order_a->get_id() ) );

		$this->assert_reserve_throws( $order_b );
		$this->assertCount( 0, $this->get_hold_rows( $order_b->get_id() ) );
	}

	/**
	 * @testdox A retry of the same order refreshes its own hold instead of failing on it, the zero affected rows fallback is covered by test_retry_keeps_own_hold_when_stock_dropped_below_it.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_retry_of_same_order_refreshes_own_hold( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 5 );
		$order   = $this->create_order( $product, 5 );

		$this->sut->reserve_stock_for_failed_order( $order );
		$this->sut->reserve_stock_for_failed_order( $order );

		$rows = $this->get_hold_rows( $order->get_id() );
		$this->assertCount( 1, $rows );
		$this->assertSame( 5, (int) $rows[0]['stock_quantity'] );
	}

	/**
	 * @testdox A retry through reserve_stock_for_order() keeps its own hold when the stock dropped below it, the failed order path refuses that retry through the rule.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_retry_keeps_own_hold_when_stock_dropped_below_it( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product = $this->create_product( 5 );
		$order   = $this->create_order( $product, 5 );
		$this->sut->reserve_stock_for_failed_order( $order );

		$this->set_stock( $product, 3 );
		$this->sut->reserve_stock_for_order( wc_get_order( $order->get_id() ) );

		$rows = $this->get_hold_rows( $order->get_id() );
		$this->assertCount( 1, $rows );
		$this->assertSame( 5, (int) $rows[0]['stock_quantity'] );
	}

	/**
	 * @testdox With holds off, the rule still runs but nothing is reserved.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_holds_off_runs_rule_only( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		add_filter( 'woocommerce_hold_stock_for_checkout', '__return_false' );

		$this->assert_reserve_throws( $this->create_order( $this->create_product( 1 ), 2 ) );

		$order = $this->create_order( $this->create_product( 5 ), 2 );
		$this->sut->reserve_stock_for_failed_order( $order );
		$this->assertCount( 0, $this->get_hold_rows( $order->get_id() ) );

		remove_filter( 'woocommerce_hold_stock_for_checkout', '__return_false' );
		update_option( 'woocommerce_hold_stock_minutes', '' );

		$order = $this->create_order( $this->create_product( 5 ), 2 );
		$this->sut->reserve_stock_for_failed_order( $order );
		$this->assertCount( 0, $this->get_hold_rows( $order->get_id() ) );
	}

	/**
	 * @testdox A product the enough stock filter allows gets no hold, the others still do.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_filter_allowed_product_gets_no_hold( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product_p = $this->create_product( 1 );
		$product_q = $this->create_product( 5 );
		$order     = $this->create_order( $product_p, 3 );
		$order->add_product( $product_q, 2 );
		$order->save();

		add_filter(
			'woocommerce_pay_order_product_has_enough_stock',
			function ( $has_enough_stock, WC_Product $product ) use ( $product_p ) {
				return $product->get_id() === $product_p->get_id() ? true : $has_enough_stock;
			},
			10,
			2
		);

		$this->sut->reserve_stock_for_failed_order( $order );

		$rows = $this->get_hold_rows( $order->get_id() );
		$this->assertCount( 1, $rows );
		$this->assertSame( $product_q->get_id(), (int) $rows[0]['product_id'] );
		$this->assertSame( 2, (int) $rows[0]['stock_quantity'] );
	}

	/**
	 * @testdox A product the in stock filter allows gets no hold, the others still do.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_in_stock_filter_allowed_product_gets_no_hold( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$product_p = WC_Helper_Product::create_simple_product( true, array( 'stock_status' => ProductStockStatus::OUT_OF_STOCK ) );
		$product_q = $this->create_product( 5 );
		$order     = $this->create_order( $product_p, 1 );
		$order->add_product( $product_q, 2 );
		$order->save();

		add_filter(
			'woocommerce_pay_order_product_in_stock',
			function ( $is_in_stock, WC_Product $product ) use ( $product_p ) {
				return $product->get_id() === $product_p->get_id() ? true : $is_in_stock;
			},
			10,
			2
		);

		$this->sut->reserve_stock_for_failed_order( $order );

		$rows = $this->get_hold_rows( $order->get_id() );
		$this->assertCount( 1, $rows );
		$this->assertSame( $product_q->get_id(), (int) $rows[0]['product_id'] );
	}

	/**
	 * @testdox Variations of one parent share its stock in the rule and in the hold.
	 * @dataProvider provider_hpos
	 *
	 * @param bool $hpos Whether the orders table is authoritative.
	 */
	public function test_variations_share_parent_stock( bool $hpos ): void {
		$this->toggle_cot_authoritative( $hpos );
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_manage_stock( true );
		$parent->set_stock_quantity( 3 );
		$parent->save();
		$variations = array_map( 'wc_get_product', array_slice( $parent->get_children(), 0, 2 ) );

		$order = $this->create_order( $variations[0], 2 );
		$order->add_product( $variations[1], 2 );
		$order->save();

		$error = $this->sut->get_pay_order_stock_error( $order );
		$this->assertIsArray( $error );
		$this->assertStringContainsString( '(3 available)', $error['message'] );

		$this->set_stock( $parent, 4 );
		// A parent save does not evict cached variation instances, which keep the parent stock they were read with.
		foreach ( $variations as $variation ) {
			wc_get_container()->get( ProductCache::class )->remove( $variation->get_id() );
		}
		$this->sut->reserve_stock_for_failed_order( wc_get_order( $order->get_id() ) );

		$rows = $this->get_hold_rows( $order->get_id() );
		$this->assertCount( 1, $rows );
		$this->assertSame( $parent->get_id(), (int) $rows[0]['product_id'] );
		$this->assertSame( 4, (int) $rows[0]['stock_quantity'] );
	}

	/**
	 * Asserts that the reservation refuses the order with the out of stock code.
	 *
	 * @param WC_Order $order Order to reserve.
	 * @return ReserveStockException The exception thrown.
	 */
	private function assert_reserve_throws( WC_Order $order ): ReserveStockException {
		try {
			$this->sut->reserve_stock_for_failed_order( $order );
		} catch ( ReserveStockException $e ) {
			$this->assertSame( self::OUT_OF_STOCK_CODE, $e->getErrorCode() );
			return $e;
		}

		$this->fail( 'Expected reserve_stock_for_failed_order() to throw.' );
	}

	/**
	 * A simple product that manages a fixed stock, with backorders off.
	 *
	 * @param int $stock Stock quantity.
	 * @return WC_Product_Simple
	 */
	private function create_product( int $stock ): WC_Product_Simple {
		return WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => $stock,
				'backorders'     => 'no',
			)
		);
	}

	/**
	 * Sets a new stock quantity and reloads the product.
	 *
	 * @param WC_Product $product Product to update.
	 * @param int        $stock   New stock quantity.
	 */
	private function set_stock( WC_Product $product, int $stock ): void {
		$product->set_stock_quantity( $stock );
		$product->save();
	}

	/**
	 * A guest order with one line item.
	 *
	 * @param WC_Product $product  Product to add.
	 * @param int        $quantity Line quantity.
	 * @param string     $status   Order status, failed by default.
	 * @return WC_Order
	 */
	private function create_order( WC_Product $product, int $quantity, string $status = OrderStatus::FAILED ): WC_Order {
		$order = new WC_Order();
		$order->set_billing_email( 'shopper@example.com' );
		$order->add_product( $product, $quantity );
		$order->set_status( $status );
		$order->calculate_totals();
		$order->save();

		return $order;
	}

	/**
	 * Registers a custom status the shopper can pay from.
	 *
	 * @param string $status Status slug without the wc- prefix.
	 */
	private function register_payable_status( string $status ): void {
		register_post_status(
			'wc-' . $status,
			array(
				'label'  => 'Awaiting pay',
				'public' => false,
			)
		);
		add_filter(
			'wc_order_statuses',
			function ( $statuses ) use ( $status ) {
				$statuses[ 'wc-' . $status ] = 'Awaiting pay';
				return $statuses;
			}
		);
		add_filter(
			'woocommerce_valid_order_statuses_for_payment',
			function ( $statuses ) use ( $status ) {
				$statuses[] = $status;
				return $statuses;
			}
		);
	}

	/**
	 * Hold rows of an order.
	 *
	 * @param int $order_id Order ID.
	 * @return array<int, array<string, string>>
	 */
	private function get_hold_rows( int $order_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT product_id, stock_quantity FROM {$wpdb->wc_reserved_stock} WHERE order_id = %d", $order_id ), ARRAY_A );
	}
}
