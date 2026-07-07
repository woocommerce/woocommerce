<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductButton;
use ReflectionClass;
use ReflectionMethod;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductButton block type's in-cart text resolution.
 */
class ProductButtonTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test, instantiated without invoking the constructor so
	 * the test bootstrap's existing block registration isn't re-applied. The
	 * methods under test read no injected dependencies.
	 *
	 * @var ProductButton
	 */
	private ProductButton $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$reflection = new ReflectionClass( ProductButton::class );
		$this->sut  = $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Invoke the private `get_in_the_cart_text()` for a product.
	 *
	 * @param \WC_Product $product The product.
	 * @return string The resolved in-the-cart text.
	 */
	private function invoke_get_in_the_cart_text( $product ): string {
		$method = new ReflectionMethod( ProductButton::class, 'get_in_the_cart_text' );
		$method->setAccessible( true );
		return $method->invoke( $this->sut, $product );
	}

	/**
	 * @testdox Grouped products keep the bespoke "Added to cart" label.
	 */
	public function test_grouped_product_uses_added_to_cart_label(): void {
		$child = \WC_Helper_Product::create_simple_product();

		$grouped = new \WC_Product_Grouped();
		$grouped->set_children( array( $child->get_id() ) );
		$grouped->save();

		$this->assertSame(
			'Added to cart',
			$this->invoke_get_in_the_cart_text( $grouped ),
			'Grouped products should show the bespoke "Added to cart" label, not a summed count.'
		);
	}

	/**
	 * @testdox Simple products use the "### in cart" count placeholder.
	 */
	public function test_simple_product_uses_count_placeholder(): void {
		$simple = \WC_Helper_Product::create_simple_product();

		$this->assertSame(
			'### in cart',
			$this->invoke_get_in_the_cart_text( $simple ),
			'Simple products should show the "### in cart" count placeholder.'
		);
	}

	/**
	 * @testdox Variable products use the count placeholder, not the grouped label.
	 */
	public function test_variable_product_uses_count_placeholder(): void {
		// A variable product's children are its variations, NOT grouped
		// children — it must fall through to the count placeholder rather than
		// the grouped "Added to cart" label.
		$variable = \WC_Helper_Product::create_variation_product();

		$this->assertSame(
			'### in cart',
			$this->invoke_get_in_the_cart_text( $variable ),
			'Variable products (whose children are variations) must not use the grouped label.'
		);
	}
}
