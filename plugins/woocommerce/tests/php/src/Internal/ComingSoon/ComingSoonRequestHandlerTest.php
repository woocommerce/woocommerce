<?php

namespace Automattic\WooCommerce\Tests\ComingSoon;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonRequestHandler;
use Automattic\WooCommerce\Admin\Features\Features;

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
}
