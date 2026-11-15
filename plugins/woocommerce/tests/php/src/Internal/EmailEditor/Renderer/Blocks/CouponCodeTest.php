<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Coupon_Code;
use WC_Unit_Test_Case;

/**
 * Tests for the Coupon_Code email block renderer.
 */
class CouponCodeTest extends WC_Unit_Test_Case {

	/**
	 * Saved block markup with the placeholder the editor stores.
	 */
	private const BLOCK_CONTENT = '<div class="coupon">' . Coupon_Code::COUPON_CODE_PLACEHOLDER . '</div>';

	/**
	 * The System Under Test.
	 *
	 * @var Coupon_Code
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Coupon_Code();
	}

	/**
	 * @testdox Puts the generated code, escaped, in place of the placeholder.
	 */
	public function test_generated_code_replaces_placeholder(): void {
		$this->generate_code( 'SAVE<10>' );

		$html = $this->render( array() );

		$this->assertStringContainsString( '<div class="coupon">SAVE&lt;10&gt;</div>', $html );
		$this->assertStringNotContainsString( Coupon_Code::COUPON_CODE_PLACEHOLDER, $html );
	}

	/**
	 * @testdox Renders no coupon when a new code could not be generated.
	 */
	public function test_no_generated_code_renders_no_coupon(): void {
		$this->generate_code( '' );

		$this->assertStringNotContainsString( 'class="coupon"', $this->render( array() ) );
	}

	/**
	 * @testdox Keeps an existing coupon code without generating one.
	 */
	public function test_existing_coupon_is_kept(): void {
		$generated = false;
		add_filter(
			'woocommerce_coupon_code_block_auto_generate',
			function ( $code ) use ( &$generated ) {
				$generated = true;
				return $code;
			}
		);

		$html = $this->sut->render( '<div class="coupon">SUMMER</div>', $this->parsed_block( array( 'source' => 'existing' ) ), $this->context() );

		$this->assertStringContainsString( '<div class="coupon">SUMMER</div>', $html );
		$this->assertFalse( $generated, 'No coupon should be generated for an existing coupon.' );
	}

	/**
	 * @testdox Puts a coupon from WooCommerce's generator into a sent email.
	 */
	public function test_woocommerce_generator_fills_the_code(): void {
		// Starts the email editor as WooCommerce does when the block email editor feature is on.
		Email_Editor_Container::container()->get( Bootstrap::class )->init();

		$html = $this->render( array() );

		$this->assertMatchesRegularExpression( '/<div class="coupon">[a-z0-9]{4}-[a-z0-9]{6}-[a-z0-9]{4}<\/div>/i', $html );
	}

	/**
	 * @testdox Renders no coupon when a filter returns something other than a code.
	 * @testWith [["not", "a", "code"]]
	 *           [0]
	 *           ["0"]
	 *
	 * @param mixed $code Value the filter returns.
	 */
	public function test_non_code_value_renders_no_coupon( $code ): void {
		$this->generate_code( $code );

		$this->assertStringNotContainsString( 'class="coupon"', $this->render( array() ) );
	}

	/**
	 * @testdox Aligns the coupon as set, and centers it for any other value.
	 * @testWith ["left", "left"]
	 *           ["right", "right"]
	 *           ["justify", "center"]
	 *
	 * @param string $align    Block alignment.
	 * @param string $expected Alignment used in the email.
	 */
	public function test_alignment( string $align, string $expected ): void {
		$this->generate_code( 'CODE' );

		$this->assertStringContainsString( 'align="' . $expected . '" style="text-align:' . $expected . ';"', $this->render( array( 'align' => $align ) ) );
	}

	/**
	 * Makes code generation return a fixed value.
	 *
	 * @param mixed $code Generated code.
	 */
	private function generate_code( $code ): void {
		remove_all_filters( 'woocommerce_coupon_code_block_auto_generate' );
		add_filter(
			'woocommerce_coupon_code_block_auto_generate',
			function () use ( $code ) {
				return $code;
			}
		);
	}

	/**
	 * Renders a block that creates a new coupon.
	 *
	 * @param array $attrs Block attributes.
	 */
	private function render( array $attrs ): string {
		return $this->sut->render( self::BLOCK_CONTENT, $this->parsed_block( $attrs ), $this->context() );
	}

	/**
	 * Parsed coupon code block.
	 *
	 * @param array $attrs Block attributes.
	 */
	private function parsed_block( array $attrs ): array {
		return array(
			'blockName' => 'woocommerce/coupon-code',
			'attrs'     => $attrs,
		);
	}

	/**
	 * Rendering context for a sent email.
	 */
	private function context(): Rendering_Context {
		return new Rendering_Context( new \WP_Theme_JSON() );
	}
}
