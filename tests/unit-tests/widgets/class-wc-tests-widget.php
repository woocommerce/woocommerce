<?php
/**
 * Testing WC_Widget functionality.
 *
 * @package WooCommerce/Tests/Widgets
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
		require_once 'class-dummy-widget.php';
		$dummy_widget = new Dummy_Widget();
		$this->assertTrue( property_exists( $dummy_widget, 'widget_id' ) );
	}

	public function test_caching() {
		global $wp_widget_factory;
		require_once 'class-dummy-widget.php';
		register_widget( 'Dummy_Widget' );

		$dummy_widget = $wp_widget_factory->widgets['Dummy_Widget'];
		$this->assertFalse( $dummy_widget->get_cached_widget( array( 'widget_id' => $dummy_widget->widget_id ) ) );
		$dummy_widget->widget( array( 'widget_id' => $dummy_widget->widget_id ), array() );
		$this->assertTrue( $dummy_widget->get_cached_widget( array( 'widget_id' => $dummy_widget->widget_id ) ) );
	}

	public function test_form() {
		global $wp_widget_factory;
		require_once 'class-dummy-widget.php';
		register_widget( 'Dummy_Widget' );
		$dummy_widget = $wp_widget_factory->widgets['Dummy_Widget'];
		$this->assertEmpty( $dummy_widget->form( array( 'widget_id' => $dummy_widget->widget_id ) ) );
	}
}
