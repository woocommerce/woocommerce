<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\ProductReviews;

use Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides;
use Generator;
use ReflectionClass;
use ReflectionException;
use WC_Unit_Test_Case;

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
	 * @testdox `display_notices` determines whether to display notices for the $current_screen_base.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides::display_notices()
	 * @dataProvider provider_test_display_notices()
	 *
	 * @param string $current_screen_base    The current WP_Screen base value.
	 * @param bool   $should_display_notices Whether notices should be displayed.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_display_notices( string $current_screen_base, bool $should_display_notices ) : void {
		global $current_screen;

		$screen = new \stdClass();
		$screen->base = $current_screen_base;

		$current_screen = $screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// phpcs:disable Squiz.Commenting
		$instance = new class() extends ReviewsCommentsOverrides {
			public $maybe_display_reviews_moved_notice_called = 0;

			protected function maybe_display_reviews_moved_notice() : void {
				$this->maybe_display_reviews_moved_notice_called++;
			}
		};
		// phpcs:enable Squiz.Commenting

		$method = ( new ReflectionClass( $instance ) )->getMethod( 'display_notices' );
		$method->setAccessible( true );

		$method->invoke( $instance );

		$this->assertSame( (int) $should_display_notices, $instance->maybe_display_reviews_moved_notice_called );
	}

	/** @see test_display_notices() */
	public function provider_test_display_notices() : Generator {
		yield 'Comments page' => [ 'edit-comments', true ];
		yield 'Posts page' => [ 'edit', false ];
		yield 'Product Reviews page' => [ 'product_page_product-reviews', false ];
	}

	/**
	 * @testdox `maybe_display_reviews_moved_notice` displays the notice if the $current_screen_base is 'edit-comments'.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides::maybe_display_reviews_moved_notice()
	 * @dataProvider provider_test_maybe_display_reviews_moved_notice()
	 *
	 * @param bool $should_display_notice Whether the reviews moved notice should be displayed.
	 *
	 * @return void
	 * @throws ReflectionException If the method is not found.
	 */
	public function test_maybe_display_reviews_moved_notice( bool $should_display_notice ) : void {

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

			protected function display_reviews_moved_notice() : void {
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
	public function provider_test_maybe_display_reviews_moved_notice() : Generator {
		yield [ true ];
		yield [ false ];
	}

	/**
	 * @testdox `maybe_display_reviews_moved_notice` determines whether the notice should be displayed based on user capabilities and notice dismissal state.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides::should_display_reviews_moved_notice()
	 * @dataProvider provider_test_should_display_reviews_moved_notice()
	 *
	 * @param bool $user_has_capability       Whether the user has the capability to see the new page.
	 * @param bool $user_has_dismissed_notice Whether the user has dismissed this notice before.
	 * @param bool $expected                  Whether the reviews moved notice should be displayed.
	 *
	 * @return void
	 * @throws ReflectionException Throws if the method is not accessible.
	 */
	public function test_should_display_reviews_moved_notice( bool $user_has_capability, bool $user_has_dismissed_notice, bool $expected ) : void {
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

		$should_display_notice = $method->invoke( wc_get_container()->get( ReviewsCommentsOverrides::class ) );

		$this->assertSame( $expected, $should_display_notice );
	}

	/** @see test_should_display_reviews_moved_notice() */
	public function provider_test_should_display_reviews_moved_notice() : Generator {
		yield 'user does not have the capability to see the new page' => [ false, false, false ];
		yield 'user already dismissed this notice' => [ true, true, false ];
		yield 'user has the capability and have not dismissed the notice' => [ true, false, true ];
	}

	/**
	 * @testdox `display_reviews_moved_notice` displays the notice.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides::display_reviews_moved_notice()
	 *
	 * @return void
	 * @throws ReflectionException Thrown when the method does not exist.
	 */
	public function test_display_reviews_moved_notice() : void {
		$overrides = wc_get_container()->get( ReviewsCommentsOverrides::class );
		$method = ( new ReflectionClass( $overrides ) )->getMethod( 'display_reviews_moved_notice' );
		$method->setAccessible( true );

		ob_start();

		$method->invoke( $overrides );

		$output = trim( ob_get_clean() );

		$nonce = wp_create_nonce( 'woocommerce_hide_notices_nonce' );

		$this->assertStringContainsString( '<div class="notice notice-info is-dismissible">', $output );
		$this->assertStringContainsString( '<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews" class="button-primary">', $output );
		$this->assertStringContainsString( '<button type="button" class="notice-dismiss" onclick="window.location = \'?wc-hide-notice=product_reviews_moved&#038;_wc_notice_nonce=' . $nonce . '\';">', $output );
	}

	/**
	 * @testdox `get_dismiss_capability` returns the $expected_capability user capability for dismissing a $notice_name.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides::get_dismiss_capability()
	 * @dataProvider provider_test_get_dismiss_capability()
	 *
	 * @param string $default_capability The default required capability.
	 * @param string $notice_name The notice name.
	 * @param string $expected_capability The expected capability.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_dismiss_capability( string $default_capability, string $notice_name, string $expected_capability ) : void {
		$overrides = wc_get_container()->get( ReviewsCommentsOverrides::class );

		$method = ( new ReflectionClass( $overrides ) )->getMethod( 'get_dismiss_capability' );
		$method->setAccessible( true );

		$this->assertSame( $expected_capability, $method->invoke( $overrides, $default_capability, $notice_name ) );
	}

	/** @see test_get_dismiss_capability() */
	public function provider_test_get_dismiss_capability() : Generator {
		yield 'another notice' => [ 'manage_woocommerce', 'other_notice', 'manage_woocommerce' ];
		yield 'product reviews moved notice' => [ 'manage_woocommerce', ReviewsCommentsOverrides::REVIEWS_MOVED_NOTICE_ID, 'moderate_comments' ];
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
		$overrides = wc_get_container()->get( ReviewsCommentsOverrides::class );

		$original_args = [
			'post_type' => [ 'product' ],
		];

		$this->assertTrue( in_array( 'product', $original_args['post_type'] ) );

		$method = ( new ReflectionClass( $overrides ) )->getMethod( 'exclude_reviews_from_comments' );
		$method->setAccessible( true );

		$new_args = $method->invoke( $overrides, $original_args );

		$this->assertFalse( in_array( 'product', $new_args['post_type'] ) );
	}

}
