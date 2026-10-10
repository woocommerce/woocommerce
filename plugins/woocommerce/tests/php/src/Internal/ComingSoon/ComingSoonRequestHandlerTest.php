<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ComingSoon;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonRequestHandler;

/**
 * Tests for the coming soon cache invalidator class.
 */
class ComingSoonRequestHandlerTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var ComingSoonRequestHandler;
	 */
	private $sut;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( ComingSoonRequestHandler::class );
	}

	/**
	 * @testdox Test request parser displays a coming soon page to public visitor.
	 */
	public function test_coming_soon_mode_shown_to_visitor() {
		$this->markTestSkipped( 'The die statement breaks the test. To be improved.' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		$wp          = new \WP();
		$wp->request = '/';
		do_action_ref_array( 'parse_request', array( &$wp ) );

		$this->assertSame( $wp->query_vars['page_id'], 99 );
	}

	/**
	 * @testdox Test request parser displays a live page to public visitor.
	 */
	public function test_live_mode_shown_to_visitor() {
		$this->markTestSkipped( 'The die statement breaks the test. To be improved.' );
		update_option( 'woocommerce_coming_soon', 'no' );
		$wp          = new \WP();
		$wp->request = '/';
		do_action_ref_array( 'parse_request', array( &$wp ) );

		$this->assertArrayNotHasKey( 'page_id', $wp->query_vars );
	}

	/**
	 * @testdox Test request parser excludes admins.
	 */
	public function test_shop_manager_exclusion() {
		$this->markTestSkipped( 'Failing in CI but not locally. To be investigated.' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		$user_id = $this->factory->user->create(
			array(
				'role' => 'shop_manager',
			)
		);
		wp_set_current_user( $user_id );

		$wp          = new \WP();
		$wp->request = '/';
		do_action_ref_array( 'parse_request', array( &$wp ) );

		$this->assertSame( $wp->query_vars['page_id'], null );
	}

	/**
	 * @testdox Adds a bundled font only when the theme does not already provide it by name, or by font family and slug with its own font faces.
	 * @dataProvider provider_theme_fonts
	 *
	 * @param array<int, array<string, mixed>> $theme_fonts    Theme font presets.
	 * @param string[]                         $expected_slugs Preset slugs after filtering.
	 */
	public function test_experimental_filter_theme_json_theme_adds_missing_bundled_fonts( array $theme_fonts, array $expected_slugs ): void {
		$fonts = $this->filter_theme_fonts( $theme_fonts );

		$this->assertSame( $expected_slugs, array_column( $fonts, 'slug' ) );
		$this->assertSame( $theme_fonts, array_slice( $fonts, 0, count( $theme_fonts ) ), 'Theme fonts should be kept unchanged and in order.' );
	}

	/**
	 * Theme font presets and the preset slugs expected after the Coming Soon fonts are added.
	 *
	 * @return array<string, array{0: array<int, array<string, mixed>>, 1: string[]}>
	 */
	public function provider_theme_fonts(): array {
		return array(
			'no bundled fonts'                        => array(
				array(
					array(
						'fontFamily' => 'Theme Sans',
						'name'       => 'Theme Sans',
						'slug'       => 'theme-sans',
					),
				),
				array( 'theme-sans', 'inter', 'cardo' ),
			),
			'bundled fonts without the bundled names' => array(
				array(
					array(
						'fontFamily' => '"Inter", sans-serif',
						'name'       => 'Sans Serif: Inter',
						'slug'       => 'inter',
						'fontFace'   => array( array( 'fontFamily' => 'Inter' ) ),
					),
					array(
						'fontFamily' => 'Cardo',
						'slug'       => 'cardo',
						'fontFace'   => array( array( 'fontFamily' => 'Cardo' ) ),
					),
				),
				array( 'inter', 'cardo' ),
			),
			'bundled font family and slug without font faces' => array(
				array(
					array(
						'fontFamily' => '"Inter", sans-serif',
						'slug'       => 'inter',
					),
				),
				array( 'inter', 'inter', 'cardo' ),
			),
			'bundled name with another font family'   => array(
				array(
					array(
						'fontFamily' => 'IBM Plex Serif, sans-serif',
						'name'       => 'IBMPlexSerif',
						'slug'       => 'ibm-plex-serif',
					),
					array(
						'fontFamily' => 'Inter, sans-serif',
						'name'       => 'Inter',
						'slug'       => 'inter',
					),
				),
				array( 'ibm-plex-serif', 'inter', 'cardo' ),
			),
			'unnamed bundled font family under another slug' => array(
				array(
					array(
						'fontFamily' => '"Inter", sans-serif',
						'slug'       => 'body',
						'fontFace'   => array( array( 'fontFamily' => 'Inter' ) ),
					),
				),
				array( 'body', 'inter', 'cardo' ),
			),
			'unnamed bundled slug with another font family' => array(
				array(
					array(
						'fontFamily' => 'Theme Serif, serif',
						'slug'       => 'cardo',
						'fontFace'   => array( array( 'fontFamily' => 'Theme Serif' ) ),
					),
				),
				array( 'cardo', 'inter', 'cardo' ),
			),
		);
	}

	/**
	 * Filters the supplied theme fonts through the Coming Soon theme JSON handler.
	 *
	 * @param array<int, array<string, mixed>> $font_data Theme font definitions.
	 * @return array<int, array<string, mixed>> Filtered theme font definitions.
	 */
	private function filter_theme_fonts( array $font_data ): array {
		$theme_json     = $this->createMock( \WP_Theme_JSON_Data::class );
		$captured_fonts = array();

		$theme_json->method( 'get_data' )->willReturn(
			array(
				'settings' => array(
					'typography' => array(
						'fontFamilies' => array(
							'theme' => $font_data,
						),
					),
				),
			)
		);
		$theme_json->expects( $this->once() )
			->method( 'update_with' )
			->willReturnCallback(
				function ( array $new_data ) use ( &$captured_fonts ): void {
					$captured_fonts = $new_data['settings']['typography']['fontFamilies']['theme'];
				}
			);

		$this->sut->experimental_filter_theme_json_theme( $theme_json );

		return $captured_fonts;
	}
}
