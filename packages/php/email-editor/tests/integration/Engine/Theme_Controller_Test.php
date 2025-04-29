<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\User_Theme;

/**
 * Integration test for Theme_Controller class
 */
class Theme_Controller_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Theme controller instance
	 *
	 * @var Theme_Controller
	 */
	private Theme_Controller $theme_controller;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Switch theme for easier testing theme colors.
		switch_theme( 'twentytwentyfour' );

		// Crete a custom user theme post.
		$styles_data = array(
			'version'                     => 3,
			'isGlobalStylesUserThemeJSON' => true,
			'styles'                      => array(
				'color' => array(
					'background' => '#123456',
					'text'       => '#654321',
				),
			),
		);
		$post_data   = array(
			'post_title'   => __( 'Custom Email Styles', 'woocommerce' ),
			'post_name'    => 'wp-global-styles-woocommerce-email',
			'post_content' => (string) wp_json_encode( $styles_data, JSON_FORCE_OBJECT ),
			'post_status'  => 'publish',
			'post_type'    => 'wp_global_styles',
		);
		wp_insert_post( $post_data );

		$this->theme_controller = $this->di_container->get( Theme_Controller::class );
	}

	/**
	 * Test it get the theme json
	 *
	 * @return void
	 */
	public function testItGetThemeJson(): void {
		$theme_json     = $this->theme_controller->get_theme();
		$theme_settings = $theme_json->get_settings();

		$this->assertEquals( '660px', $theme_settings['layout']['contentSize'] ); // from email editor theme.json file.
		$this->assertFalse( $theme_settings['spacing']['margin'] );
		$this->assertFalse( $theme_settings['typography']['dropCap'] );
	}

	/**
	 * Test it generates css styles for renderer
	 */
	public function testItGeneratesCssStylesForRenderer() {
		$css = $this->theme_controller->get_stylesheet_for_rendering();
		// Font families.
		$this->assertStringContainsString( '.has-arial-font-family', $css );
		$this->assertStringContainsString( '.has-comic-sans-ms-font-family', $css );
		$this->assertStringContainsString( '.has-courier-new-font-family', $css );
		$this->assertStringContainsString( '.has-georgia-font-family', $css );
		$this->assertStringContainsString( '.has-lucida-font-family', $css );
		$this->assertStringContainsString( '.has-tahoma-font-family', $css );
		$this->assertStringContainsString( '.has-times-new-roman-font-family', $css );
		$this->assertStringContainsString( '.has-trebuchet-ms-font-family', $css );
		$this->assertStringContainsString( '.has-verdana-font-family', $css );
		$this->assertStringContainsString( '.has-arvo-font-family', $css );
		$this->assertStringContainsString( '.has-lato-font-family', $css );
		$this->assertStringContainsString( '.has-merriweather-font-family', $css );
		$this->assertStringContainsString( '.has-merriweather-sans-font-family', $css );
		$this->assertStringContainsString( '.has-noticia-text-font-family', $css );
		$this->assertStringContainsString( '.has-open-sans-font-family', $css );
		$this->assertStringContainsString( '.has-playfair-display-font-family', $css );
		$this->assertStringContainsString( '.has-roboto-font-family', $css );
		$this->assertStringContainsString( '.has-source-sans-pro-font-family', $css );
		$this->assertStringContainsString( '.has-oswald-font-family', $css );
		$this->assertStringContainsString( '.has-raleway-font-family', $css );
		$this->assertStringContainsString( '.has-permanent-marker-font-family', $css );
		$this->assertStringContainsString( '.has-pacifico-font-family', $css );

		$this->assertStringContainsString( '.has-small-font-size', $css );
		$this->assertStringContainsString( '.has-medium-font-size', $css );
		$this->assertStringContainsString( '.has-large-font-size', $css );
		$this->assertStringContainsString( '.has-x-large-font-size', $css );

		// Font sizes.
		$this->assertStringContainsString( '.has-small-font-size', $css );
		$this->assertStringContainsString( '.has-medium-font-size', $css );
		$this->assertStringContainsString( '.has-large-font-size', $css );
		$this->assertStringContainsString( '.has-x-large-font-size', $css );

		// Colors.
		$this->assertStringContainsString( '.has-black-color', $css );
		$this->assertStringContainsString( '.has-black-background-color', $css );
		$this->assertStringContainsString( '.has-black-border-color', $css );

		$this->assertStringContainsString( '.has-black-color', $css );
		$this->assertStringContainsString( '.has-black-background-color', $css );
		$this->assertStringContainsString( '.has-black-border-color', $css );

		$this->assertStringContainsString( '.has-vivid-purple-background-color', $css );
		$this->assertStringContainsString( '.has-vivid-purple-color', $css );
		$this->assertStringContainsString( '.has-vivid-purple-border-color', $css );
	}

	/**
	 * Test if the theme controller translates font size slug to font size value
	 */
	public function testItCanTranslateFontSizeSlug() {
		$this->assertEquals( '13px', $this->theme_controller->translate_slug_to_font_size( 'small' ) );
		$this->assertEquals( '16px', $this->theme_controller->translate_slug_to_font_size( 'medium' ) );
		$this->assertEquals( '28px', $this->theme_controller->translate_slug_to_font_size( 'large' ) );
		$this->assertEquals( '42px', $this->theme_controller->translate_slug_to_font_size( 'x-large' ) );
		$this->assertEquals( 'unknown', $this->theme_controller->translate_slug_to_font_size( 'unknown' ) );
	}

	/**
	 * Test if the theme controller translates font family slug to font family name
	 */
	public function testItCanTranslateColorSlug() {
		$this->assertEquals( '#000000', $this->theme_controller->translate_slug_to_color( 'black' ) );
		$this->assertEquals( '#ffffff', $this->theme_controller->translate_slug_to_color( 'white' ) );
		$this->assertEquals( '#abb8c3', $this->theme_controller->translate_slug_to_color( 'cyan-bluish-gray' ) );
		$this->assertEquals( '#f78da7', $this->theme_controller->translate_slug_to_color( 'pale-pink' ) );
		$this->assertEquals( '#9b51e0', $this->theme_controller->translate_slug_to_color( 'vivid-purple' ) );
	}

	/**
	 * Test if the theme controller loads custom user theme
	 */
	public function testItLoadsCustomUserTheme() {
		$theme = $this->theme_controller->get_theme();
		$this->assertEquals( '#123456', $theme->get_raw_data()['styles']['color']['background'] );
		$this->assertEquals( '#654321', $theme->get_raw_data()['styles']['color']['text'] );
	}

	/**
	 * Test if the theme controller loads custom user theme and applies it to the styles
	 */
	public function testItAddCustomUserThemeToStyles() {
		$theme  = $this->theme_controller->get_theme();
		$styles = $theme->get_stylesheet();
		$this->assertStringContainsString( 'color: #654321', $styles );
		$this->assertStringContainsString( 'background-color: #123456', $styles );
	}

	/**
	 * Test if the theme controller returns correct color styles
	 */
	public function testGetBaseThemeDoesNotIncludeUserThemeData() {
		$theme = $this->theme_controller->get_base_theme();
		$this->assertEquals( '#ffffff', $theme->get_raw_data()['styles']['color']['background'] );
		$this->assertEquals( '#1e1e1e', $theme->get_raw_data()['styles']['color']['text'] );
	}

	/**
	 * Test if the theme controller returns correct color palette
	 */
	public function testItLoadsColorPaletteFromSiteTheme() {
		$settings = $this->theme_controller->get_settings();
		$this->assertNotEmpty( $settings['color']['palette']['theme'] );
	}

	/**
	 * Test if the theme controller returns correct preset variables map
	 */
	public function testItReturnsCorrectPresetVariablesMap() {
		$variable_map = $this->theme_controller->get_variables_values_map();
		$this->assertSame( '#000000', $variable_map['--wp--preset--color--black'] );
		$this->assertSame( '20px', $variable_map['--wp--preset--spacing--20'] );
	}
}
