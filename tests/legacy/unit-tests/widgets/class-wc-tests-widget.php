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
	 * Test intance creation
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
}
