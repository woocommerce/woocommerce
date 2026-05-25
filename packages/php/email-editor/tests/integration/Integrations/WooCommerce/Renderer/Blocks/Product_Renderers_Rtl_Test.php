<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * Integration tests for WooCommerce product renderer RTL defaults.
 */
class Product_Renderers_Rtl_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Product used in renderer tests.
	 *
	 * @var \WC_Product_Simple|null
	 */
	private $product = null;

	/**
	 * LTR rendering context.
	 *
	 * @var Rendering_Context
	 */
	private Rendering_Context $ltr_context;

	/**
	 * RTL rendering context.
	 *
	 * @var Rendering_Context
	 */
	private Rendering_Context $rtl_context;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();

		$theme_controller  = $this->di_container->get( Theme_Controller::class );
		$this->ltr_context = new Rendering_Context( $theme_controller->get_theme(), array( 'is_rtl' => false ) );
		$this->rtl_context = new Rendering_Context( $theme_controller->get_theme(), array( 'is_rtl' => true ) );

		if ( ! class_exists( '\WC_Product_Simple' ) ) {
			return;
		}

		$this->product = new \WC_Product_Simple();
		$this->product->set_name( 'RTL Test Product' );
		$this->product->set_regular_price( '10' );
		$this->product->set_sale_price( '5' );
		$this->product->set_price( '5' );
		$this->product->save();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		if ( $this->product ) {
			$this->product->delete( true );
		}
		parent::tearDown();
	}

	/**
	 * Test product button defaults to RTL alignment and preserves explicit alignment.
	 */
	public function testProductButtonRtlAlignment(): void {
		$this->skip_if_woocommerce_is_unavailable();

		$renderer = new Product_Button();
		$block    = $this->get_product_block( 'woocommerce/product-button' );

		$ltr                         = $renderer->render( '', $block, $this->ltr_context );
		$rtl                         = $renderer->render( '', $block, $this->rtl_context );
		$block['attrs']['textAlign'] = 'left';
		$explicit_left               = $renderer->render( '', $block, $this->rtl_context );

		$this->assertStringContainsString( 'align="left"', $ltr );
		$this->assertStringContainsString( 'align="right"', $rtl );
		$this->assertStringContainsString( 'align="left"', $explicit_left );
	}

	/**
	 * Test product price defaults to RTL alignment and preserves explicit alignment.
	 */
	public function testProductPriceRtlAlignment(): void {
		$this->skip_if_woocommerce_is_unavailable();

		$renderer = new Product_Price();
		$block    = $this->get_product_block( 'woocommerce/product-price' );

		$ltr                         = $renderer->render( '', $block, $this->ltr_context );
		$rtl                         = $renderer->render( '', $block, $this->rtl_context );
		$block['attrs']['textAlign'] = 'left';
		$explicit_left               = $renderer->render( '', $block, $this->rtl_context );

		$this->assertStringContainsString( 'text-align:left', $ltr );
		$this->assertStringContainsString( 'text-align:right', $rtl );
		$this->assertStringContainsString( 'text-align:left', $explicit_left );
	}

	/**
	 * Test product sale badge defaults to RTL alignment and preserves explicit alignment.
	 */
	public function testProductSaleBadgeRtlAlignment(): void {
		$this->skip_if_woocommerce_is_unavailable();

		$renderer = new Product_Sale_Badge();
		$block    = $this->get_product_block( 'woocommerce/product-sale-badge' );

		$ltr                     = $renderer->render( '', $block, $this->ltr_context );
		$rtl                     = $renderer->render( '', $block, $this->rtl_context );
		$block['attrs']['align'] = 'left';
		$explicit_left           = $renderer->render( '', $block, $this->rtl_context );

		$this->assertStringContainsString( 'text-align:left', $ltr );
		$this->assertStringContainsString( 'text-align:right', $rtl );
		$this->assertStringContainsString( 'text-align:left', $explicit_left );
	}

	/**
	 * Test product image defaults to RTL alignment and preserves explicit alignment.
	 */
	public function testProductImageRtlAlignment(): void {
		$this->skip_if_woocommerce_is_unavailable();

		$renderer = new Product_Image();
		$block    = $this->get_product_block( 'woocommerce/product-image' );

		$ltr                     = $renderer->render( '', $block, $this->ltr_context );
		$rtl                     = $renderer->render( '', $block, $this->rtl_context );
		$block['attrs']['align'] = 'left';
		$explicit_left           = $renderer->render( '', $block, $this->rtl_context );

		$this->assertStringContainsString( 'align="left"', $ltr );
		$this->assertStringContainsString( 'align="right"', $rtl );
		$this->assertStringContainsString( 'align="left"', $explicit_left );
	}

	/**
	 * Test product collection two-column gaps use direction-aware physical sides.
	 */
	public function testProductCollectionRtlColumnGapSide(): void {
		$renderer = new Product_Collection();
		$method   = new \ReflectionMethod( $renderer, 'render_two_column_grid' );
		$method->setAccessible( true );
		$template_block = array( 'innerBlocks' => array() );

		$ltr = $method->invoke( $renderer, array( null, null ), $template_block, 'test', $this->ltr_context );
		$rtl = $method->invoke( $renderer, array( null, null ), $template_block, 'test', $this->rtl_context );
		$this->assertIsString( $ltr );
		$this->assertIsString( $rtl );

		$ltr_cell_styles = $this->get_first_two_cell_styles( $ltr );
		$rtl_cell_styles = $this->get_first_two_cell_styles( $rtl );

		$this->assertStringContainsString( 'padding-right: 10px', $ltr_cell_styles[0] );
		$this->assertStringNotContainsString( 'padding-left: 10px', $ltr_cell_styles[0] );
		$this->assertStringContainsString( 'padding-left: 10px', $ltr_cell_styles[1] );
		$this->assertStringNotContainsString( 'padding-right: 10px', $ltr_cell_styles[1] );
		$this->assertStringContainsString( 'padding-left: 10px', $rtl_cell_styles[0] );
		$this->assertStringNotContainsString( 'padding-right: 10px', $rtl_cell_styles[0] );
		$this->assertStringContainsString( 'padding-right: 10px', $rtl_cell_styles[1] );
		$this->assertStringNotContainsString( 'padding-left: 10px', $rtl_cell_styles[1] );
	}

	/**
	 * Test product collection outer spacers use RTL alignment.
	 */
	public function testProductCollectionRtlOuterSpacerAlignment(): void {
		$renderer = new Product_Collection();
		$method   = new \ReflectionMethod( $renderer, 'render_product_grid' );
		$method->setAccessible( true );
		$template_block = array(
			'email_attrs' => array(),
			'innerBlocks' => array(),
		);

		$single_column = $method->invoke( $renderer, array( null ), $template_block, 'test', 1, $this->rtl_context );
		$two_column    = $method->invoke( $renderer, array( null, null ), $template_block, 'test', 2, $this->rtl_context );
		$this->assertIsString( $single_column );
		$this->assertIsString( $two_column );

		$this->assert_outer_spacer_alignment( $single_column );
		$this->assert_outer_spacer_alignment( $two_column );
	}

	/**
	 * Assert the outer spacer table uses RTL alignment.
	 *
	 * @param string $rendered Rendered HTML.
	 */
	private function assert_outer_spacer_alignment( string $rendered ): void {
		$right_aligned_table_position = strpos( $rendered, 'align="right"' );
		$layout_class_position        = strpos( $rendered, 'email-block-layout' );

		$this->assertNotFalse( $right_aligned_table_position );
		$this->assertNotFalse( $layout_class_position );
		$this->assertGreaterThan( $right_aligned_table_position, $layout_class_position );
	}

	/**
	 * Get parsed product block.
	 *
	 * @param string $block_name Block name.
	 * @return array
	 * @throws \RuntimeException When the product fixture is unavailable.
	 */
	private function get_product_block( string $block_name ): array {
		if ( ! $this->product instanceof \WC_Product_Simple ) {
			throw new \RuntimeException( 'Product fixture is unavailable.' );
		}

		return array(
			'blockName'   => $block_name,
			'attrs'       => array(),
			'context'     => array(
				'postId' => $this->product->get_id(),
			),
			'email_attrs' => array(),
			'innerBlocks' => array(),
		);
	}

	/**
	 * Get the first two TD style attributes from rendered grid HTML.
	 *
	 * @param string $html Rendered HTML.
	 * @return string[]
	 */
	private function get_first_two_cell_styles( string $html ): array {
		preg_match_all( '/<td style="([^"]*)"/', $html, $matches );
		$this->assertGreaterThanOrEqual( 2, count( $matches[1] ) );
		return array_slice( $matches[1], 0, 2 );
	}

	/**
	 * Skip product-specific renderer tests when WooCommerce is not loaded.
	 */
	private function skip_if_woocommerce_is_unavailable(): void {
		if ( ! $this->product ) {
			$this->markTestSkipped( 'WooCommerce product classes are not loaded in this package test environment.' );
		}
	}
}
