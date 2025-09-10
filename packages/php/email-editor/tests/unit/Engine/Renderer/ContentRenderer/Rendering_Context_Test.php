<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

/**
 * Unit test for Rendering_Context class.
 */
class Rendering_Context_Test extends \Email_Editor_Unit_Test {
	/**
	 * Test it returns correct layout width without padding.
	 */
	public function testItReturnsLayoutWidthWithoutPadding(): void {
		$styles = array(
			'spacing'    => array(
				'padding'  => array(
					'left'  => '20px',
					'right' => '20px',
				),
				'blockGap' => '0px',
			),
			'color'      => array(),
			'typography' => array(),
		);

		$settings = array(
			'layout' => array(
				'contentSize' => '600px',
			),
			'color'  => array(
				'palette' => array(
					'theme'   => array(),
					'default' => array(),
				),
			),
		);

		/**
		 * WP_Theme_JSON mock for using in test.
		 *
		 * @var \WP_Theme_JSON&\PHPUnit\Framework\MockObject\MockObject $theme_json
		 */
		$theme_json = $this->createMock( \WP_Theme_JSON::class );
		$theme_json->method( 'get_data' )->willReturn( array( 'styles' => $styles ) );
		$theme_json->method( 'get_settings' )->willReturn( $settings );

		$context = new Rendering_Context( $theme_json );

		$this->assertEquals( '560px', $context->get_layout_width_without_padding() );
	}

	/**
	 * Test it translates color slug to real color.
	 */
	public function testItTranslatesSlugToColor(): void {
		$settings = array(
			'layout' => array(
				'contentSize' => '600px',
			),
			'color'  => array(
				'palette' => array(
					'theme'   => array(
						array(
							'slug'  => 'primary',
							'color' => '#FF0000',
						),
					),
					'default' => array(
						array(
							'slug'  => 'secondary',
							'color' => '#00FF00',
						),
					),
				),
			),
		);

		/**
		 * WP_Theme_JSON mock for using in test.
		 *
		 * @var \WP_Theme_JSON&\PHPUnit\Framework\MockObject\MockObject $theme_json
		 */
		$theme_json = $this->createMock( \WP_Theme_JSON::class );
		$theme_json->method( 'get_data' )->willReturn( array( 'styles' => array() ) );
		$theme_json->method( 'get_settings' )->willReturn( $settings );

		$context = new Rendering_Context( $theme_json );

		$this->assertSame( '#ff0000', $context->translate_slug_to_color( 'primary' ) );
		$this->assertSame( '#00ff00', $context->translate_slug_to_color( 'secondary' ) );
		$this->assertSame( 'unknown', $context->translate_slug_to_color( 'unknown' ) );
	}
}
