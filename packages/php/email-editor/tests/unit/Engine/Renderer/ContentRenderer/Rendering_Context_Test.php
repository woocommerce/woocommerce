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

	/**
	 * Test it stores and retrieves email context data.
	 */
	public function testItStoresEmailContext(): void {
		/**
		 * WP_Theme_JSON mock for using in test.
		 *
		 * @var \WP_Theme_JSON&\PHPUnit\Framework\MockObject\MockObject $theme_json
		 */
		$theme_json = $this->createMock( \WP_Theme_JSON::class );

		$email_context = array(
			'user_id'         => 123,
			'recipient_email' => 'user@example.com',
			'order_id'        => 456,
			'email_type'      => 'order_confirmation',
		);

		$context = new Rendering_Context( $theme_json, $email_context );

		$this->assertSame( 123, $context->get_user_id() );
		$this->assertSame( 'user@example.com', $context->get_recipient_email() );
		$this->assertSame( 456, $context->get( 'order_id' ) );
		$this->assertSame( 'order_confirmation', $context->get( 'email_type' ) );
		$this->assertSame( $email_context, $context->get_email_context() );
	}

	/**
	 * Test it returns null for missing email context data.
	 */
	public function testItReturnsNullForMissingEmailContext(): void {
		/**
		 * WP_Theme_JSON mock for using in test.
		 *
		 * @var \WP_Theme_JSON&\PHPUnit\Framework\MockObject\MockObject $theme_json
		 */
		$theme_json = $this->createMock( \WP_Theme_JSON::class );

		$context = new Rendering_Context( $theme_json );

		$this->assertNull( $context->get_user_id() );
		$this->assertNull( $context->get_recipient_email() );
		$this->assertNull( $context->get( 'order_id' ) );
		$this->assertNull( $context->get( 'non_existent_key' ) );
		$this->assertSame( 'default', $context->get( 'non_existent_key', 'default' ) );
		$this->assertSame( array(), $context->get_email_context() );
	}
}
