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
	 * @var \WC_Product_Simple
	 */
	private \WC_Product_Simple $product;

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

		$this->product = new \WC_Product_Simple();
		$this->product->set_name( 'RTL Test Product' );
		$this->product->set_regular_price( '10' );
		$this->product->set_sale_price( '5' );
		$this->product->set_price( '5' );
		$this->product->save();

		$theme_controller  = $this->di_container->get( Theme_Controller::class );
		$this->ltr_context = new Rendering_Context( $theme_controller->get_theme(), array( 'is_rtl' => false ) );
		$this->rtl_context = new Rendering_Context( $theme_controller->get_theme(), array( 'is_rtl' => true ) );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$this->product->delete( true );
		parent::tearDown();
	}

	/**
	 * Test product button defaults to RTL alignment and preserves explicit alignment.
	 */
	public function testProductButtonRtlAlignment(): void {
		$renderer = new Product_Button();
		$block    = $this->get_product_block( 'woocommerce/product-button' );

		$ltr = $renderer->render( '', $block, $this->ltr_context );
		$rtl = $renderer->render( '', $block, $this->rtl_context );
		$block['attrs']['textAlign'] = 'left';
		$explicit_left = $renderer->render( '', $block, $this->rtl_context );

		$this->assertStringContainsString( 'align="left"', $ltr );
		$this->assertStringContainsString( 'align="right"', $rtl );
		$this->assertStringContainsString( 'align="left"', $explicit_left );
	}

	/**
	 * Test product price defaults to RTL alignment and preserves explicit alignment.
	 */
	public function testProductPriceRtlAlignment(): void {
		$renderer = new Product_Price();
		$block    = $this->get_product_block( 'woocommerce/product-price' );

		$ltr = $renderer->render( '', $block, $this->ltr_context );
		$rtl = $renderer->render( '', $block, $this->rtl_context );
		$block['attrs']['textAlign'] = 'left';
		$explicit_left = $renderer->render( '', $block, $this->rtl_context );

		$this->assertStringContainsString( 'text-align:left', $ltr );
		$this->assertStringContainsString( 'text-align:right', $rtl );
		$this->assertStringContainsString( 'text-align:left', $explicit_left );
	}

	/**
	 * Test product sale badge defaults to RTL alignment and preserves explicit alignment.
	 */
	public function testProductSaleBadgeRtlAlignment(): void {
		$renderer = new Product_Sale_Badge();
		$block    = $this->get_product_block( 'woocommerce/product-sale-badge' );

		$ltr = $renderer->render( '', $block, $this->ltr_context );
		$rtl = $renderer->render( '', $block, $this->rtl_context );
		$block['attrs']['align'] = 'left';
		$explicit_left = $renderer->render( '', $block, $this->rtl_context );

		$this->assertStringContainsString( 'text-align:left', $ltr );
		$this->assertStringContainsString( 'text-align:right', $rtl );
		$this->assertStringContainsString( 'text-align:left', $explicit_left );
	}

	/**
	 * Test product image defaults to RTL alignment and preserves explicit alignment.
	 */
	public function testProductImageRtlAlignment(): void {
		$renderer = new Product_Image();
		$block    = $this->get_product_block( 'woocommerce/product-image' );

		$ltr = $renderer->render( '', $block, $this->ltr_context );
		$rtl = $renderer->render( '', $block, $this->rtl_context );
		$block['attrs']['align'] = 'left';
		$explicit_left = $renderer->render( '', $block, $this->rtl_context );

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

		$ltr = $method->invoke( $renderer, array( $this->product, $this->product ), $template_block, 'test', $this->ltr_context );
		$rtl = $method->invoke( $renderer, array( $this->product, $this->product ), $template_block, 'test', $this->rtl_context );

		$this->assertStringContainsString( 'padding-right: 10px', $ltr );
		$this->assertStringContainsString( 'padding-left: 10px', $rtl );
	}

	/**
	 * Get parsed product block.
	 *
	 * @param string $block_name Block name.
	 * @return array
	 */
	private function get_product_block( string $block_name ): array {
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
}
