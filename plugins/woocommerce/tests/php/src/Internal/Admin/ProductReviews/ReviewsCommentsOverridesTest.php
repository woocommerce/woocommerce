<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\ProductReviews;

use Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides;
use ReflectionClass;
use ReflectionException;
use WC_Unit_Test_Case;
use WP_Screen;

/**
 * Tests the product reviews overrides for the comments page.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides
 */
class ReviewsCommentsOverridesTest extends WC_Unit_Test_Case {

	/**
	 * Sets the global vars before each test.
	 */
	public function setUp() : void {
		global $current_screen;

		$this->old_current_screen = $current_screen;

		parent::setUp();
	}

	/**
	 * Restores the global vars after each test.
	 */
	public function tearDown() : void {
		global $current_screen;

		$current_screen = $this->old_current_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::tearDown();
	}

	/**
	 * @testdox `exclude_reviews_from_comments` excludes product reviews from the comment query in the comments page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides::exclude_reviews_from_comments()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_exclude_reviews_from_comments() : void {
		global $current_screen;
		$original_screen_value = $current_screen;

		$overrides = wc_get_container()->get( ReviewsCommentsOverrides::class );
		$method    = ( new ReflectionClass( $overrides ) )->getMethod( 'exclude_reviews_from_comments' );
		$method->setAccessible( true );

		$original_args = [
			'post_type' => [ 'product' ],
		];

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$current_screen = WP_Screen::get( 'edit-comments' );
		$filtered_args  = $method->invoke( $overrides, $original_args );

		$this->assertFalse(
			in_array( 'product', $filtered_args['post_type'], true ),
			'In the context of the edit-comments screen, the product post type will be removed from the comments query.'
		);

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$current_screen = WP_Screen::get( 'arbitrary-admin-page' );
		$filtered_args  = $method->invoke( $overrides, $original_args );

		$this->assertTrue(
			in_array( 'product', $filtered_args['post_type'], true ),
			'In the context of all other admin screens, the product post type will not be removed from the comments query.'
		);

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$current_screen = null;
		$filtered_args  = $method->invoke( $overrides, $original_args );

		$this->assertTrue(
			in_array( 'product', $filtered_args['post_type'], true ),
			'If the $current_screen global is not set, the product post type will not be removed from the comments query.'
		);

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$current_screen = $original_screen_value;
	}

}
