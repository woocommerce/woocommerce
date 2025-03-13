<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Caches\CommentCountCache;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Tests for WC_Comments class.
 */
class WC_Comments_Tests extends \WC_Unit_Test_Case {
	/**
	 * Test get_review_counts_for_product_ids().
	 */
	public function test_get_review_counts_for_product_ids() {
		$product1 = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();
		$product3 = WC_Helper_Product::create_simple_product();

		$expected_review_count = array(
			$product1->get_id() => 0,
			$product2->get_id() => 0,
			$product3->get_id() => 0,
		);
		$product_id_array      = array_keys( $expected_review_count );

		$this->assertEquals( $expected_review_count, WC_Comments::get_review_counts_for_product_ids( $product_id_array ) );

		ProductHelper::create_product_review( $product2->get_id() );
		ProductHelper::create_product_review( $product3->get_id() );
		ProductHelper::create_product_review( $product3->get_id() );

		$expected_review_count = array(
			$product1->get_id() => 0,
			$product2->get_id() => 1,
			$product3->get_id() => 2,
		);
		$this->assertEquals( $expected_review_count, WC_Comments::get_review_counts_for_product_ids( $product_id_array ) );
	}

	/**
	 * Test get_review_count_for_product.
	 */
	public function test_get_review_count_for_product() {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertEquals( 0, WC_Comments::get_review_count_for_product( $product ) );

		ProductHelper::create_product_review( $product->get_id() );
		$this->assertEquals( 1, WC_Comments::get_review_count_for_product( $product ) );

		ProductHelper::create_product_review( $product->get_id() );
		$this->assertEquals( 2, WC_Comments::get_review_count_for_product( $product ) );
	}

	/**
	 * Test get_products_reviews_pending_moderation_counter.
	 *
	 * @param CommentCountCache $comments_cache_facade Cache facade instance to inject from data provider.
	 * @dataProvider cache_facade_instance
	 */
	public function test_get_pending_review_count_is_getting_updated(CommentCountCache $comments_cache_facade ) {
		$di                             = wc_get_container();
		$original_comments_cache_facade = $di->get( CommentCountCache::class );
		$di->replace( CommentCountCache::class, $comments_cache_facade );

		// Populate the cache entry, otherwise lazy updates will not be performed.
		$this->assertSame( 0, WC_Comments::get_products_reviews_pending_moderation_counter() );

		// Ensure the newly posted reviews processing is handled as intended.
		$product            = WC_Helper_Product::create_simple_product();
		$approved_reviews   = array(
			ProductHelper::create_product_review( $product->get_id(), '...' ),
		);
		$unapproved_reviews = array(
			ProductHelper::create_product_review( $product->get_id(), '...', '0' ),
			ProductHelper::create_product_review( $product->get_id(), '...', '0' ),
		);
		$this->assertSame( count( $unapproved_reviews ), WC_Comments::get_products_reviews_pending_moderation_counter() );

		// Ensure the existing reviews status changes are handled as intended (flip-flop approved statuses).
		foreach ( $unapproved_reviews as $review_id ) {
			\wp_update_comment(
				array(
					'comment_ID'       => $review_id,
					'comment_approved' => '1',
				)
			);
		}
		$this->assertSame( 0, WC_Comments::get_products_reviews_pending_moderation_counter() );
		foreach ( $unapproved_reviews as $review_id ) {
			\wp_update_comment(
				array(
					'comment_ID'       => $review_id,
					'comment_approved' => '0',
				)
			);
		}
		$this->assertSame( count( $unapproved_reviews ), WC_Comments::get_products_reviews_pending_moderation_counter() );

		$di->replace( CommentCountCache::class, $original_comments_cache_facade );
	}

	/**
	 * Data provider supplying the caching facade instance testing against different caching APIs.
	 *
	 * @return Generator<CommentCountCache>
	 */
	public function cache_facade_instance() {
		yield from array(
			'use cache API'     => array(
				new class() extends CommentCountCache {
					/**
					 * Constructor override.
					 */
					public function __construct() {
						parent::__construct();
						$this->use_cache_api = true;
					}
				},
			),
			'use transient API' => array(
				new class() extends CommentCountCache {
					/**
					 * Constructor override.
					 */
					public function __construct() {
						parent::__construct();
						$this->use_cache_api = false;
					}
				},
			),
		);
	}
}
