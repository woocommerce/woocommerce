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
	 * @testdox Tests that the method adds the 'Inter' and 'Cardo' fonts to the theme JSON data.
	 */
	public function test_experimental_filter_theme_json_theme() {
		$theme_json   = $this->createMock( \WP_Theme_JSON_Data::class );
		$initial_data = array(
			'settings' => array(
				'typography' => array(
					'fontFamilies' => array(
						'theme' => array(
							array(
								'fontFamily' => 'Existing Font',
								'name'       => 'Existing Font',
								'slug'       => 'existing-font',
								'fontFace'   => array(
									array(
										'fontFamily' => 'Existing Font',
										'fontStyle'  => 'normal',
										'fontWeight' => '400',
										'src'        => array( 'existing-font.woff2' ),
									),
								),
							),
							array(
								'fontFamily' => 'Unnamed Font',
								'slug'       => 'unnamed-font',
								'fontFace'   => array(
									array(
										'fontFamily' => 'Unnamed Font',
										'fontStyle'  => 'normal',
										'fontWeight' => '400',
										'src'        => array( 'unnamed-font.woff2' ),
									),
								),
							),
						),
					),
				),
			),
		);

		$theme_json->method( 'get_data' )->willReturn( $initial_data );

		$theme_json->expects( $this->once() )
			->method( 'update_with' )
			->with(
				$this->callback(
					function ( $new_data ) {
						$fonts      = $new_data['settings']['typography']['fontFamilies']['theme'];
						$font_names = array_column( $fonts, 'name' );
						return in_array( 'Inter', $font_names, true ) && in_array( 'Cardo', $font_names, true );
					}
				)
			);

		$this->sut->experimental_filter_theme_json_theme( $theme_json );
	}

	/**
	 * Creates a parent and child theme in a temporary directory and activates the child.
	 *
	 * @param array $parent_fonts Font presets declared by the parent theme.
	 * @param array $child_fonts  Font presets declared by the child theme.
	 * @return string The temporary theme root.
	 */
	private function activate_child_theme( array $parent_fonts, array $child_fonts ): string {
		$root = trailingslashit( get_temp_dir() ) . 'wc-coming-soon-themes-' . wp_generate_password( 8, false );
		wp_mkdir_p( $root . '/parent-theme' );
		wp_mkdir_p( $root . '/child-theme' );

		$json = function ( array $fonts ) {
			$settings = empty( $fonts ) ? new \stdClass() : array( 'typography' => array( 'fontFamilies' => $fonts ) );
			return wp_json_encode(
				array(
					'version'  => 3,
					'settings' => $settings,
				)
			);
		};

		file_put_contents( $root . '/parent-theme/style.css', '/* Theme Name: Parent Theme */' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $root . '/parent-theme/theme.json', $json( $parent_fonts ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $root . '/child-theme/style.css', '/* Theme Name: Child Theme */' . PHP_EOL . '/* Template: parent-theme */' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $root . '/child-theme/theme.json', $json( $child_fonts ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		register_theme_directory( $root );
		wp_clean_themes_cache();
		switch_theme( 'child-theme' );

		return $root;
	}

	/**
	 * Runs the theme.json filter with the given existing fonts and returns the resulting font list.
	 *
	 * @param array $existing_fonts Fonts already present in the theme data.
	 * @return array The fonts passed to update_with().
	 */
	private function get_filtered_fonts( array $existing_fonts ): array {
		$theme_json = $this->createMock( \WP_Theme_JSON_Data::class );
		$theme_json->method( 'get_data' )->willReturn(
			array( 'settings' => array( 'typography' => array( 'fontFamilies' => array( 'theme' => $existing_fonts ) ) ) )
		);

		$result = null;
		$theme_json->method( 'update_with' )->willReturnCallback(
			function ( $new_data ) use ( &$result, $theme_json ) {
				$result = $new_data['settings']['typography']['fontFamilies']['theme'];
				return $theme_json;
			}
		);

		$this->sut->experimental_filter_theme_json_theme( $theme_json );
		return $result;
	}

	/**
	 * @testdox A child theme's own fonts are not replaced by parent fonts that share a slug.
	 */
	public function test_experimental_filter_theme_json_theme_keeps_child_fonts_with_same_slug_as_parent() {
		$original = get_stylesheet();
		$parent   = array(
			array(
				'fontFamily' => '"Figtree", sans-serif',
				'name'       => 'Figtree',
				'slug'       => 'fontprimary',
			),
		);
		$child    = array(
			array(
				'fontFamily' => '"Inter", sans-serif',
				'name'       => 'Inter Primary',
				'slug'       => 'fontprimary',
			),
		);
		$root     = $this->activate_child_theme( $parent, $child );

		try {
			$fonts = $this->get_filtered_fonts( $child );
			$slugs = array_column( $fonts, 'slug' );

			$this->assertSame( 1, count( array_keys( $slugs, 'fontprimary', true ) ) );
			$this->assertSame( 'Inter Primary', $fonts[0]['name'] );
			$this->assertContains( 'inter', $slugs );
			$this->assertContains( 'cardo', $slugs );
		} finally {
			switch_theme( $original );
			wp_clean_themes_cache();
			$GLOBALS['wp_theme_directories'] = array_values( array_diff( $GLOBALS['wp_theme_directories'], array( $root ) ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * @testdox A child theme without fonts of its own inherits the parent theme's fonts.
	 */
	public function test_experimental_filter_theme_json_theme_inherits_parent_fonts_when_child_has_none() {
		$original = get_stylesheet();
		$parent   = array(
			array(
				'fontFamily' => '"Figtree", sans-serif',
				'name'       => 'Figtree',
				'slug'       => 'figtree',
			),
		);
		$root     = $this->activate_child_theme( $parent, array() );

		try {
			$slugs = array_column( $this->get_filtered_fonts( array() ), 'slug' );

			$this->assertContains( 'figtree', $slugs );
			$this->assertContains( 'inter', $slugs );
			$this->assertContains( 'cardo', $slugs );
		} finally {
			switch_theme( $original );
			wp_clean_themes_cache();
			$GLOBALS['wp_theme_directories'] = array_values( array_diff( $GLOBALS['wp_theme_directories'], array( $root ) ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
}
