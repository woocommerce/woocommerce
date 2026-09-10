<?php
/**
 * ScheduledSaleBatchProcessorTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Internal\Caches\ProductCacheController;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\ScheduledSaleBatchProcessor;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Helper_Product;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_Unit_Test_Case;

/**
 * Tests for the ScheduledSaleBatchProcessor class.
 */
class ScheduledSaleBatchProcessorTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ScheduledSaleBatchProcessor
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( ScheduledSaleBatchProcessor::class );
	}

	/**
	 * @testdox Starting a sale stores the sale price as the active price.
	 */
	public function test_start_mode_applies_the_sale_price(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( time() - 100 );
		$product->set_date_on_sale_to( time() + 300 );
		$product->save();
		update_post_meta( $product->get_id(), '_price', 100 );

		$this->sut->process( array( $product->get_id() ), 'start' );

		$this->assertEquals( 50, get_post_meta( $product->get_id(), '_price', true ), 'The sale price should be the active price once the sale starts.' );
	}

	/**
	 * @testdox Every product is processed when the list spans more than one batch.
	 */
	public function test_end_mode_processes_every_product_across_batches(): void {
		$ids = array();

		for ( $i = 0; $i <= ScheduledSaleBatchProcessor::BATCH_SIZE; $i++ ) {
			$ids[] = $this->create_missed_sale_end_product()->get_id();
		}

		$this->sut->process( $ids, 'end' );

		foreach ( $ids as $id ) {
			$this->assertEquals( 100, get_post_meta( $id, '_price', true ), "Product {$id} was not processed, so a batch boundary dropped it." );
		}
	}

	/**
	 * @testdox An external object cache keeps the persistent-capable groups out of the release.
	 */
	public function test_leaves_shared_groups_alone_on_an_external_cache(): void {
		$product = $this->create_missed_sale_end_product();

		wp_cache_set( 'sentinel', 'keep me', 'products' );
		wp_cache_set( 'sentinel', 'keep me', 'term-queries' );

		// Cast null to false because passing null reads rather than restores the flag.
		$was_external = (bool) wp_using_ext_object_cache( true );

		try {
			$this->sut->process( array( $product->get_id() ), 'end' );
		} finally {
			wp_using_ext_object_cache( $was_external );
		}

		$this->assertSame( 'keep me', wp_cache_get( 'sentinel', 'products' ), 'The products group must survive when an external object cache is in use.' );
		$this->assertSame( 'keep me', wp_cache_get( 'sentinel', 'term-queries' ), 'The term-queries group must survive when an external object cache is in use.' );
		$this->assertEquals( 100, get_post_meta( $product->get_id(), '_price', true ), 'The product must still settle with the shared groups left alone.' );
	}

	/**
	 * @testdox A request-local cache has the flushed groups released after the run.
	 */
	public function test_releases_the_flushed_groups_on_a_request_local_cache(): void {
		if ( ! wp_cache_supports( 'flush_group' ) || wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'Requires a request-local object cache with flush_group support.' );
		}

		$product = $this->create_missed_sale_end_product();

		wp_cache_set( 'sentinel', 'release me', 'products' );
		wp_cache_set( 'sentinel', 'release me', 'term-queries' );

		$this->sut->process( array( $product->get_id() ), 'end' );

		$this->assertFalse( wp_cache_get( 'sentinel', 'products' ), 'The products group must be flushed when the cache is request-local.' );
		$this->assertFalse( wp_cache_get( 'sentinel', 'term-queries' ), 'The term-queries group must be flushed when the cache is request-local.' );
		$this->assertEquals( 100, get_post_meta( $product->get_id(), '_price', true ), 'Fixture precondition: the product should have been processed.' );
	}

	/**
	 * @testdox product_objects entries are released by id, independent of flush_group support.
	 */
	public function test_releases_the_product_objects_cache_by_id(): void {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$was_enabled         = FeaturesUtil::feature_is_enabled( ProductCacheController::FEATURE_NAME );
		$features_controller->change_feature_enable( ProductCacheController::FEATURE_NAME, true );

		try {
			$product = $this->create_missed_sale_end_product();

			$product_cache     = wc_get_container()->get( ProductCache::class );
			$unrelated_product = WC_Helper_Product::create_simple_product();
			$product_cache->set( $product );
			$product_cache->set( $unrelated_product );
			$this->assertTrue( $product_cache->is_cached( $product->get_id() ), 'Fixture precondition: the product must be cached before the run.' );

			$this->sut->process( array( $product->get_id() ), 'end' );

			$this->assertFalse( $product_cache->is_cached( $product->get_id() ), 'The processed product must be released from product_objects.' );
			$this->assertTrue( $product_cache->is_cached( $unrelated_product->get_id() ), 'Per-ID cleanup must leave unrelated product_objects entries intact.' );
		} finally {
			$features_controller->change_feature_enable( ProductCacheController::FEATURE_NAME, $was_enabled );
		}
	}

	/**
	 * @testdox A batch holding both a product and a variation leaves no term relationship cached.
	 */
	public function test_releases_term_caches_for_a_mixed_batch(): void {
		// Priming uses the union of the product and variation taxonomies for every batch ID.
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Mixed batch parent' );
		$parent->set_regular_price( 100 );
		$parent->set_sale_price( 50 );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_regular_price( 100 );
		$variation->set_sale_price( 50 );
		$variation->save();

		$ids = array( $parent->get_id(), $variation->get_id() );

		foreach ( $ids as $id ) {
			update_post_meta( $id, '_price', 50 );
			update_post_meta( $id, '_sale_price_dates_from', time() - 300 );
			update_post_meta( $id, '_sale_price_dates_to', time() - 100 );
		}

		$taxonomies = array_unique(
			array_merge( get_object_taxonomies( 'product' ), get_object_taxonomies( 'product_variation' ) )
		);

		wp_cache_flush();
		_prime_post_caches( $ids );
		$this->assertNotFalse( wp_cache_get( $variation->get_id(), 'product_cat_relationships' ), 'Fixture precondition: priming must cache the product-only groups for the variation.' );

		// Guard against firing clean_object_term_cache with mismatched object types.
		$fired_with_wrong_type = false;
		add_action(
			'clean_object_term_cache',
			function ( $object_ids, $object_type ) use ( $parent, $variation, &$fired_with_wrong_type ) {
				$object_ids = array_map( 'intval', (array) $object_ids );

				if ( in_array( $parent->get_id(), $object_ids, true ) && 'product' !== $object_type ) {
					$fired_with_wrong_type = true;
				}

				if ( in_array( $variation->get_id(), $object_ids, true ) && 'product_variation' !== $object_type ) {
					$fired_with_wrong_type = true;
				}
			},
			10,
			2
		);

		$this->sut->process( $ids, 'end' );

		$this->assertFalse( $fired_with_wrong_type, 'clean_object_term_cache must not fire with a post type that does not match the ids it was given.' );

		foreach ( $ids as $id ) {
			foreach ( $taxonomies as $taxonomy ) {
				$this->assertFalse( wp_cache_get( $id, "{$taxonomy}_relationships" ), "Post {$id} should hold no {$taxonomy} relationship cache after the run." );
			}
		}
	}

	/**
	 * @testdox Releasing a batch does not evict cache entries belonging to other posts.
	 */
	public function test_leaves_unrelated_post_caches_intact(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $page_id, '_unrelated', 'keep me' );
		get_post( $page_id );
		get_post_meta( $page_id );
		$this->assertNotFalse( wp_cache_get( $page_id, 'posts' ), 'Fixture precondition: the page should be primed before the run.' );
		$this->assertNotFalse( wp_cache_get( $page_id, 'post_meta' ), 'Fixture precondition: the page meta should be primed before the run.' );

		$product = $this->create_missed_sale_end_product();

		$this->sut->process( array( $product->get_id() ), 'end' );

		// Assert before get_post_meta() can repopulate the released cache.
		$this->assertFalse( wp_cache_get( $product->get_id(), 'posts' ), 'The batch did not release its own post cache entry.' );
		$this->assertFalse( wp_cache_get( $product->get_id(), 'post_meta' ), 'The batch did not release its own post meta cache entry.' );

		$this->assertEquals( 100, get_post_meta( $product->get_id(), '_price', true ), 'Fixture precondition: the product should have been processed.' );
		$this->assertNotFalse( wp_cache_get( $page_id, 'posts' ), 'The run released an unrelated post from the shared posts cache.' );
		$this->assertNotFalse( wp_cache_get( $page_id, 'post_meta' ), 'The run released unrelated meta from the shared post_meta cache.' );
	}

	/**
	 * @testdox Supported data-store result shapes still settle.
	 */
	public function test_settles_supported_data_store_result_shapes(): void {
		$ids = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$ids[] = $this->create_missed_sale_end_product()->get_id();
		}

		// The forms a replaced data store might return: the default store returns strings.
		$rows = array(
			(string) $ids[0],
			wc_get_product( $ids[1] ),
			get_post( $ids[2] ),
		);

		$this->sut->process( $rows, 'end' );

		foreach ( $ids as $index => $id ) {
			$this->assertEquals(
				100,
				get_post_meta( $id, '_price', true ),
				"Product {$id}, supplied as " . wp_json_encode( $rows[ $index ] ) . ', should have settled.'
			);
		}
	}

	/**
	 * @testdox A malformed id does not stop the run or evict unrelated posts.
	 */
	public function test_survives_malformed_ids(): void {
		$product = $this->create_missed_sale_end_product();

		// A loose integer cast would turn this malformed value into the cached page ID.
		$decoy_id  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$malformed = $decoy_id . 'abc';
		get_post( $decoy_id );
		$this->assertNotFalse( wp_cache_get( $decoy_id, 'posts' ), 'Fixture precondition: the decoy page should be primed before the run.' );

		$this->sut->process( array( $product->get_id(), $malformed ), 'end' );

		$this->assertEquals( 100, get_post_meta( $product->get_id(), '_price', true ), 'A malformed id stopped the run before the real product settled.' );
		$this->assertNotFalse( wp_cache_get( $decoy_id, 'posts' ), "The release cast '{$malformed}' onto post {$decoy_id} and evicted an unrelated page." );
	}

	/**
	 * @testdox An id listed twice in one batch is processed once.
	 */
	public function test_processes_a_duplicated_id_once(): void {
		// Duplicate postmeta rows can return the same product more than once.
		$product = $this->create_missed_sale_end_product();

		$saves = 0;
		add_action(
			'woocommerce_update_product',
			static function ( $updated_id ) use ( $product, &$saves ) {
				if ( (int) $updated_id === $product->get_id() ) {
					++$saves;
				}
			}
		);

		$this->sut->process( array( $product->get_id(), (string) $product->get_id() ), 'end' );

		$this->assertSame( 1, $saves, 'A duplicated id must settle with a single save, not one per row.' );
		$this->assertEquals( 100, get_post_meta( $product->get_id(), '_price', true ), 'Fixture precondition: the product should have been processed.' );
	}

	/**
	 * Create a product whose sale has ended while its stored price is still the sale price.
	 *
	 * @return WC_Product
	 */
	private function create_missed_sale_end_product(): WC_Product {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();

		update_post_meta( $product->get_id(), '_price', 50 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );

		return $product;
	}
}
