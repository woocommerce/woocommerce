<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Mocks\CouponCodeMock;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;

/**
 * Tests for the CouponCode block type.
 */
class CouponCodeTest extends \WP_UnitTestCase {

	/**
	 * Mock instance of the CouponCode block.
	 *
	 * @var CouponCodeMock
	 */
	private CouponCodeMock $mock;

	/**
	 * Rendering context for tests.
	 *
	 * @var Rendering_Context
	 */
	private Rendering_Context $rendering_context;

	/**
	 * The original block type registry entry for the CouponCode block.
	 *
	 * @var \WP_Block_Type|null
	 */
	private $original_block_type;

	/**
	 * Setup test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$registry = \WP_Block_Type_Registry::get_instance();

		$this->original_block_type = null;
		if ( $registry->is_registered( 'woocommerce/coupon-code' ) ) {
			$this->original_block_type = $registry->get_registered( 'woocommerce/coupon-code' );
			$registry->unregister( 'woocommerce/coupon-code' );
		}

		$this->mock = new CouponCodeMock();

		$theme_controller        = Email_Editor_Container::container()->get( Theme_Controller::class );
		$this->rendering_context = new Rendering_Context( $theme_controller->get_theme(), array() );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$registry = \WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( 'woocommerce/coupon-code' ) ) {
			$registry->unregister( 'woocommerce/coupon-code' );
		}

		if ( $this->original_block_type ) {
			$registry->register( $this->original_block_type );
		}

		parent::tearDown();
	}

	/**
	 * Test that render returns empty string when no coupon code is provided.
	 */
	public function test_render_returns_empty_when_no_coupon_code(): void {
		$result = $this->mock->call_render( array() );
		$this->assertSame( '', $result );

		$result = $this->mock->call_render( array( 'couponCode' => '' ) );
		$this->assertSame( '', $result );
	}

	/**
	 * Test that render returns HTML when coupon code is provided.
	 */
	public function test_render_returns_html_with_coupon_code(): void {
		$result = $this->mock->call_render( array( 'couponCode' => 'TESTCODE' ) );

		$this->assertStringContainsString( 'TESTCODE', $result );
		$this->assertStringContainsString( 'woocommerce-coupon-code', $result );
		$this->assertStringContainsString( '<table', $result );
	}

	/**
	 * Test that coupon code is properly escaped in output.
	 */
	public function test_render_escapes_coupon_code(): void {
		$result = $this->mock->call_render( array( 'couponCode' => '<script>alert("xss")</script>' ) );

		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringContainsString( '&lt;script&gt;', $result );
	}

	/**
	 * Test build_coupon_html applies default styles.
	 */
	public function test_build_coupon_html_applies_default_styles(): void {
		$result = $this->mock->call_build_coupon_html(
			'TESTCODE',
			array(),
			$this->rendering_context
		);

		$this->assertStringContainsString( 'font-size', $result );
		$this->assertStringContainsString( 'padding', $result );
		$this->assertStringContainsString( 'border', $result );
		$this->assertStringContainsString( 'background-color', $result );
	}

	/**
	 * Test build_coupon_html uses default background color when none specified.
	 */
	public function test_build_coupon_html_uses_default_background_color(): void {
		$result = $this->mock->call_build_coupon_html(
			'TESTCODE',
			array(),
			$this->rendering_context
		);

		$this->assertStringContainsString( 'background-color:#f5f5f5', $result );
	}

	/**
	 * Test build_coupon_html applies custom border styles.
	 */
	public function test_build_coupon_html_applies_custom_border_styles(): void {
		$attributes = array(
			'couponCode' => 'TESTCODE',
			'style'      => array(
				'border' => array(
					'width'  => '3px',
					'color'  => '#ff0000',
					'radius' => '10px',
				),
			),
		);

		$result = $this->mock->call_build_coupon_html(
			'TESTCODE',
			$attributes,
			$this->rendering_context
		);

		$this->assertStringContainsString( '3px', $result );
		$this->assertStringContainsString( '#ff0000', $result );
		$this->assertStringContainsString( '10px', $result );
	}

	/**
	 * Test is_css_color_value correctly identifies hex colors.
	 */
	public function test_is_css_color_value_identifies_hex_colors(): void {
		$this->assertTrue( $this->mock->call_is_css_color_value( '#fff' ) );
		$this->assertTrue( $this->mock->call_is_css_color_value( '#ffffff' ) );
		$this->assertTrue( $this->mock->call_is_css_color_value( '#FF00AA' ) );
	}

	/**
	 * Test is_css_color_value correctly identifies rgb colors.
	 */
	public function test_is_css_color_value_identifies_rgb_colors(): void {
		$this->assertTrue( $this->mock->call_is_css_color_value( 'rgb(255, 0, 0)' ) );
		$this->assertTrue( $this->mock->call_is_css_color_value( 'rgba(255, 0, 0, 0.5)' ) );
	}

	/**
	 * Test is_css_color_value correctly identifies hsl colors.
	 */
	public function test_is_css_color_value_identifies_hsl_colors(): void {
		$this->assertTrue( $this->mock->call_is_css_color_value( 'hsl(120, 100%, 50%)' ) );
		$this->assertTrue( $this->mock->call_is_css_color_value( 'hsla(120, 100%, 50%, 0.5)' ) );
	}

	/**
	 * Test is_css_color_value rejects color slugs.
	 */
	public function test_is_css_color_value_rejects_color_slugs(): void {
		$this->assertFalse( $this->mock->call_is_css_color_value( 'accent-5' ) );
		$this->assertFalse( $this->mock->call_is_css_color_value( 'primary' ) );
		$this->assertFalse( $this->mock->call_is_css_color_value( 'vivid-red' ) );
	}

	/**
	 * Test get_alignment returns center by default.
	 */
	public function test_get_alignment_returns_center_by_default(): void {
		$result = $this->mock->call_get_alignment( array() );
		$this->assertSame( 'center', $result );
	}

	/**
	 * Test get_alignment returns valid alignment values.
	 */
	public function test_get_alignment_returns_valid_alignments(): void {
		$this->assertSame( 'left', $this->mock->call_get_alignment( array( 'attrs' => array( 'align' => 'left' ) ) ) );
		$this->assertSame( 'center', $this->mock->call_get_alignment( array( 'attrs' => array( 'align' => 'center' ) ) ) );
		$this->assertSame( 'right', $this->mock->call_get_alignment( array( 'attrs' => array( 'align' => 'right' ) ) ) );
	}

	/**
	 * Test get_alignment falls back to center for invalid values.
	 */
	public function test_get_alignment_falls_back_for_invalid_values(): void {
		$this->assertSame( 'center', $this->mock->call_get_alignment( array( 'attrs' => array( 'align' => 'invalid' ) ) ) );
		$this->assertSame( 'center', $this->mock->call_get_alignment( array( 'attrs' => array( 'align' => 'full' ) ) ) );
		$this->assertSame( 'center', $this->mock->call_get_alignment( array( 'attrs' => array( 'align' => 123 ) ) ) );
	}

	/**
	 * Test render output contains proper table structure for email compatibility.
	 */
	public function test_render_contains_email_table_structure(): void {
		$result = $this->mock->call_render( array( 'couponCode' => 'TESTCODE' ) );

		$this->assertStringContainsString( '<table', $result );
		$this->assertStringContainsString( '</table>', $result );
		$this->assertStringContainsString( '<td', $result );
		$this->assertStringContainsString( 'email-coupon-code-cell', $result );
	}

	/**
	 * Test that non-string coupon code values are handled.
	 */
	public function test_render_handles_non_string_coupon_code(): void {
		$result = $this->mock->call_render( array( 'couponCode' => 12345 ) );
		$this->assertSame( '', $result );

		$result = $this->mock->call_render( array( 'couponCode' => array( 'code' ) ) );
		$this->assertSame( '', $result );
	}

	/**
	 * Test build_coupon_html applies hex text color.
	 */
	public function test_build_coupon_html_applies_hex_text_color(): void {
		$attributes = array(
			'style' => array(
				'color' => array(
					'text' => '#ff0000',
				),
			),
		);

		$result = $this->mock->call_build_coupon_html(
			'TESTCODE',
			$attributes,
			$this->rendering_context
		);

		$this->assertStringContainsString( '#ff0000', $result );
	}

	/**
	 * Test build_coupon_html includes coupon code text.
	 */
	public function test_build_coupon_html_includes_coupon_code(): void {
		$result = $this->mock->call_build_coupon_html(
			'MY-DISCOUNT-50',
			array(),
			$this->rendering_context
		);

		$this->assertStringContainsString( 'MY-DISCOUNT-50', $result );
	}

	/**
	 * Test that the span element has the correct class.
	 */
	public function test_build_coupon_html_has_correct_class(): void {
		$result = $this->mock->call_build_coupon_html(
			'TESTCODE',
			array(),
			$this->rendering_context
		);

		$this->assertStringContainsString( 'class="woocommerce-coupon-code"', $result );
	}

	/**
	 * Test default styles include font-weight bold.
	 */
	public function test_build_coupon_html_has_bold_font_weight(): void {
		$result = $this->mock->call_build_coupon_html(
			'TESTCODE',
			array(),
			$this->rendering_context
		);

		$this->assertStringContainsString( 'font-weight:bold', $result );
	}
}
