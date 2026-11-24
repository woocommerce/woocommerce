<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFailures;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Internal\Caches\ProductCacheController;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for what each product fails, stored by the scan.
 */
class ProductFailuresTest extends WC_Unit_Test_Case {

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $readiness;

	/**
	 * Run before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		StoreOutputChecksTest::block_requests();
		// Orders other tests left in the Analytics tables would count toward the sales share.
		\WC_Helper_Reports::reset_stats_dbs();
		$this->readiness = wc_get_container()->get( AgentReadiness::class );
	}

	/**
	 * Run after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( AgentReadiness::REPORT_OPTION );
		parent::tearDown();
	}

	/**
	 * A count from the stored report.
	 *
	 * @param string $id Check id.
	 */
	private function check_count( string $id ): int {
		return (int) array_column( (array) $this->readiness->get_cached_report()['checks'], 'count', 'id' )[ $id ];
	}

	/**
	 * @testdox A scan stores what each product fails and clears products it no longer tests.
	 */
	public function test_scan_stores_and_clears(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->readiness->refresh_report();

		$stored = ProductFailures::for_product( $product->get_id() );
		$this->assertContains( 'missing_images', $stored );
		$this->assertSame( 1, $this->check_count( 'missing_images' ) );
		$this->assertNotEmpty( array_filter( $stored, static fn( string $value ): bool => 0 === strpos( $value, ProductScan::NOT_LISTABLE . ':' ) ) );

		wp_update_post(
			array(
				'ID'          => $product->get_id(),
				'post_status' => 'draft',
			)
		);
		$this->readiness->refresh_report();
		$this->assertSame( array(), ProductFailures::for_product( $product->get_id() ) );
	}

	/**
	 * @testdox The report's counts follow the rows, and a report from before rows were stored keeps its own.
	 */
	public function test_counts_follow_rows(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();

		ProductFailures::store( $product->get_id(), array() );
		$this->assertSame( 0, $this->check_count( 'missing_images' ) );
		$channels = (array) $this->readiness->get_cached_report()['channels'];
		$this->assertSame( 1, reset( $channels )['complete'] );

		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'checks' => array(
					array(
						'id'    => 'missing_images',
						'count' => 3,
					),
				),
			)
		);
		$this->assertSame( 3, $this->check_count( 'missing_images' ) );
	}

	/**
	 * @testdox A product that becomes hidden after start() is not recorded and its old rows are cleared.
	 */
	public function test_product_hidden_between_start_and_batch_is_cleared(): void {
		$product = WC_Helper_Product::create_simple_product();
		$id      = $product->get_id();
		$this->readiness->refresh_report();
		$this->assertNotEmpty( ProductFailures::for_product( $id ) );

		$hook = static function ( array $product_ids ) use ( $id ): void {
			if ( ! in_array( $id, array_map( 'intval', $product_ids ), true ) ) {
				return;
			}
			$product = wc_get_product( $id );
			$product->set_catalog_visibility( 'hidden' );
			$product->save();
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $hook );
		try {
			$this->readiness->refresh_report();
		} finally {
			remove_action( 'woocommerce_agent_readiness_scan_batch', $hook );
		}

		$this->assertSame( array(), ProductFailures::for_product( $id ) );
	}

	/**
	 * @testdox A product that stores rows while the scan runs keeps them.
	 */
	public function test_product_stored_during_scan_keeps_its_rows(): void {
		// A product to scan, so there is a batch for the new product to be saved during.
		WC_Helper_Product::create_simple_product();
		$during_id = 0;
		$hook      = static function () use ( &$during_id ) {
			if ( 0 !== $during_id ) {
				return;
			}
			// This batch is already loaded, so the new product is not one of its results.
			$during_id = -1;
			$during_id = WC_Helper_Product::create_simple_product()->get_id();
			add_post_meta( $during_id, ProductFailures::META_KEY, 'missing_images' );
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $hook );

		try {
			$this->readiness->refresh_report();
		} finally {
			remove_action( 'woocommerce_agent_readiness_scan_batch', $hook );
		}

		$this->assertGreaterThan( 0, $during_id );
		$this->assertContains( 'missing_images', ProductFailures::for_product( $during_id ) );
	}

	/**
	 * @testdox A write at or after the given time stays, and an older write is removed with its marker.
	 */
	public function test_forget_unless_written_since_keeps_a_later_write(): void {
		$before = microtime( true );
		$stale  = WC_Helper_Product::create_simple_product();
		$fresh  = WC_Helper_Product::create_simple_product();
		ProductFailures::store( $stale->get_id(), array(), false );
		update_post_meta( $stale->get_id(), ProductFailures::WRITTEN_META, sprintf( '%.6F', $before - 5 ) );
		ProductFailures::store( $fresh->get_id(), array(), true );

		// Read through the product, so its meta cache holds the rows.
		$this->assertNotEmpty( wc_get_product( $stale->get_id() )->get_meta( ProductFailures::META_KEY, false ) );

		ProductFailures::forget_unless_written_since( array( $stale->get_id(), $fresh->get_id() ), $before );

		$this->assertSame( array(), ProductFailures::stored( $stale->get_id() ) );
		$this->assertSame( array(), wc_get_product( $stale->get_id() )->get_meta( ProductFailures::META_KEY, false ), 'The product meta cache drops the removed rows' );
		$this->assertSame( '', get_post_meta( $stale->get_id(), ProductFailures::WRITTEN_META, true ) );
		$this->assertSame( array( ProductFailures::CHECKED ), ProductFailures::stored( $fresh->get_id() ) );
		$this->assertNotSame( '', get_post_meta( $fresh->get_id(), ProductFailures::WRITTEN_META, true ) );
	}

	/**
	 * @testdox After cleanup, a cached product query on the rows, like the Products list filter, no longer finds the product.
	 */
	public function test_cleanup_reruns_cached_product_queries(): void {
		$before = microtime( true );
		$id     = WC_Helper_Product::create_simple_product()->get_id();
		ProductFailures::store( $id, array( 'missing_images' => array() ), false );
		update_post_meta( $id, ProductFailures::WRITTEN_META, sprintf( '%.6F', $before - 5 ) );
		$query = static fn(): array => get_posts(
			array(
				'post_type'  => 'product',
				'fields'     => 'ids',
				'meta_key'   => ProductFailures::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => 'missing_images', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$this->assertSame( array( $id ), $query(), 'Fixture precondition: the query finds the product and caches the result' );

		ProductFailures::forget_unless_written_since( array( $id ), $before );

		$this->assertSame( array(), $query() );
	}

	/**
	 * @testdox With product instance caching on, cleanup drops the cached product, so it no longer has the removed rows.
	 */
	public function test_cleanup_drops_the_cached_product(): void {
		$option     = 'woocommerce_feature_' . ProductCacheController::FEATURE_NAME . '_enabled';
		$was        = get_option( $option );
		$controller = wc_get_container()->get( ProductCacheController::class );
		update_option( $option, 'yes' );
		$controller->register_hooks();
		try {
			$before = microtime( true );
			$id     = WC_Helper_Product::create_simple_product()->get_id();
			ProductFailures::store( $id, array( 'missing_images' => array() ), false );
			update_post_meta( $id, ProductFailures::WRITTEN_META, sprintf( '%.6F', $before - 5 ) );
			$cached = wc_get_product( $id );
			$cached->get_meta( ProductFailures::META_KEY, false ); // Meta loads on first read, so the cached product holds the rows.
			wc_get_container()->get( ProductCache::class )->set( $cached );
			$this->assertTrue( wc_get_container()->get( ProductCache::class )->is_cached( $id ), 'Fixture precondition: the product is cached' );

			ProductFailures::forget_unless_written_since( array( $id ), $before );

			$this->assertSame( array(), wc_get_product( $id )->get_meta( ProductFailures::META_KEY, false ) );
		} finally {
			wc_get_container()->get( ProductCache::class )->flush();
			remove_action( 'clean_post_cache', array( $controller, 'invalidate_product_cache_on_clean' ), 10 );
			foreach ( array( 'updated_post_meta', 'added_post_meta', 'deleted_post_meta' ) as $hook ) {
				remove_action( $hook, array( $controller, 'invalidate_product_cache_by_meta' ), 10 );
			}
			remove_action( 'woocommerce_updated_product_stock', array( $controller, 'invalidate_product_cache' ), 10 );
			remove_action( 'woocommerce_updated_product_sales', array( $controller, 'invalidate_product_cache' ), 10 );
			false === $was ? delete_option( $option ) : update_option( $option, $was );
		}
	}

	/**
	 * @testdox A save records its write time before it changes the rows, so cleanup that runs during the save keeps them.
	 */
	public function test_cleanup_during_a_save_keeps_its_rows(): void {
		$product = WC_Helper_Product::create_simple_product();
		$id      = $product->get_id();
		ProductFailures::store( $id, array(), false );
		$scan_started = microtime( true );

		// Cleanup of a scan that lists this product runs while the save is writing its rows.
		$cleanup = static function ( $meta_id, $object_id, $meta_key ) use ( $id, $scan_started ): void {
			unset( $meta_id );
			if ( $id === (int) $object_id && ProductFailures::META_KEY === $meta_key ) {
				ProductFailures::forget_unless_written_since( array( $id ), $scan_started );
			}
		};
		add_action( 'added_post_meta', $cleanup, 10, 3 );
		try {
			ProductFailures::store( $id, array( 'missing_images' => array() ) );
		} finally {
			remove_action( 'added_post_meta', $cleanup, 10 );
		}

		$this->assertSame( array( ProductFailures::CHECKED, 'missing_images' ), ProductFailures::stored( $id ) );
	}

	/**
	 * @testdox Cleanup of more products than one delete takes removes old rows past the first chunk, keeps fresh ones, and clears their cached meta.
	 */
	public function test_forget_unless_written_since_past_one_chunk(): void {
		$ids           = self::factory()->post->create_many( ProductFailures::CLEANUP_CHUNK + 50 );
		$last_in_first = ProductFailures::CLEANUP_CHUNK - 1;
		foreach ( $ids as $id ) {
			ProductFailures::store( $id, array(), false );
		}
		$scan_started = microtime( true );
		$fresh        = array( $ids[5], $ids[ ProductFailures::CLEANUP_CHUNK + 20 ] );
		foreach ( $fresh as $id ) {
			ProductFailures::store( $id, array() );
		}
		foreach ( $ids as $id ) {
			ProductFailures::stored( $id ); // Prime the meta cache.
		}

		ProductFailures::forget_unless_written_since( $ids, $scan_started );

		foreach ( array( $ids[0], $ids[ $last_in_first ], $ids[ ProductFailures::CLEANUP_CHUNK ], end( $ids ) ) as $id ) {
			$this->assertSame( array(), ProductFailures::stored( $id ), "Old rows of product $id are removed" );
		}
		foreach ( $fresh as $id ) {
			$this->assertSame( array( ProductFailures::CHECKED ), ProductFailures::stored( $id ), "Fresh rows of product $id stay" );
		}
	}

	/**
	 * @testdox Two rows for one value on one product count as one product.
	 */
	public function test_duplicate_rows_count_once(): void {
		$product = WC_Helper_Product::create_simple_product();
		add_post_meta( $product->get_id(), ProductFailures::META_KEY, ProductFailures::CHECKED );
		add_post_meta( $product->get_id(), ProductFailures::META_KEY, ProductFailures::CHECKED );

		$this->assertSame( 1, ProductFailures::counts()[ ProductFailures::CHECKED ] );
	}

	/**
	 * @testdox The counts are read per site, so after switch_to_blog() they are the other site's.
	 */
	public function test_counts_per_site(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}
		$other = self::factory()->blog->create();
		ProductFailures::store( WC_Helper_Product::create_simple_product()->get_id(), array() );
		$this->assertSame( 1, ProductFailures::counts()[ ProductFailures::CHECKED ] ?? 0 );

		switch_to_blog( $other );
		$on_other = ProductFailures::counts();
		restore_current_blog();

		$this->assertArrayNotHasKey( ProductFailures::CHECKED, $on_other );
		$this->assertSame( 1, ProductFailures::counts()[ ProductFailures::CHECKED ] ?? 0, 'Back on the first site' );
	}

	/**
	 * @testdox A check's share of sales is dropped once its count changes after the scan, since it was measured on the products failing then.
	 */
	public function test_sales_share_dropped_when_count_changes(): void {
		update_option( 'woocommerce_analytics_enabled', 'yes' );
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 10 ) );
		for ( $i = 0; $i < \Automattic\WooCommerce\Internal\AgentReadiness\ProductSales::MIN_ORDERS; $i++ ) {
			$order = wc_create_order();
			$order->add_product( $product, 1 );
			$order->set_status( 'completed' );
			$order->calculate_totals();
			$order->save();
			\Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::sync_order( $order->get_id() );
			\Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::sync_order_products( $order->get_id() );
		}
		$share = fn(): ?float => array_column( (array) $this->readiness->get_cached_report()['checks'], 'sales_share', 'id' )['missing_images'];

		$this->readiness->refresh_report();
		$this->assertSame( 1.0, $share() );

		ProductFailures::store( $product->get_id(), array() );
		$this->assertNull( $share() );
		$this->assertSame( 1.0, array_column( (array) $this->readiness->get_cached_report()['checks'], 'ranked_share', 'id' )['missing_images'], 'Fix first still ranks by the share from the check' );
	}

	/**
	 * @testdox A check's share of sales is also dropped when the example products change while the count stays the same.
	 */
	public function test_sales_share_dropped_when_example_membership_changes(): void {
		$kept  = WC_Helper_Product::create_simple_product();
		$fixed = WC_Helper_Product::create_simple_product();
		$added = WC_Helper_Product::create_simple_product();
		$fail  = array(
			'missing_images' => array(
				'chatgpt' => array(
					'field'  => 'image',
					'effect' => 'gaps',
				),
			),
		);
		ProductFailures::store( $kept->get_id(), $fail );
		ProductFailures::store( $fixed->get_id(), array() );
		ProductFailures::store( $added->get_id(), $fail );
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'failures_stored' => true,
				'products'        => 3,
				'channels'        => array(),
				'checks'          => array(
					array(
						'id'          => 'missing_images',
						'count'       => 2,
						'product_ids' => array( $kept->get_id(), $fixed->get_id() ),
						'sales_share' => 0.5,
					),
				),
			)
		);

		$check = array_column( (array) $this->readiness->get_cached_report()['checks'], null, 'id' )['missing_images'];
		$this->assertSame( 2, $check['count'], 'Count still two failing products' );
		$this->assertNull( $check['sales_share'], 'Share cleared: examples no longer match the scan' );
		$this->assertSame( 0.5, $check['ranked_share'] );
		$this->assertSame( array( $kept->get_id() ), $check['product_ids'], 'Only examples that still fail remain' );
	}

	/**
	 * @testdox Every checked product has a checked row, passing or not, which for_product leaves out and the product total counts.
	 */
	public function test_checked_row_counts_products(): void {
		$failing = WC_Helper_Product::create_simple_product();
		$passing = WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();

		ProductFailures::store( $passing->get_id(), array() );
		$this->assertSame( array(), ProductFailures::for_product( $passing->get_id() ) );
		$this->assertContains( ProductFailures::CHECKED, (array) get_post_meta( $passing->get_id(), ProductFailures::META_KEY, false ) );
		$this->assertNotContains( ProductFailures::CHECKED, ProductFailures::for_product( $failing->get_id() ) );
		$this->assertSame( 2, ProductFailures::counts()[ ProductFailures::CHECKED ] );

		ProductFailures::forget( $passing->get_id() );
		$this->assertSame( 1, ProductFailures::counts()[ ProductFailures::CHECKED ], 'A write clears the counts read earlier in the request' );
		$this->assertSame( 1, $this->readiness->get_cached_report()['products'] );
	}
}
