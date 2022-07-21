<?php

/**
 * Class WC_Shop_Customizer_Test
 */
class WC_Shop_Customizer_Test extends \WC_Unit_Test_Case {
	/**
	 * Tests that the customizer and all its related scripts are registered when a classic theme is active.
	 */
	public function test_customizer_is_registered_when_using_a_classic_theme() {
		switch_theme( 'storefront' );

		$customizer = new WC_Shop_Customizer();

		$this->assertEquals( 10, has_action( 'customize_register', array( $customizer, 'add_sections' ) ) );
		$this->assertEquals( 10, has_action( 'customize_controls_print_styles', array( $customizer, 'add_styles' ) ) );
		$this->assertEquals( 30, has_action( 'customize_controls_print_scripts', array( $customizer, 'add_scripts' ) ) );
		$this->assertEquals( 10, has_action( 'wp_enqueue_scripts', array( $customizer, 'add_frontend_scripts' ) ) );
	}

	/**
	 * Tests that the customizer is not loaded when a FSE theme is active.
	 */
	public function test_customizer_is_not_registered_when_using_a_classic_theme() {
		switch_theme( 'twentytwentytwo' );

		$customizer = new WC_Shop_Customizer();

		$this->assertFalse( has_action( 'customize_register', array( $customizer, 'add_sections' ) ) );
		$this->assertFalse( has_action( 'customize_controls_print_styles', array( $customizer, 'add_styles' ) ) );
		$this->assertFalse( has_action( 'customize_controls_print_scripts', array( $customizer, 'add_scripts' ) ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', array( $customizer, 'add_frontend_scripts' ) ) );
	}
}
