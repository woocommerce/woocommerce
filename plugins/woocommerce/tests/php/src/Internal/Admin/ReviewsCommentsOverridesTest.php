<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ReviewsCommentsOverrides;
use WC_Unit_Test_Case;

/**
 * Tests the product reviews overrides for the comments page.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsCommentsOverrides
 */
class ReviewsCommentsOverridesTest extends WC_Unit_Test_Case {

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsCommentsOverrides::display_notices()
	 * @dataProvider provider_test_display_notices()
	 * @param string $current_screen_base    The current WP_Screen base value.
	 * @param bool   $should_display_notices Whether notices should be displayed.
	 */
	public function test_display_notices( string $current_screen_base, bool $should_display_notices ) {
		$this->reset_legacy_proxy_mocks();

		$this->register_legacy_proxy_function_mocks(
			[
				'get_current_screen' => function() use ( $current_screen_base ) {
					$screen = new \stdClass();
					$screen->base = $current_screen_base;
					return $screen;
				},
			]
		);

		ob_start();

		ReviewsCommentsOverrides::get_instance()->display_notices();

		if ( $should_display_notices ) {
			$this->assertNotEmpty( ob_get_clean() );
		} else {
			$this->assertEmpty( ob_get_clean() );
		}
	}

	/** @see test_display_notices() */
	public function provider_test_display_notices() : \Generator {
		yield 'Comments page' => [ 'edit-comments', true ];
		yield 'Posts page' => [ 'edit', false ];
		yield 'Product Reviews page' => [ 'product_page_product-reviews', false ];
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsCommentsOverrides::exclude_reviews_from_comments()
	 */
	public function test_exclude_reviews_from_comments() {
		$overrides = new ReviewsCommentsOverrides();

		$original_args = [
			'post_type' => [ 'product' ],
		];

		$this->assertTrue( in_array( 'product', $original_args['post_type'] ) );

		$new_args = $overrides->exclude_reviews_from_comments( $original_args );

		$this->assertFalse( in_array( 'product', $new_args['post_type'] ) );
	}

}
