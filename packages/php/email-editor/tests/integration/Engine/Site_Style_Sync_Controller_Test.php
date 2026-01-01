<?php
/**
 * Integration tests for Site_Style_Sync_Controller class.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\Site_Style_Sync_Controller;
use Automattic\WooCommerce\EmailEditor\Tests\Integration\Email_Editor_Integration_Test_Case;
use WP_Theme_JSON;

/**
 * Class Site_Style_Sync_Controller_Test
 */
class Site_Style_Sync_Controller_Test extends \Email_Editor_Integration_Test_Case {

	/**
	 * Site Style Sync Controller instance.
	 *
	 * @var Site_Style_Sync_Controller
	 */
	private Site_Style_Sync_Controller $controller;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Switch theme for easier testing theme colors.
		switch_theme( 'twentytwentyfour' );

		$this->controller = new Site_Style_Sync_Controller();
	}

	/**
	 * Test controller initialization.
	 */
	public function test_initialize_adds_hooks(): void {
		// Initialize the controller.
		$this->controller->initialize();

		// Verify that theme change hooks are added.
		$this->assertNotFalse( has_action( 'switch_theme', array( $this->controller, 'invalidate_site_theme_cache' ) ) );
		$this->assertNotFalse( has_action( 'customize_save_after', array( $this->controller, 'invalidate_site_theme_cache' ) ) );
	}

	/**
	 * Test sync is enabled by default.
	 */
	public function test_sync_is_enabled_by_default(): void {
		$this->assertTrue( $this->controller->is_sync_enabled() );
	}

	/**
	 * Test sync can be disabled via filter.
	 */
	public function test_sync_can_be_disabled_via_filter(): void {
		// Add filter to disable sync.
		add_filter( 'woocommerce_email_editor_site_style_sync_enabled', '__return_false' );

		$this->assertFalse( $this->controller->is_sync_enabled() );

		// Clean up.
		remove_filter( 'woocommerce_email_editor_site_style_sync_enabled', '__return_false' );
	}

	/**
	 * Test get_theme returns null when sync is disabled.
	 */
	public function test_get_theme_returns_null_when_disabled(): void {
		// Disable sync.
		add_filter( 'woocommerce_email_editor_site_style_sync_enabled', '__return_false' );

		$result = $this->controller->get_theme();
		$this->assertNull( $result );

		// Clean up.
		remove_filter( 'woocommerce_email_editor_site_style_sync_enabled', '__return_false' );
	}

	/**
	 * Test get_theme returns WP_Theme_JSON when sync is enabled.
	 */
	public function test_get_theme_returns_wp_theme_json_when_enabled(): void {
		$result = $this->controller->get_theme();
		$this->assertInstanceOf( WP_Theme_JSON::class, $result );
	}

	/**
	 * Test sync_site_styles returns properly structured data.
	 */
	public function test_sync_site_styles_returns_structured_data(): void {
		$synced_data = $this->controller->sync_site_styles();

		// Verify structure.
		$this->assertIsArray( $synced_data );
		$this->assertArrayHasKey( 'version', $synced_data );
		$this->assertArrayHasKey( 'settings', $synced_data );
		$this->assertArrayHasKey( 'styles', $synced_data );
		$this->assertEquals( 3, $synced_data['version'] );
	}

	/**
	 * Test sync_site_styles with color palette.
	 */
	public function test_sync_site_styles_with_color_palette(): void {
		// Mock theme data with color palette.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(
				'color' => array(
					'palette' => array(
						'theme' => array(
							array(
								'slug'  => 'primary',
								'color' => '#007cba',
								'name'  => 'Primary Color',
							),
						),
					),
				),
			),
			'styles'   => array(),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		// Use reflection to set the private site_theme property.
		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify color palette is synced.
		$this->assertArrayHasKey( 'color', $synced_data['settings'] );
		$this->assertArrayHasKey( 'palette', $synced_data['settings']['color'] );
		$this->assertEquals( '#007cba', $synced_data['settings']['color']['palette'][0]['color'] );
		$this->assertEquals( 'Primary Color', $synced_data['settings']['color']['palette'][0]['name'] );
	}

	/**
	 * Test sync_site_styles with typography styles.
	 */
	public function test_sync_site_styles_with_typography(): void {
		// Mock theme data with typography.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(),
			'styles'   => array(
				'typography' => array(
					'fontFamily' => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
					'fontSize'   => '16px',
					'fontWeight' => '400',
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify typography is synced.
		$this->assertArrayHasKey( 'typography', $synced_data['styles'] );
		$this->assertStringContainsString( 'Arial, ', $synced_data['styles']['typography']['fontFamily'] );
		$this->assertEquals( '16px', $synced_data['styles']['typography']['fontSize'] );
	}

	/**
	 * Test sync_site_styles with font size conversion.
	 */
	public function test_sync_site_styles_converts_font_sizes(): void {
		// Mock theme data with rem font size.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(),
			'styles'   => array(
				'typography' => array(
					'fontSize' => '1.5rem',
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		// Use reflection to set the private site_theme property.
		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify font size is converted to px.
		$this->assertArrayHasKey( 'typography', $synced_data['styles'] );
		$this->assertStringContainsString( 'px', $synced_data['styles']['typography']['fontSize'] );
	}

	/**
	 * Test sync_site_styles with spacing styles.
	 */
	public function test_sync_site_styles_with_spacing(): void {
		// Mock theme data with spacing.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(),
			'styles'   => array(
				'spacing' => array(
					'padding'  => array(
						'top'    => '1rem',
						'bottom' => '1rem',
						'left'   => '2rem',
						'right'  => '2rem',
					),
					'blockGap' => '1.5rem',
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		// Use reflection to set the private site_theme property.
		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify spacing is synced and converted.
		$this->assertArrayHasKey( 'spacing', $synced_data['styles'] );
		$this->assertArrayHasKey( 'padding', $synced_data['styles']['spacing'] );
		$this->assertArrayHasKey( 'blockGap', $synced_data['styles']['spacing'] );
	}

	/**
	 * Test sync_site_styles with element styles.
	 */
	public function test_sync_site_styles_with_elements(): void {
		// Mock theme data with element styles.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(),
			'styles'   => array(
				'elements' => array(
					'button' => array(
						'color'      => array(
							'background' => '#007cba',
							'text'       => '#ffffff',
						),
						'typography' => array(
							'fontSize' => '14px',
						),
					),
					'link'   => array(
						'color' => array(
							'text' => '#007cba',
						),
					),
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify elements are synced.
		$this->assertArrayHasKey( 'elements', $synced_data['styles'] );
		$this->assertArrayHasKey( 'button', $synced_data['styles']['elements'] );
		$this->assertArrayHasKey( 'link', $synced_data['styles']['elements'] );
	}

	/**
	 * Test synced styles filter is applied.
	 */
	public function test_synced_styles_filter_is_applied(): void {
		$filter_applied = false;

		// Add filter to modify synced data.
		add_filter(
			'woocommerce_email_editor_synced_site_styles',
			function ( $synced_data ) use ( &$filter_applied ) {
				$filter_applied                 = true;
				$synced_data['custom_property'] = 'custom_value';
				return $synced_data;
			},
			10,
			1
		);

		$synced_data = $this->controller->sync_site_styles();

		// Verify filter was applied.
		$this->assertTrue( $filter_applied );
		$this->assertEquals( 'custom_value', $synced_data['custom_property'] );

		// Clean up.
		remove_all_filters( 'woocommerce_email_editor_synced_site_styles' );
	}

	/**
	 * Test cache invalidation when sync is disabled.
	 */
	public function test_invalidate_cache_when_sync_disabled(): void {
		// Disable sync.
		add_filter( 'woocommerce_email_editor_site_style_sync_enabled', '__return_false' );

		// This should return early without doing anything.
		$this->controller->invalidate_site_theme_cache();

		// Since sync is disabled, this should still be disabled.
		$this->assertFalse( $this->controller->is_sync_enabled() );

		// Clean up.
		remove_filter( 'woocommerce_email_editor_site_style_sync_enabled', '__return_false' );
	}

	/**
	 * Test cache invalidation clears cached theme.
	 */
	public function test_invalidate_cache_clears_theme(): void {
		// Get theme to populate cache.
		$theme1 = $this->controller->get_theme();
		$this->assertInstanceOf( WP_Theme_JSON::class, $theme1 );

		// Invalidate cache.
		$this->controller->invalidate_site_theme_cache();

		// Get theme again - should be fresh instance.
		$theme2 = $this->controller->get_theme();
		$this->assertInstanceOf( WP_Theme_JSON::class, $theme2 );
	}

	/**
	 * Test clamp() function conversion in font sizes.
	 */
	public function test_clamp_function_conversion(): void {
		// Mock theme data with clamp font size.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(),
			'styles'   => array(
				'typography' => array(
					'fontSize' => 'clamp(1rem, 2.5vw, 2rem)',
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify clamp is converted to static px value.
		$this->assertArrayHasKey( 'typography', $synced_data['styles'] );
		$font_size = $synced_data['styles']['typography']['fontSize'];
		$this->assertStringContainsString( 'px', $font_size );
		$this->assertStringNotContainsString( 'clamp', $font_size );
	}

	/**
	 * Test string spacing values are converted.
	 */
	public function test_string_spacing_conversion(): void {
		// Mock theme data with string spacing.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(),
			'styles'   => array(
				'spacing' => array(
					'padding' => '2rem',
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		// Use reflection to set the private site_theme property.
		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify string spacing is converted.
		$this->assertArrayHasKey( 'spacing', $synced_data['styles'] );
		$this->assertIsString( $synced_data['styles']['spacing']['padding'] );
	}

	/**
	 * Test initialization occurs on WordPress init hook.
	 */
	public function test_initialization_on_init_hook(): void {
		// Create new controller to test initialization.
		$test_controller = new Site_Style_Sync_Controller();

		// Trigger the init action.
		do_action( 'init' );

		// Verify hooks are added after init.
		$this->assertNotFalse( has_action( 'switch_theme', array( $test_controller, 'invalidate_site_theme_cache' ) ) );
		$this->assertNotFalse( has_action( 'customize_save_after', array( $test_controller, 'invalidate_site_theme_cache' ) ) );
	}

	/**
	 * Test cache invalidation on theme switch.
	 */
	public function test_cache_invalidation_on_theme_switch(): void {
		// Initialize controller.
		$this->controller->initialize();

		// Get initial theme to populate cache.
		$initial_theme = $this->controller->get_theme();
		$this->assertInstanceOf( WP_Theme_JSON::class, $initial_theme );

		// Trigger theme switch action.
		do_action( 'switch_theme' );

		// Cache should be invalidated - this is hard to test directly,
		// but we can verify the action is hooked.
		$this->assertNotFalse( has_action( 'switch_theme', array( $this->controller, 'invalidate_site_theme_cache' ) ) );
	}

	/**
	 * Test cache invalidation on customizer save.
	 */
	public function test_cache_invalidation_on_customizer_save(): void {
		// Initialize controller.
		$this->controller->initialize();

		// Trigger customizer save action.
		do_action( 'customize_save_after' );

		// Verify the action is hooked.
		$this->assertNotFalse( has_action( 'customize_save_after', array( $this->controller, 'invalidate_site_theme_cache' ) ) );
	}

	/**
	 * Test empty styles don't break sync.
	 */
	public function test_empty_styles_dont_break_sync(): void {
		// Mock theme data with empty styles.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(),
			'styles'   => array(),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Should still return valid structure.
		$this->assertIsArray( $synced_data );
		$this->assertEquals( 3, $synced_data['version'] );
		$this->assertIsArray( $synced_data['settings'] );
		$this->assertIsArray( $synced_data['styles'] );
	}

	/**
	 * Test get_theme returns null for empty synced data.
	 */
	public function test_get_theme_returns_null_for_empty_synced_data(): void {
		// Mock sync_site_styles to return empty data.
		$controller = new class() extends Site_Style_Sync_Controller {
			/**
			 * Mock sync_site_styles to return empty data.
			 */
			public function sync_site_styles(): array {
				return array();
			}
		};

		$result = $controller->get_theme();
		$this->assertNull( $result );
	}

	/**
	 * Test get_email_safe_fonts loads and caches fonts from theme.json.
	 */
	public function test_get_email_safe_fonts_loads_from_theme_json(): void {
		$fonts = $this->controller->get_email_safe_fonts();

		// Should be an array.
		$this->assertIsArray( $fonts );

		// Should contain email-safe fonts from theme.json.
		$this->assertArrayHasKey( 'arial', $fonts );
		$this->assertArrayHasKey( 'georgia', $fonts );
		$this->assertArrayHasKey( 'courier-new', $fonts );
		$this->assertArrayHasKey( 'trebuchet-ms', $fonts );
		$this->assertArrayHasKey( 'verdana', $fonts );
		$this->assertArrayHasKey( 'tahoma', $fonts );
		$this->assertArrayHasKey( 'lucida', $fonts );

		// Verify actual font family values.
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $fonts['arial'] );
		$this->assertEquals( "Georgia, Times, 'Times New Roman', serif", $fonts['georgia'] );
		$this->assertEquals( "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace", $fonts['courier-new'] );
	}

	/**
	 * Test convert_to_email_safe_font with email-safe font slug.
	 */
	public function test_convert_to_email_safe_font_with_safe_font_slug(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->controller );
		$method     = $reflection->getMethod( 'convert_to_email_safe_font' );
		$method->setAccessible( true );

		// Test with email-safe font slug.
		$result = $method->invoke( $this->controller, 'arial' );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $result );

		$result = $method->invoke( $this->controller, 'georgia' );
		$this->assertEquals( "Georgia, Times, 'Times New Roman', serif", $result );

		$complex_font_family = "Georgia, Times, 'Times New Roman', serif";
		$result              = $method->invoke( $this->controller, $complex_font_family );
		$this->assertEquals( $complex_font_family, $result );
	}

	/**
	 * Test convert_to_email_safe_font with common web font mappings.
	 */
	public function test_convert_to_email_safe_font_with_web_font_mappings(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->controller );
		$method     = $reflection->getMethod( 'convert_to_email_safe_font' );
		$method->setAccessible( true );

		// Test Helvetica - should be preserved since it's found in Arial fallback.
		$result = $method->invoke( $this->controller, 'Helvetica' );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $result );

		// Test Times - should be preserved since it's found in Georgia fallback.
		$result = $method->invoke( $this->controller, 'Times' );
		$this->assertEquals( "Georgia, Times, 'Times New Roman', serif", $result );

		// Test Courier - should be preserved since it's found in Courier New fallback.
		$result = $method->invoke( $this->controller, 'Courier' );
		$this->assertEquals( "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace", $result );

		// Test with a truly unknown font that would trigger mapping.
		$result = $method->invoke( $this->controller, 'UnknownHelvetica' );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $result );
	}

	/**
	 * Test convert_to_email_safe_font with unknown font defaults to Arial.
	 */
	public function test_convert_to_email_safe_font_unknown_font_defaults_to_arial(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->controller );
		$method     = $reflection->getMethod( 'convert_to_email_safe_font' );
		$method->setAccessible( true );

		// Test with unknown font.
		$result = $method->invoke( $this->controller, 'UnknownFont' );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $result );

		$result = $method->invoke( $this->controller, 'CustomWebFont' );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $result );
	}

	/**
	 * Test font family conversion in typography styles sync.
	 */
	public function test_typography_styles_font_family_conversion(): void {
		// Mock theme data with font that will be preserved.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(),
			'styles'   => array(
				'typography' => array(
					'fontFamily' => 'Helvetica, sans-serif',
					'fontSize'   => '16px',
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify font family is preserved since Helvetica is found in email-safe families.
		$this->assertArrayHasKey( 'typography', $synced_data['styles'] );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $synced_data['styles']['typography']['fontFamily'] );
	}

	/**
	 * Test element styles font conversion.
	 */
	public function test_element_styles_font_conversion(): void {
		// Mock theme data with element styles using fonts that will be preserved.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(),
			'styles'   => array(
				'elements' => array(
					'heading' => array(
						'typography' => array(
							'fontFamily' => 'Helvetica, sans-serif',
							'fontSize'   => '24px',
						),
					),
					'button'  => array(
						'typography' => array(
							'fontFamily' => 'Times, serif',
						),
					),
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify element fonts are processed.
		$this->assertArrayHasKey( 'elements', $synced_data['styles'] );
		$this->assertArrayHasKey( 'heading', $synced_data['styles']['elements'] );
		$this->assertArrayHasKey( 'button', $synced_data['styles']['elements'] );

		// Check font family is preserved in heading since Helvetica is found in email-safe families.
		$this->assertEquals(
			"Arial, 'Helvetica Neue', Helvetica, sans-serif",
			$synced_data['styles']['elements']['heading']['typography']['fontFamily']
		);

		// Check font family is preserved in button since Times is found in email-safe families.
		$this->assertEquals(
			"Georgia, Times, 'Times New Roman', serif",
			$synced_data['styles']['elements']['button']['typography']['fontFamily']
		);
	}

	/**
	 * Test case-insensitive font slug matching.
	 */
	public function test_convert_to_email_safe_font_case_insensitive(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->controller );
		$method     = $reflection->getMethod( 'convert_to_email_safe_font' );
		$method->setAccessible( true );

		// Test with uppercase font names.
		$result = $method->invoke( $this->controller, 'ARIAL' );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $result );

		$result = $method->invoke( $this->controller, 'GEORGIA' );
		$this->assertEquals( "Georgia, Times, 'Times New Roman', serif", $result );

		// Test with mixed case.
		$result = $method->invoke( $this->controller, 'AriaL' );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $result );
	}

	/**
	 * Test actual font conversion for unknown fonts.
	 */
	public function test_convert_unknown_fonts_to_email_safe(): void {
		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->controller );
		$method     = $reflection->getMethod( 'convert_to_email_safe_font' );
		$method->setAccessible( true );

		// Test truly unknown fonts that should map to Arial.
		$result = $method->invoke( $this->controller, 'CustomWebFont' );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $result );

		$result = $method->invoke( $this->controller, 'SomeUnknownFont' );
		$this->assertEquals( "Arial, 'Helvetica Neue', Helvetica, sans-serif", $result );
	}

	/**
	 * Test referenced value that is correctly read and used.
	 */
	public function test_referenced_value_correctly_resolved(): void {
		// Mock theme data with a referenced value within styles subtree.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(
				'color' => array(
					'palette' => array(
						'theme' => array(
							array(
								'slug'  => 'primary',
								'color' => '#007cba',
								'name'  => 'Primary Color',
							),
						),
					),
				),
			),
			'styles'   => array(
				'color'      => array(
					'text'       => '#007cba',
					'background' => array(
						'ref' => 'styles.color.text',
					),
				),
				'typography' => array(
					'fontSize' => '16px',
				),
				'elements'   => array(
					'button' => array(
						'color' => array(
							'background' => array(
								'ref' => 'styles.color.text',
							),
						),
					),
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify the referenced color value is correctly resolved.
		$this->assertArrayHasKey( 'color', $synced_data['styles'] );
		$this->assertArrayHasKey( 'background', $synced_data['styles']['color'] );
		$this->assertEquals( '#007cba', $synced_data['styles']['color']['background'] );

		// Verify button element also references the same color.
		$this->assertArrayHasKey( 'elements', $synced_data['styles'] );
		$this->assertArrayHasKey( 'button', $synced_data['styles']['elements'] );
		$this->assertArrayHasKey( 'color', $synced_data['styles']['elements']['button'] );
		$this->assertEquals( '#007cba', $synced_data['styles']['elements']['button']['color']['background'] );
	}

	/**
	 * Test reference pointing to non-existing path should result in null.
	 */
	public function test_referenced_value_with_invalid_path_returns_null(): void {
		// Mock theme data with invalid reference paths within styles subtree.
		$mock_theme_data = array(
			'version'  => 3,
			'settings' => array(
				'color' => array(
					'palette' => array(
						'theme' => array(
							array(
								'slug'  => 'primary',
								'color' => '#007cba',
								'name'  => 'Primary Color',
							),
						),
					),
				),
			),
			'styles'   => array(
				'color'      => array(
					'text'       => '#333333',
					'background' => array(
						'ref' => 'styles.color.nonexistent', // Invalid path - doesn't exist.
					),
					'link'       => array(
						'ref' => 'styles.elements.button.color.background', // Invalid path - button doesn't exist.
					),
				),
				'typography' => array(
					'fontSize' => '16px',
				),
				'elements'   => array(
					'heading' => array(
						'color' => array(
							'text' => array(
								'ref' => 'styles.typography.nonexistent', // Invalid path - doesn't exist.
							),
						),
					),
				),
			),
		);

		// Create a mock theme.
		$mock_theme = new WP_Theme_JSON( $mock_theme_data );

		$reflection          = new \ReflectionClass( $this->controller );
		$site_theme_property = $reflection->getProperty( 'site_theme' );
		$site_theme_property->setAccessible( true );
		$site_theme_property->setValue( $this->controller, $mock_theme );

		$synced_data = $this->controller->sync_site_styles();

		// Verify that invalid references are not included in synced data.
		$this->assertArrayHasKey( 'color', $synced_data['styles'] );
		$this->assertArrayNotHasKey( 'background', $synced_data['styles']['color'] );
		$this->assertArrayNotHasKey( 'link', $synced_data['styles']['color'] );

		// Verify that invalid references in elements are also not included.
		$this->assertArrayHasKey( 'elements', $synced_data['styles'] );
		$this->assertArrayHasKey( 'heading', $synced_data['styles']['elements'] );
		$this->assertArrayHasKey( 'color', $synced_data['styles']['elements']['heading'] );
		$this->assertArrayNotHasKey( 'text', $synced_data['styles']['elements']['heading']['color'] );
	}
}
