<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;

/**
 * Tests for the MiniCartProductsTableBlock block type.
 */
class MiniCartProductsTableBlockTest extends WC_Unit_Test_Case {

	/**
	 * The Interactivity API namespace this block registers its state under.
	 *
	 * @var string
	 */
	private $block_namespace = 'woocommerce/mini-cart-products-table-block';

	/**
	 * Clear the global WP_Interactivity_API context stack so tests do not
	 * bleed state into each other. WordPress core does not expose a public
	 * reset helper, so we reach in via reflection.
	 */
	public function tearDown(): void {
		$api = wp_interactivity();

		if ( is_object( $api ) ) {
			$reflection = new \ReflectionClass( $api );
			if ( $reflection->hasProperty( 'context_stack' ) ) {
				$property = $reflection->getProperty( 'context_stack' );
				$property->setAccessible( true );
				$property->setValue( $api, null );
			}
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should keep the data-wp-each row as its own context, keyed by the cart line.
	 */
	public function test_render_keeps_the_each_row_context(): void {
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart-products-table-block /-->' );
		$markup = render_block( $block[0] );

		$this->assertStringContainsString( 'data-wp-each--cart-item="woocommerce::state.cart.items"', $markup, 'Each row should keep the cart item as its own context.' );
		$this->assertStringContainsString( 'data-wp-each-key="state.cartItem.key"', $markup, 'Rows should be keyed by the cart line key.' );
	}

	/**
	 * @testdox Should expose a cartItemKey getter that resolves the current row's cart line key.
	 */
	public function test_registers_a_cart_item_key_getter(): void {
		$block = parse_blocks( '<!-- wp:woocommerce/mini-cart-products-table-block /-->' );
		render_block( $block[0] );

		$this->push_woocommerce_context( array( 'cartItem' => array( 'key' => 'line-1' ) ) );

		$state = wp_interactivity_state( $this->block_namespace );

		$this->assertArrayHasKey( 'cartItemKey', $state, 'The block should register a cartItemKey getter.' );
		$this->assertSame( 'line-1', $state['cartItemKey'](), 'The getter should resolve the current row\'s cart item key.' );
	}

	/**
	 * @testdox Should resolve the cartItemKey getter to null when the row declares no cart item.
	 */
	public function test_cart_item_key_getter_resolves_to_null_without_a_row(): void {
		$block = parse_blocks( '<!-- wp:woocommerce/mini-cart-products-table-block /-->' );
		render_block( $block[0] );

		$this->push_woocommerce_context( array() );

		$state = wp_interactivity_state( $this->block_namespace );

		$this->assertNull( $state['cartItemKey'](), 'The getter should resolve to null when no cart item is in context.' );
	}

	/**
	 * Push a `woocommerce` context frame onto the global interactivity API
	 * instance, simulating a row rendering with the given declared context.
	 *
	 * @param array $context The `woocommerce` context to simulate.
	 */
	private function push_woocommerce_context( array $context ): void {
		$api        = wp_interactivity();
		$reflection = new \ReflectionClass( $api );
		$property   = $reflection->getProperty( 'context_stack' );
		$property->setAccessible( true );
		$property->setValue( $api, array( array( 'woocommerce' => $context ) ) );
	}
}
