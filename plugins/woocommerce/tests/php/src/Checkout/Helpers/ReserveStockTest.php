<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Checkout\Helpers;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;
use WC_Unit_Test_Case;

/**
 * @covers \Automattic\WooCommerce\Checkout\Helpers\ReserveStock::prime_reserved_stock
 * @covers \Automattic\WooCommerce\Checkout\Helpers\ReserveStock::get_reserved_stock_cached
 * @covers \Automattic\WooCommerce\Checkout\Helpers\ReserveStock::get_reserved_stock
 */
class ReserveStockTest extends WC_Unit_Test_Case {

	/**
	 * IDs of products created in setUp, in order.
	 *
	 * @var int[]
	 */
	private array $product_ids = array();

	/**
	 * Reset the request-scoped cache and seed three stock-managed products.
	 */
	public function setUp(): void {
		parent::setUp();
		ReserveStock::flush_reserved_stock_cache();

		// 3 stock-managed simple products.
		for ( $i = 0; $i < 3; $i++ ) {
			$product = new \WC_Product_Simple();
			$product->set_name( 'CC2 RS Test ' . $i );
			$product->set_regular_price( '5.00' );
			$product->set_manage_stock( true );
			$product->set_stock_quantity( 50 );
			$product->set_stock_status( 'instock' );
			$product->set_status( 'publish' );
			$this->product_ids[] = (int) $product->save();
		}
	}

	/**
	 * Flush the cache and delete the seeded products.
	 */
	public function tearDown(): void {
		ReserveStock::flush_reserved_stock_cache();
		foreach ( $this->product_ids as $id ) {
			wp_delete_post( $id, true );
		}
		$this->product_ids = array();
		parent::tearDown();
	}

	/**
	 * @testdox After priming, get_reserved_stock_cached returns 0 for every requested ID when no reservations exist.
	 */
	public function test_prime_returns_zero_for_unreserved_products(): void {
		$sut = new ReserveStock();
		$sut->prime_reserved_stock( $this->product_ids, 0 );

		foreach ( $this->product_ids as $pid ) {
			$this->assertSame( 0.0, (float) $sut->get_reserved_stock_cached( wc_get_product( $pid ), 0 ) );
		}
	}

	/**
	 * @testdox Primed cached reads agree with per-product get_reserved_stock for the same exclude_order_id.
	 */
	public function test_primed_cached_parity_with_single_call(): void {
		// Reserve some stock against a pending order.
		$order = new \WC_Order();
		$order->set_status( 'pending' );
		$order->save();

		$pid = $this->product_ids[1];
		$this->reserve_directly( (int) $order->get_id(), $pid, 7 );

		ReserveStock::flush_reserved_stock_cache();

		$sut = new ReserveStock();
		$sut->prime_reserved_stock( $this->product_ids, 0 );

		$cached = array();
		foreach ( $this->product_ids as $product_id ) {
			$cached[ $product_id ] = $sut->get_reserved_stock_cached( wc_get_product( $product_id ), 0 );
		}

		// Compare to single-shot lookups (fresh instance, fresh cache).
		ReserveStock::flush_reserved_stock_cache();
		foreach ( $this->product_ids as $product_id ) {
			$single = ( new ReserveStock() )->get_reserved_stock( wc_get_product( $product_id ), 0 );
			$this->assertSame(
				(float) $single,
				(float) $cached[ $product_id ],
				"Primed cached vs single mismatch for product $product_id"
			);
		}

		$order->delete( true );
	}

	/**
	 * @testdox exclude_order_id removes that order's reservations from both single and primed cached results.
	 */
	public function test_exclude_order_id_excludes_in_prime(): void {
		$order = new \WC_Order();
		$order->set_status( 'pending' );
		$order->save();
		$oid = (int) $order->get_id();

		$pid     = $this->product_ids[2];
		$product = wc_get_product( $pid );
		$this->reserve_directly( $oid, $pid, 9 );

		ReserveStock::flush_reserved_stock_cache();
		$sut = new ReserveStock();

		// Without excluding, we see the reservation.
		$sut->prime_reserved_stock( array( $pid ), 0 );
		$this->assertSame( 9.0, (float) $sut->get_reserved_stock_cached( $product, 0 ) );

		// With excluding, we don't.
		ReserveStock::flush_reserved_stock_cache();
		$sut->prime_reserved_stock( array( $pid ), $oid );
		$this->assertSame( 0.0, (float) $sut->get_reserved_stock_cached( $product, $oid ) );

		$order->delete( true );
	}

	/**
	 * @testdox After priming, get_reserved_stock_cached hits the cache and emits no extra SQL.
	 */
	public function test_prime_warms_cache_for_cached_calls(): void {
		global $wpdb;

		ReserveStock::flush_reserved_stock_cache();
		$sut = new ReserveStock();
		$sut->prime_reserved_stock( $this->product_ids, 0 );

		// Resolve products before snapshotting so product loading is not in the measured window.
		$products = array_map( 'wc_get_product', $this->product_ids );

		// Snapshot wpdb num_queries; require_once SAVEQUERIES already on in tests is unreliable, so we use num_queries.
		$before = (int) $wpdb->num_queries;
		foreach ( $products as $product ) {
			$sut->get_reserved_stock_cached( $product, 0 );
		}
		$after = (int) $wpdb->num_queries;

		$this->assertSame( $before, $after, 'Per-product cached calls after batch must not run any SQL.' );
	}

	/**
	 * @testdox The plain get_reserved_stock() is uncached and re-queries on every call.
	 */
	public function test_plain_getter_is_uncached(): void {
		global $wpdb;

		ReserveStock::flush_reserved_stock_cache();
		$sut     = new ReserveStock();
		$product = wc_get_product( $this->product_ids[0] );

		$first  = $sut->get_reserved_stock( $product, 0 );
		$before = (int) $wpdb->num_queries;
		$second = $sut->get_reserved_stock( $product, 0 );
		$after  = (int) $wpdb->num_queries;

		$this->assertSame( (float) $first, (float) $second );
		$this->assertGreaterThan( $before, $after, 'Plain getter must always run SQL.' );
	}

	/**
	 * @testdox The cached getter returns the same value on the second invocation without extra SQL.
	 */
	public function test_cached_call_is_memoized(): void {
		global $wpdb;

		ReserveStock::flush_reserved_stock_cache();
		$sut     = new ReserveStock();
		$product = wc_get_product( $this->product_ids[0] );

		// Warm (cache miss => 1 query).
		$first  = $sut->get_reserved_stock_cached( $product, 0 );
		$before = (int) $wpdb->num_queries;
		$second = $sut->get_reserved_stock_cached( $product, 0 );
		$after  = (int) $wpdb->num_queries;

		$this->assertSame( (float) $first, (float) $second );
		$this->assertSame( $before, $after, 'Second cached call must hit cache.' );
	}

	/**
	 * @testdox get_reserved_stock_cached bypasses the cache when a woocommerce_query_for_reserved_stock filter is registered.
	 */
	public function test_cached_getter_bypasses_cache_when_filter_registered(): void {
		$order = new \WC_Order();
		$order->set_status( 'pending' );
		$order->save();

		$pid = $this->product_ids[0];
		$this->reserve_directly( (int) $order->get_id(), $pid, 4 );

		ReserveStock::flush_reserved_stock_cache();
		$sut     = new ReserveStock();
		$product = wc_get_product( $pid );

		// Warm the cache with the unfiltered value (4).
		$this->assertSame( 4.0, (float) $sut->get_reserved_stock_cached( $product, 0 ) );

		// Register a filter mid-request that forces the query to return nothing.
		$filter = static function () {
			return 'SELECT 0';
		};
		add_filter( 'woocommerce_query_for_reserved_stock', $filter );

		// Cached getter must now bypass the stale cache and honour the filter.
		$this->assertSame( 0.0, (float) $sut->get_reserved_stock_cached( $product, 0 ) );

		remove_filter( 'woocommerce_query_for_reserved_stock', $filter );
		$order->delete( true );
	}

	/**
	 * @testdox flush_reserved_stock_cache(null) drops all cached entries; targeted flush only drops one bucket.
	 */
	public function test_flush_cache_behaviour(): void {
		global $wpdb;
		$sut = new ReserveStock();

		// Prime two buckets: exclude_order_id 0 and exclude_order_id 999.
		$sut->prime_reserved_stock( $this->product_ids, 0 );
		$sut->prime_reserved_stock( $this->product_ids, 999 );

		// Targeted flush.
		ReserveStock::flush_reserved_stock_cache( 999 );

		// Resolve products before snapshotting so product loading is not in the measured window.
		$products = array_map( 'wc_get_product', $this->product_ids );

		$before = (int) $wpdb->num_queries;
		// Bucket 0 should still be warm.
		foreach ( $products as $product ) {
			$sut->get_reserved_stock_cached( $product, 0 );
		}
		$this->assertSame( $before, (int) $wpdb->num_queries, 'Bucket 0 should still be cached.' );

		// Full flush.
		ReserveStock::flush_reserved_stock_cache();
		$before = (int) $wpdb->num_queries;
		$sut->get_reserved_stock_cached( wc_get_product( $this->product_ids[0] ), 0 );
		$this->assertGreaterThan( $before, (int) $wpdb->num_queries, 'After flush, cached call must run SQL.' );
	}

	/**
	 * Insert a reserved_stock row without going through reserve_stock_for_order (which would trigger flush internally).
	 *
	 * @param int   $order_id   Order ID to associate the reservation with.
	 * @param int   $product_id Product ID being reserved.
	 * @param float $qty        Quantity to reserve.
	 */
	private function reserve_directly( int $order_id, int $product_id, float $qty ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->wc_reserved_stock,
			array(
				'order_id'       => $order_id,
				'product_id'     => $product_id,
				'stock_quantity' => $qty,
				'timestamp'      => current_time( 'mysql', true ),
				'expires'        => gmdate( 'Y-m-d H:i:s', time() + 3600 ),
			)
		);
	}
}
