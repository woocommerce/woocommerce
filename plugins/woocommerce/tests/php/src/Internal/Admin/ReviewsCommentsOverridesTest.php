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

		// phpcs:disable Squiz.Commenting
		$instance = new class() extends ReviewsCommentsOverrides {
			public $maybe_display_reviews_moved_notice_called = 0;

			protected function maybe_display_reviews_moved_notice() {
				$this->maybe_display_reviews_moved_notice_called++;
			}
		};
		// phpcs:enable Squiz.Commenting

		$instance->display_notices();

		$this->assertSame( (int) $should_display_notices, $instance->maybe_display_reviews_moved_notice_called );
	}

	/** @see test_display_notices() */
	public function provider_test_display_notices() : \Generator {
		yield 'Comments page' => [ 'edit-comments', true ];
		yield 'Posts page' => [ 'edit', false ];
		yield 'Product Reviews page' => [ 'product_page_product-reviews', false ];
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsCommentsOverrides::maybe_display_reviews_moved_notice()
	 * @dataProvider provider_test_maybe_display_reviews_moved_notice()
	 * @param bool $should_display_notice Whether the reviews moved notice should be displayed.
	 */
	public function test_maybe_display_reviews_moved_notice( bool $should_display_notice ) {

		// phpcs:disable Squiz.Commenting
		$instance = new class($should_display_notice) extends ReviewsCommentsOverrides {
			public $should_display_reviews_moved_notice_called = 0;
			public $display_reviews_moved_notice_called = 0;

			public function __construct( $should_display_notice ) {
				$this->should_display_notice = $should_display_notice;
				parent::__construct();
			}

			protected function should_display_reviews_moved_notice() : bool {
				$this->should_display_reviews_moved_notice_called++;
				return $this->should_display_notice;
			}

			protected function display_reviews_moved_notice() {
				$this->display_reviews_moved_notice_called++;
			}
		};
		// phpcs:enable Squiz.Commenting

		$reflection = new ReflectionClass( $instance );
		$method = $reflection->getMethod( 'maybe_display_reviews_moved_notice' );
		$method->setAccessible( true );

		$method->invoke( $instance );

		$this->assertSame( 1, $instance->should_display_reviews_moved_notice_called );
		$this->assertSame( (int) $should_display_notice, $instance->display_reviews_moved_notice_called );
	}

	/** @see test_maybe_display_reviews_moved_notice() */
	public function provider_test_maybe_display_reviews_moved_notice() : \Generator {
		yield [ true ];
		yield [ false ];
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsCommentsOverrides::should_display_reviews_moved_notice()
	 * @dataProvider provider_test_should_display_reviews_moved_notice()
	 * @param bool $user_has_capability       Whether the user has the capability to see the new page.
	 * @param bool $user_has_dismissed_notice Whether the user has dismissed this notice before.
	 * @param bool $expected                  Whether the reviews moved notice should be displayed.
	 */
	public function test_should_display_reviews_moved_notice( bool $user_has_capability, bool $user_has_dismissed_notice, bool $expected ) {
		$this->register_legacy_proxy_function_mocks(
			[
				'current_user_can' => function( $capability, ...$args ) use ( $user_has_capability ) {
					if ( 'moderate_comments' === $capability ) {
						return $user_has_capability;
					} else {
						return current_user_can( $capability, $args );
					}
				},
				'get_user_meta' => function ( int $user_id, string $key = '', bool $single = false ) use ( $user_has_dismissed_notice ) {
					if ( 'dismissed_product_reviews_moved_notice' === $key ) {
						return $user_has_dismissed_notice;
					} else {
						return get_user_meta( $user_id, $key, $single );
					}
				},
			]
		);

		$reflection = new ReflectionClass( ReviewsCommentsOverrides::class );
		$method = $reflection->getMethod( 'should_display_reviews_moved_notice' );
		$method->setAccessible( true );

		$should_display_notice = $method->invoke( ReviewsCommentsOverrides::get_instance() );

		$this->assertSame( $expected, $should_display_notice );
	}

	/** @see test_should_display_reviews_moved_notice() */
	public function provider_test_should_display_reviews_moved_notice() : \Generator {
		yield 'user does not have the capability to see the new page' => [ false, false, false ];
		yield 'user already dismissed this notice' => [ true, true, false ];
		yield 'user has the capability and have not dismissed the notice' => [ true, false, true ];
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

		$nonce = wp_create_nonce( 'woocommerce_hide_notices_nonce' );

		$this->assertSame(
			'<div class="notice notice-info">
			<p><strong>Product reviews have moved!</strong></p>
			<p>Product reviews can now be managed from Products &gt; Reviews.</p>
			<p class="submit">
				<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews" class="button-primary">Visit new location</a>
				<a href="?wc-hide-notice=product_reviews_moved&#038;_wc_notice_nonce=' . $nonce . '" class="button">Dismiss</a>
			</p>
		</div>',
			$output
		);
	}

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
