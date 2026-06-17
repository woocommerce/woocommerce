<?php
/**
 * Reserved attribute names tests.
 *
 * @package WooCommerce\Tests\Internal\ProductAttributes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributes;

use Automattic\WooCommerce\Internal\ProductAttributes\ReservedAttributeNames;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Unit_Test_Case;

/**
 * Tests for the reserved attribute names utility.
 */
class ReservedAttributeNamesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox get_blocked_reserved_names() blocks newly added reserved custom attribute names.
	 */
	public function test_blocks_new_reserved_names(): void {
		$attributes = array(
			$this->create_custom_attribute( 'Variation' ),
			$this->create_custom_attribute( 'Color' ),
		);

		$blocked = ReservedAttributeNames::get_blocked_reserved_names( $attributes );

		$this->assertSame( array( 'Variation' ), $blocked, 'Only the reserved attribute name should be blocked.' );
	}

	/**
	 * @testdox get_blocked_reserved_names() grandfathers reserved names already stored on the product.
	 */
	public function test_grandfathers_existing_reserved_names(): void {
		$existing_product = new WC_Product_Variable();
		$existing_product->set_attributes( array( $this->create_custom_attribute( 'Variation' ) ) );

		$blocked = ReservedAttributeNames::get_blocked_reserved_names(
			array( $this->create_custom_attribute( 'Variation' ) ),
			$existing_product
		);

		$this->assertSame( array(), $blocked, 'A reserved name already on the product should be grandfathered.' );
	}

	/**
	 * @testdox get_blocked_reserved_names() ignores global (taxonomy) attributes.
	 */
	public function test_ignores_taxonomy_attributes(): void {
		$taxonomy_attribute = new WC_Product_Attribute();
		$taxonomy_attribute->set_id( 123 );
		$taxonomy_attribute->set_name( 'pa_variation' );

		$blocked = ReservedAttributeNames::get_blocked_reserved_names( array( $taxonomy_attribute ) );

		$this->assertSame( array(), $blocked, 'Global attributes are namespaced and should never be blocked.' );
	}

	/**
	 * Build a custom (non-taxonomy) product attribute with the given name.
	 *
	 * @param string $name Attribute name.
	 * @return WC_Product_Attribute
	 */
	private function create_custom_attribute( string $name ): WC_Product_Attribute {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( $name );
		$attribute->set_options( array( 'Value' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		return $attribute;
	}
}
