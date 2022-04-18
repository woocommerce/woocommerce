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
	 * Tests that can exclude reviews from comments in the comments page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsCommentsOverrides::exclude_reviews_from_comments()
	 *
	 * @return void
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
