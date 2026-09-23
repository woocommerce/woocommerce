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
	 * Create a theme JSON mock.
	 *
	 * @return \WP_Theme_JSON
	 */
	private function create_theme_json(): \WP_Theme_JSON {
		$theme_json = $this->createMock( \WP_Theme_JSON::class );
		$theme_json->method( 'get_data' )->willReturn( array( 'styles' => array() ) );
		$theme_json->method( 'get_settings' )->willReturn(
			array(
				'color' => array(
					'palette' => array(
						'theme'   => array(),
						'default' => array(),
					),
				),
			)
		);
		return $theme_json;
	}

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

	/**
	 * Test it resolves explicit RTL context before language fallback.
	 */
	public function testItResolvesExplicitRtlContextBeforeLanguage(): void {
		$theme_json = $this->create_theme_json();

		$ltr_context = new Rendering_Context( $theme_json, array( 'is_rtl' => false ), 'ar_SA' );
		$rtl_context = new Rendering_Context( $theme_json, array( 'is_rtl' => true ), 'en_US' );

		$this->assertFalse( $ltr_context->is_rtl() );
		$this->assertSame( 'ltr', $ltr_context->get_text_direction() );
		$this->assertSame( 'left', $ltr_context->get_default_text_align() );
		$this->assertTrue( $rtl_context->is_rtl() );
		$this->assertSame( 'rtl', $rtl_context->get_text_direction() );
		$this->assertSame( 'right', $rtl_context->get_default_text_align() );
	}

	/**
	 * Test it resolves direction from language when explicit context is absent.
	 */
	public function testItResolvesDirectionFromLanguage(): void {
		$theme_json = $this->create_theme_json();

		$this->assertTrue( ( new Rendering_Context( $theme_json, array(), 'he_IL' ) )->is_rtl() );
		$this->assertTrue( ( new Rendering_Context( $theme_json, array(), 'fa-IR' ) )->is_rtl() );
		$this->assertTrue( ( new Rendering_Context( $theme_json, array(), 'ckb_IR' ) )->is_rtl() );
		$this->assertFalse( ( new Rendering_Context( $theme_json, array(), 'en_US' ) )->is_rtl() );
		$this->assertFalse( ( new Rendering_Context( $theme_json, array(), 'unknown' ) )->is_rtl() );
	}

	/**
	 * Test it ignores malformed RTL context values.
	 */
	public function testItIgnoresMalformedRtlContextValues(): void {
		$context = new Rendering_Context( $this->create_theme_json(), array( 'is_rtl' => 'true' ), 'ar' );

		$this->assertTrue( $context->is_rtl() );
		$this->assertSame( 'right', $context->get_start_side() );
		$this->assertSame( 'left', $context->get_end_side() );
	}

	/**
	 * Test it sanitizes and resolves text alignment values.
	 */
	public function testItSanitizesAndResolvesTextAlignment(): void {
		$context = new Rendering_Context( $this->create_theme_json(), array( 'is_rtl' => true ) );

		$this->assertSame( 'left', $context->sanitize_text_align( 'left' ) );
		$this->assertSame( 'center', $context->sanitize_text_align( 'center' ) );
		$this->assertSame( 'right', $context->sanitize_text_align( 'right' ) );
		$this->assertNull( $context->sanitize_text_align( 'start' ) );
		$this->assertNull( $context->sanitize_text_align( '<script>' ) );
		$this->assertSame( 'right', $context->resolve_text_align( null ) );
		$this->assertSame( 'center', $context->resolve_text_align( 'center' ) );
	}
}
