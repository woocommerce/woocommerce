<?php
/**
 * Tests for WC_Shop_Customizer
 *
 * @package WooCommerce\Tests\Customizer
 */

// This comment is needed to prevent a code sniffer error.

use Automattic\WooCommerce\Theming\ThemeSupport;

require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
require_once ABSPATH . WPINC . '/class-wp-customize-control.php';

/**
 * Tests for WC_Shop_Customizer
 */
class WC_Shop_Customizer_Test extends WC_Unit_Test_Case {

	/**
	 * Runs before each test of the class.
	 */
	public function setUp() {
		remove_theme_support( 'woocommerce' );
	}

	/**
	 * @testdox add_sections should add controls for image size settings only if these sizes haven't been explicitly declared as theme support (not counting default declarations).
	 *
	 * @testWith ["single_image_width", true, false]
	 *           ["single_image_width", false, true]
	 *           ["thumbnail_image_width", true, false]
	 *           ["thumbnail_image_width", false, true]
	 *
	 * @param string $option_name The option name to test, either 'single_image_width' or 'thumbnail_image_width'.
	 * @param bool   $add_explicit_theme_support True to test when theme support is added explicitly (not as a default value).
	 * @param bool   $expected_to_have_added_customization True to expect the customization to have been added, false to expect it no to have been added.
	 */
	public function test_add_sections_should_add_image_controls_only_if_no_theme_support_for_image_sizes_is_defined( $option_name, $add_explicit_theme_support, $expected_to_have_added_customization ) {
		$customize_manager = $this->createMock( WP_Customize_Manager::class );
		$added_settings    = array();
		$added_controls    = array();

		$add_setting_callback = function( $id, $args = array() ) use ( &$added_settings ) {
			array_push( $added_settings, $id );
		};
		$add_control_callback = function( $id, $args = array() ) use ( &$added_controls ) {
			array_push( $added_controls, $id );
		};
		$customize_manager->method( 'add_setting' )->will( $this->returnCallback( $add_setting_callback ) );
		$customize_manager->method( 'add_control' )->will( $this->returnCallback( $add_control_callback ) );

		$theme_support      = $this->get_instance_of( ThemeSupport::class );
		$add_support_method = $add_explicit_theme_support ? 'add_options' : 'add_default_options';
		$theme_support->$add_support_method( array( $option_name => 1234 ) );

		$sut = $this->get_legacy_instance_of( WC_Shop_Customizer::class );
		$sut->add_sections( $customize_manager );

		$this->assertEquals( $expected_to_have_added_customization, in_array( 'woocommerce_' . $option_name, $added_settings, true ) );
		$this->assertEquals( $expected_to_have_added_customization, in_array( 'woocommerce_' . $option_name, $added_controls, true ) );
	}
}
