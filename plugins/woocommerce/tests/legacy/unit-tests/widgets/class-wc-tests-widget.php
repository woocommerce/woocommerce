<?php
/**
 * Testing WC_Widget functionality.
 *
 * @package WooCommerce\Tests\Widgets
 */

/**
 * Class for testing WC_Widget functionality.
 */
class WC_Tests_Widget extends WC_Unit_Test_Case {
	/**
	 * Test instance creation
	 *
	 * @return void
	 */
	public function test_instance() {
		require_once __DIR__ . '/class-dummy-widget.php';
		$dummy_widget = new Dummy_Widget();
		$this->assertTrue( property_exists( $dummy_widget, 'widget_id' ) );
	}

	/**
	 * Test widget caching.
	 *
	 * @return void
	 */
	public function test_caching() {
		global $wp_widget_factory;
		require_once __DIR__ . '/class-dummy-widget.php';
		register_widget( 'Dummy_Widget' );

		$dummy_widget = $wp_widget_factory->widgets['Dummy_Widget'];

		// Uncached widget.
		ob_start();
		$cache_hit = $dummy_widget->get_cached_widget( array( 'widget_id' => $dummy_widget->widget_id ) );
		$output    = ob_get_clean();
		$this->assertFalse( $cache_hit );
		$this->assertEmpty( $output );

		// Render widget to prime the cache.
		ob_start();
		$dummy_widget->widget( array( 'widget_id' => $dummy_widget->widget_id ), array() );
		ob_get_clean();

		// Cached widget.
		ob_start();
		$cache_hit = $dummy_widget->get_cached_widget( array( 'widget_id' => $dummy_widget->widget_id ) );
		$output    = ob_get_clean();
		$this->assertTrue( $cache_hit );
		$this->assertEquals( 'Dummy', $output );
	}

	/**
	 * Test widget form.
	 *
	 * @return void
	 */
	public function test_form() {
		global $wp_widget_factory;
		require_once __DIR__ . '/class-dummy-widget.php';
		register_widget( 'Dummy_Widget' );
		$dummy_widget = $wp_widget_factory->widgets['Dummy_Widget'];
		$this->assertEmpty( $dummy_widget->form( array( 'widget_id' => $dummy_widget->widget_id ) ) );
	}

	/**
	 * @testdox Should preserve extension-provided recently viewed cookie IDs in widget queries.
	 */
	public function test_recently_viewed_widget_query_preserves_extension_cookie_ids(): void {
		$viewed_product_ids = range( 1, 20 );
		$query_args         = array();
		$query_args_filter  = static function ( $args ) use ( &$query_args ) {
			$query_args = $args;

			return $args;
		};
		$cookies            = $_COOKIE; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Preserve global test cookie state.

		$_COOKIE['woocommerce_recently_viewed'] = implode( '|', $viewed_product_ids );
		add_filter( 'woocommerce_recently_viewed_products_widget_query_args', $query_args_filter );

		try {
			$sut = new WC_Widget_Recently_Viewed();

			ob_start();
			try {
				$sut->widget( array(), array() );
			} finally {
				ob_end_clean();
			}

			$this->assertSame(
				array_reverse( $viewed_product_ids ),
				$query_args['post__in'],
				'The recently viewed widget query should preserve every product ID provided through the shared cookie.'
			);
		} finally {
			remove_filter( 'woocommerce_recently_viewed_products_widget_query_args', $query_args_filter );

			$_COOKIE = $cookies;
		}
	}
}
