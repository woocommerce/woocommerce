<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductButtonMock;

/**
 * Tests for the ProductButton server-side in-cart count seed.
 */
class ProductButtonInCartCountTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductButtonMock
	 */
	private $sut;

	/**
	 * The original product-button block registration.
	 *
	 * @var \WP_Block_Type|null
	 */
	private $original_block_type;

	/**
	 * A simple product ID used across tests.
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$registry                  = \WP_Block_Type_Registry::get_instance();
		$this->original_block_type = null;
		if ( $registry->is_registered( 'woocommerce/product-button' ) ) {
			$this->original_block_type = $registry->get_registered( 'woocommerce/product-button' );
			$registry->unregister( 'woocommerce/product-button' );
		}

		$this->sut = new ProductButtonMock();

		wc_empty_cart();

		$this->product_id = 42;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wc_empty_cart();

		$registry = \WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( 'woocommerce/product-button' ) ) {
			$registry->unregister( 'woocommerce/product-button' );
		}
		if ( $this->original_block_type ) {
			$registry->register( $this->original_block_type );
		}

		parent::tearDown();
	}

	/**
	 * Add a cart item directly to the cart contents.
	 *
	 * @param int   $product_id   The parent product ID.
	 * @param int   $quantity     The line quantity.
	 * @param array $extra_data   Extra cart item data.
	 * @param int   $variation_id The variation ID.
	 * @param array $variation    The variation attributes.
	 */
	private function add_cart_item(
		int $product_id,
		int $quantity,
		array $extra_data = array(),
		int $variation_id = 0,
		array $variation = array()
	): void {
		$key = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation, $extra_data );

		WC()->cart->cart_contents[ $key ] = array(
			'key'          => $key,
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
			'quantity'     => $quantity,
		);
	}

	/**
	 * @testdox Should return zero when the cart is unavailable.
	 */
	public function test_returns_zero_when_cart_is_unavailable(): void {
		$original_cart = WC()->cart;
		WC()->cart     = null; // phpcs:ignore

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $this->product_id );

		WC()->cart = $original_cart; // phpcs:ignore

		$this->assertSame( 0, $result, 'An unavailable cart must yield a count of zero.' );
	}

	/**
	 * @testdox Should return zero for a parent product ID when only one of its variations is in the cart.
	 */
	public function test_returns_zero_when_only_variation_line_exists(): void {
		$this->add_cart_item(
			$this->product_id,
			3,
			array(),
			99,
			array( 'attribute_color' => 'red' )
		);

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $this->product_id );

		$this->assertSame( 0, $result, 'A variation line must not be used as the parent product seed.' );
	}

	/**
	 * @testdox Should return zero when the standalone line is not in the cart.
	 */
	public function test_returns_zero_without_standalone_line(): void {
		$this->add_cart_item( $this->product_id, 5, array( '_bundle' => 'parent-1' ) );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $this->product_id );

		$this->assertSame( 0, $result, 'Meta-differentiated lines must not be used as the initial quantity.' );
	}

	/**
	 * @testdox Should return the standalone line quantity.
	 */
	public function test_returns_standalone_line_quantity(): void {
		$this->add_cart_item( $this->product_id, 2 );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $this->product_id );

		$this->assertSame( 2, $result, 'The standalone line quantity must be used for a simple product.' );
	}

	/**
	 * @testdox Should ignore meta-differentiated lines when a standalone line exists.
	 */
	public function test_ignores_meta_differentiated_lines(): void {
		$this->add_cart_item( $this->product_id, 3 );
		$this->add_cart_item( $this->product_id, 4, array( '_bundle' => 'parent-1' ) );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $this->product_id );

		$this->assertSame( 3, $result, 'Only the standalone line quantity must be used.' );
	}
}
