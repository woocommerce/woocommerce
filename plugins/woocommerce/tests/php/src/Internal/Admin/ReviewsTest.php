<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Reviews;
use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
use Generator;
use ReflectionClass;
use ReflectionException;
use WC_Unit_Test_Case;

/**
 * Tests for the admin reviews handler.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews
 */
class ReviewsTest extends WC_Unit_Test_Case {

	/**
	 * Tests that can get the class instance.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::get_instance()
	 *
	 * @return void
	 */
	public function test_get_instance() {
		$this->assertInstanceOf( Reviews::class, Reviews::get_instance() );
	}

	/**
	 * Tests that `load_reviews_screen()` creates an instance of {@see ReviewsListTable}.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::load_reviews_screen()
	 *
	 * @return void
	 * @throws ReflectionException If the method or the property is not found.
	 */
	public function test_load_reviews_screen() {
		$reviews = new Reviews();

		// This has to be manually set, otherwise instantiating ReviewsListTable will throw an undefined index error.
		$hook_property = ( new ReflectionClass( $reviews ) )->getProperty( 'reviews_page_hook' );
		$hook_property->setAccessible( true );
		$hook_property->setValue( $reviews, 'product_page_product-reviews' );

		$list_table_property = ( new ReflectionClass( $reviews ) )->getProperty( 'reviews_list_table' );
		$list_table_property->setAccessible( true );

		$this->assertNull( $list_table_property->getValue( $reviews ) );

		$reviews->load_reviews_screen();

		$this->assertInstanceOf( ReviewsListTable::class, $list_table_property->getValue( $reviews ) );
	}

	/**
	 * Tests that can get the pending comment count bubble.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::get_pending_count_bubble()
	 * @dataProvider data_provider_get_pending_count_bubble
	 *
	 * @param int    $number_pending Number of pending product reviews.
	 * @param string $expected_html  Expected return value.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_pending_count_bubble( int $number_pending, string $expected_html ) {
		// Add a normal post with some pending comments -- these should not appear in our counts.
		$post_id = $this->factory()->post->create(
			[
				'post_type' => 'post',
			]
		);
		$this->factory()->comment->create_many(
			3,
			[
				'comment_post_ID'  => $post_id,
				'comment_approved' => '0',
			]
		);

		if ( $number_pending > 0 ) {
			// Now add a product with a bunch of reviews.
			$product_id = $this->factory()->post->create(
				[
					'post_type' => 'product',
				]
			);

			// Create moderated comments -- these _should_ appear in our counts.
			$this->factory()->comment->create_many(
				$number_pending,
				[
					'comment_type' => 'review',
					'comment_post_ID' => $product_id,
					'comment_approved' => '0',
				]
			);

			// Create some approved comments -- these _should not_ appear in our counts.
			$this->factory()->comment->create_many(
				2,
				[
					'comment_type' => 'review',
					'comment_post_ID' => $product_id,
					'comment_approved' => '1',
				]
			);
		}

		$reviews = new Reviews();
		$method  = ( new ReflectionClass( $reviews ) )->getMethod( 'get_pending_count_bubble' );
		$method->setAccessible( true );

		$this->assertSame(
			$expected_html,
			$method->invoke( $reviews )
		);
	}

	/** @see test_get_pending_count_bubble */
	public function data_provider_get_pending_count_bubble() : Generator {
		yield 'no pending' => [ 0, '' ];
		yield 'has pending' => [
			2,
			' <span class="awaiting-mod count-2"><span class="pending-count">2</span></span>',
		];
	}

	/**
	 * Tests that can output the reviews list table and filter it.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::render_reviews_list_table()
	 *
	 * @return void
	 * @throws ReflectionException If the property doesn't exist.
	 */
	public function test_render_reviews_list_table() {
		$reviews = Reviews::get_instance();
		$list_table = new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] );

		$property = ( new ReflectionClass( $reviews ) )->getProperty( 'reviews_list_table' );
		$property->setAccessible( true );
		$property->setValue( $reviews, $list_table );

		add_filter(
			'woocommerce_product_reviews_list_table',
			static function ( $content ) {
				return $content . 'custom additional content';
			}
		);

		ob_start();

		$reviews->render_reviews_list_table();

		$output = ob_get_clean();

		$this->assertStringContainsString( '<form id="reviews-filter" method="get">', $output );
		$this->assertStringContainsString( '<input type="hidden" name="page" value="' . Reviews::MENU_SLUG . '" />', $output );
		$this->assertStringContainsString( '<input type="hidden" name="post_type" value="product" />', $output );
		$this->assertStringEndsWith( 'custom additional content', $output );

		remove_all_filters( 'woocommerce_product_reviews_list_table' );
	}

}
