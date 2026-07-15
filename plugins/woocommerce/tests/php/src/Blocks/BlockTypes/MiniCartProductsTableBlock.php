<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\MiniCartProductsTableBlock as MiniCartProductsTableBlockType;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * Tests for the MiniCartProductsTableBlock block type.
 */
class MiniCartProductsTableBlock extends \WP_UnitTestCase {

	/**
	 * Instance of the block being tested.
	 *
	 * @var MiniCartProductsTableBlockType
	 */
	protected $block;

	/**
	 * The original block type registry entry for the block, if any.
	 *
	 * @var \WP_Block_Type|null
	 */
	protected $original_block_type;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		$registry = \WP_Block_Type_Registry::get_instance();

		$this->original_block_type = null;
		if ( $registry->is_registered( 'woocommerce/mini-cart-products-table-block' ) ) {
			$this->original_block_type = $registry->get_registered( 'woocommerce/mini-cart-products-table-block' );
			$registry->unregister( 'woocommerce/mini-cart-products-table-block' );
		}

		$this->block = new MiniCartProductsTableBlockType(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		);
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		unset( $this->block );

		$registry = \WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( 'woocommerce/mini-cart-products-table-block' ) ) {
			$registry->unregister( 'woocommerce/mini-cart-products-table-block' );
		}
		if ( $this->original_block_type ) {
			$registry->register( $this->original_block_type );
		}

		parent::tearDown();
	}

	/**
	 * Tests that the Mini-Cart quantity stepper renders buttons in visual DOM
	 * order (− input +), so keyboard focus and screen-reader reading order
	 * match the visual layout.
	 *
	 * The stepper markup lives inside an Interactivity API `<template
	 * data-wp-each>` that is expanded client-side per cart item, so the
	 * markup is present verbatim in the server-rendered output regardless of
	 * actual cart contents.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartProductsTableBlock::render
	 */
	public function test_quantity_stepper_renders_in_visual_dom_order(): void {
		$parsed_block = parse_blocks( '<!-- wp:woocommerce/mini-cart-products-table-block /-->' );
		$markup       = render_block( $parsed_block[0] );

		// The minus button must precede the input, which must precede the plus button.
		$this->assertMatchesRegularExpression(
			'/quantity-selector__button--minus.*quantity-selector__input.*quantity-selector__button--plus/s',
			$markup,
			'The Mini-Cart quantity stepper should render buttons in − input + DOM order.'
		);
	}
}
