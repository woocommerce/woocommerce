<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Caching;

use WC_Helper_Product;
use WC_Product_Simple;
use Automattic\WooCommerce\Caches\ProductCountCache;
use Automattic\WooCommerce\Caches\ProductCountCacheService;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\Utilities\ProductUtil;

/**
 * Class ProductCountCacheServiceTest.
 */
final class ProductCountCacheServiceTest extends \WC_Unit_Test_Case {

	/**
	 * ProductCountCache instance.
	 *
	 * @var ProductCountCache
	 */
	private ProductCountCache $product_cache;

	/**
	 * ProductUtil instance.
	 *
	 * @var ProductUtil
	 */
	private ProductUtil $product_util;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product_cache = new ProductCountCache();
		$this->product_cache->flush();
		$this->product_util = wc_get_container()->get( ProductUtil::class );
	}

	/**
	 * @testdox Count is incremented when a new product is created.
	 */
	public function test_count_incremented_on_product_create(): void {
		$initial_count = $this->product_util->get_counts_for_type( 'product' )[ ProductStatus::PUBLISH ];

		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( ProductStatus::PUBLISH );
		$product->save();

		$counts = $this->product_util->get_counts_for_type( 'product' );

		$this->assertSame( $initial_count + 1, $counts[ ProductStatus::PUBLISH ] );

		$product->delete( true );
	}

	/**
	 * @testdox Count is decremented when a product is permanently deleted.
	 */
	public function test_count_decremented_on_product_delete(): void {
		$initial_count = $this->product_util->get_counts_for_type( 'product' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( ProductStatus::PUBLISH );
		$product->save();
		$product->delete( true );

		$counts = $this->product_util->get_counts_for_type( 'product' );

		$this->assertSame( $initial_count[ ProductStatus::PUBLISH ], $counts[ ProductStatus::PUBLISH ] );
	}

	/**
	 * @testdox Source and destination status counts are updated when a product status changes.
	 */
	public function test_count_on_product_status_change(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( ProductStatus::PUBLISH );
		$product->save();

		$initial_count = $this->product_util->get_counts_for_type( 'product' );

		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		$count = $this->product_util->get_counts_for_type( 'product' );

		$this->assertSame( $initial_count[ ProductStatus::PUBLISH ] - 1, $count[ ProductStatus::PUBLISH ] );
		$this->assertSame( $initial_count[ ProductStatus::DRAFT ] + 1, $count[ ProductStatus::DRAFT ] );

		$product->delete( true );
	}

	/**
	 * @testdox Only the final status is incremented when a new product is saved with a non-default initial status.
	 */
	public function test_count_on_new_product_with_initial_status(): void {
		$initial_count = $this->product_util->get_counts_for_type( 'product' );

		$product = WC_Helper_Product::create_simple_product( true, array( 'status' => ProductStatus::PENDING ) );

		$count = $this->product_util->get_counts_for_type( 'product' );

		$this->assertSame( $initial_count[ ProductStatus::PUBLISH ], $count[ ProductStatus::PUBLISH ] );
		$this->assertSame( $initial_count[ ProductStatus::PENDING ] + 1, $count[ ProductStatus::PENDING ] );

		$product->delete( true );
	}

	/**
	 * @testdox Draft count is incremented correctly when transition_post_status fires during new product creation.
	 */
	public function test_count_on_new_product_with_status_change(): void {
		$initial_count = $this->product_util->get_counts_for_type( 'product' );

		$product = new WC_Product_Simple();
		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		$count = $this->product_util->get_counts_for_type( 'product' );

		$this->assertSame( $initial_count[ ProductStatus::DRAFT ] + 1, $count[ ProductStatus::DRAFT ] );
		$this->assertSame( $initial_count[ ProductStatus::PUBLISH ], $count[ ProductStatus::PUBLISH ] );

		$product->delete( true );
	}

	/**
	 * @testdox Only the final status is incremented when a product cycles through multiple statuses before being saved.
	 */
	public function test_count_on_multiple_status_changes(): void {
		$initial_count = $this->product_util->get_counts_for_type( 'product' );

		$product = new WC_Product_Simple();
		$product->set_status( ProductStatus::PUBLISH );
		$product->set_status( ProductStatus::PENDING );
		$product->save();

		$count = $this->product_util->get_counts_for_type( 'product' );

		$this->assertSame( $initial_count[ ProductStatus::PUBLISH ], $count[ ProductStatus::PUBLISH ] );
		$this->assertSame( $initial_count[ ProductStatus::DRAFT ], $count[ ProductStatus::DRAFT ] );
		$this->assertSame( $initial_count[ ProductStatus::PENDING ] + 1, $count[ ProductStatus::PENDING ] );

		$product->delete( true );
	}

	/**
	 * @testdox Counts are not corrupted when a product cycles through publish and back to draft during creation.
	 */
	public function test_count_not_corrupted_on_status_cycle_during_creation(): void {
		$initial_count = $this->product_util->get_counts_for_type( 'product' );

		$hook = null;
		$hook = static function ( int $post_id ) use ( &$hook ): void {
			remove_action( 'save_post_product', $hook, 1 );
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => ProductStatus::PUBLISH,
				)
			);
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => ProductStatus::DRAFT,
				)
			);
		};
		add_action( 'save_post_product', $hook, 1 );

		$product = new WC_Product_Simple();
		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		$count = $this->product_util->get_counts_for_type( 'product' );

		$this->assertSame( $initial_count[ ProductStatus::DRAFT ] + 1, $count[ ProductStatus::DRAFT ] );
		$this->assertSame( $initial_count[ ProductStatus::PUBLISH ], $count[ ProductStatus::PUBLISH ] );

		$product->delete( true );
	}

	/**
	 * @testdox Source status count is decremented correctly when only the source slot is warm and the destination slot is cold.
	 */
	public function test_count_decremented_when_only_source_status_is_cached(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		// Warm all status slots, then flush only the target to create a partially cold cache.
		$this->product_util->get_counts_for_type( 'product' );
		$draft_before = $this->product_cache->get( 'product', array( ProductStatus::DRAFT ) )[ ProductStatus::DRAFT ];

		$this->product_cache->flush( 'product', array( ProductStatus::PUBLISH ) );

		$product->set_status( ProductStatus::PUBLISH );
		$product->save();

		// The draft slot should be decremented even though the publish slot was cold.
		$draft_after = $this->product_cache->get( 'product', array( ProductStatus::DRAFT ) )[ ProductStatus::DRAFT ];
		$this->assertSame( $draft_before - 1, $draft_after );

		$product->delete( true );
	}

	/**
	 * @testdox Final status is not double-incremented when a plugin permanently changes product status during creation.
	 */
	public function test_count_not_double_incremented_on_new_product_with_mid_creation_status_change(): void {
		// Warm all status slots and record the publish count before the test.
		$this->product_util->get_counts_for_type( 'product' );
		$publish_before = $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) )[ ProductStatus::PUBLISH ];

		$hook = null;
		$hook = static function ( int $post_id ) use ( &$hook ): void {
			remove_action( 'save_post_product', $hook, 1 );
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => ProductStatus::PUBLISH,
				)
			);
		};
		add_action( 'save_post_product', $hook, 1 );

		$product = new WC_Product_Simple();
		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		// Publish should be exactly +1: the product ended in PUBLISH, draft must be unchanged.
		$publish_after = $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) )[ ProductStatus::PUBLISH ];
		$this->assertSame( $publish_before + 1, $publish_after );

		$product->delete( true );
	}

	/**
	 * @testdox Source status count is not corrupted when a plugin permanently changes product status during creation.
	 */
	public function test_source_count_not_corrupted_on_new_product_with_mid_creation_status_change(): void {
		// Warm all status slots and record both counts before the test.
		$this->product_util->get_counts_for_type( 'product' );
		$draft_before   = $this->product_cache->get( 'product', array( ProductStatus::DRAFT ) )[ ProductStatus::DRAFT ];
		$publish_before = $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) )[ ProductStatus::PUBLISH ];

		$hook = null;
		$hook = static function ( int $post_id ) use ( &$hook ): void {
			remove_action( 'save_post_product', $hook, 1 );
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => ProductStatus::PUBLISH,
				)
			);
		};
		add_action( 'save_post_product', $hook, 1 );

		$product = new WC_Product_Simple();
		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		// The product ended in PUBLISH, so draft must be unchanged and publish must be exactly +1.
		$draft_after   = $this->product_cache->get( 'product', array( ProductStatus::DRAFT ) )[ ProductStatus::DRAFT ];
		$publish_after = $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) )[ ProductStatus::PUBLISH ];
		$this->assertSame( $draft_before, $draft_after );
		$this->assertSame( $publish_before + 1, $publish_after );

		$product->delete( true );
	}

	/**
	 * @testdox Background recurring action is scheduled.
	 */
	public function test_background_actions_scheduled(): void {
		$product_count_cache_service = wc_get_container()->get( ProductCountCacheService::class );
		$product_count_cache_service->schedule_background_actions();
		$this->assertTrue( as_has_scheduled_action( 'woocommerce_refresh_product_count_cache' ) );
	}

	/**
	 * @testdox Cache is populated when it is cold and an external object cache is active.
	 */
	public function test_prime_cache_if_cold_when_cache_is_cold(): void {
		global $_wp_using_ext_object_cache;
		$_before                    = $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->product_cache->flush();
		$this->assertNull( $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) ) );

		// We expect the cache to be populated with the relevant values.
		$product_count_cache_service = wc_get_container()->get( ProductCountCacheService::class );
		$product_count_cache_service->prime_cache_if_cold( 'product' );

		$cached = $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) );
		$this->assertNotNull( $cached );
		$this->assertArrayHasKey( ProductStatus::PUBLISH, $cached );

		$_wp_using_ext_object_cache = $_before; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testdox Cache values are preserved when the cache is already warm.
	 */
	public function test_prime_cache_if_cold_when_cache_is_warm(): void {
		global $_wp_using_ext_object_cache;
		$_before                    = $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$publish_count = $this->product_util->get_counts_for_type( 'product' )[ ProductStatus::PUBLISH ];
		$this->product_cache->set( 'product', ProductStatus::PUBLISH, $publish_count + 10 );

		// We expect the cached values to remain same as counting skipped for warm caches.
		$product_count_cache_service = wc_get_container()->get( ProductCountCacheService::class );
		$product_count_cache_service->prime_cache_if_cold( 'product' );

		$this->assertSame( $publish_count + 10, $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) )[ ProductStatus::PUBLISH ] );

		$_wp_using_ext_object_cache = $_before; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testdox Cache is not populated when no external object cache is active.
	 */
	public function test_prime_cache_if_cold_when_object_cache_unavailable(): void {
		$this->product_cache->flush();
		$this->assertNull( $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) ) );

		// We expect the cache to remain unpopulated as object caching is unavailable.
		$product_count_cache_service = wc_get_container()->get( ProductCountCacheService::class );
		$product_count_cache_service->prime_cache_if_cold( 'product' );

		$this->assertNull( $this->product_cache->get( 'product', array( ProductStatus::PUBLISH ) ) );
	}
}
