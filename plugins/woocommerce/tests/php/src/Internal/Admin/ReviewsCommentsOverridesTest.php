<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ReviewsCommentsOverrides;
use ReflectionClass;
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
		global $current_screen;

		$screen = new \stdClass();
		$screen->base = $current_screen_base;

		$current_screen = $screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		ob_start();

		ReviewsCommentsOverrides::get_instance()->display_notices();

		$output = ob_get_clean();

		if ( $should_display_notices ) {
			$this->assertNotEmpty( $output );
		} else {
			$this->assertEmpty( $output );
		}
	}

	/** @see test_display_notices() */
	public function provider_test_display_notices() : \Generator {
		yield 'Comments page' => [ 'edit-comments', true ];
		yield 'Posts page' => [ 'edit', false ];
		yield 'Product Reviews page' => [ 'product_page_product-reviews', false ];
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsCommentsOverrides::display_reviews_moved_notice()
	 */
	public function test_display_reviews_moved_notice() {
		$overrides = new ReviewsCommentsOverrides();
		$method = ( new ReflectionClass( $overrides ) )->getMethod( 'display_reviews_moved_notice' );
		$method->setAccessible( true );

		ob_start();

		$method->invoke( $overrides );

		$output = trim( ob_get_clean() );

		$this->assertSame(
			'<div class="notice notice-info is-dismissible">
			<p>
				<strong>Product reviews have moved!</strong>
			</p>
			<p>
				Product reviews can now be managed from Products &gt; Reviews.			</p>
			<p class="submit">
				<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews" class="button-primary">
					Visit new location				</a>
								<a href="#" class="button-secondary">
					Learn more about product reviews				</a>
			</p>
		</div>',
			$output
		);
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
