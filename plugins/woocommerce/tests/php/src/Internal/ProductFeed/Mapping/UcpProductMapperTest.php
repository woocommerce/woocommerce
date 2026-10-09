<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Mapping;

use Automattic\WooCommerce\Internal\ProductFeed\Mapping\ProductShapeMapperInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Mapping\UcpProductMapper;
use WC_Helper_Product;

/**
 * Tests for the UCP product shape mapper.
 */
class UcpProductMapperTest extends \WC_Unit_Test_Case {
	/**
	 * @testdox The mapper should be consumable through the delivery-agnostic shape contract.
	 */
	public function test_is_a_product_shape_mapper(): void {
		$this->assertInstanceOf( ProductShapeMapperInterface::class, new UcpProductMapper() );
	}

	/**
	 * @testdox Mapping a simple product should express every amount as integer minor units.
	 */
	public function test_maps_amounts_to_integer_minor_units(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '19.99',
				'sale_price'    => '14.50',
				'price'         => '14.50',
			)
		);

		$mapped = ( new UcpProductMapper( 'USD' ) )->map_product( $product );

		$this->assertSame( 1450, $mapped['price_range']['min']['amount'] );
		$this->assertSame( 'USD', $mapped['price_range']['min']['currency'] );
		$this->assertSame( 1999, $mapped['list_price_range']['max']['amount'] );
		$this->assertSame( 1450, $mapped['variants'][0]['price']['amount'] );
	}

	/**
	 * A variable product with no enabled variation cannot satisfy the catalog schema,
	 * which requires at least one variant, so it must be reported as ineligible.
	 *
	 * @testdox A variable product with no enabled variation should not be catalog-eligible.
	 */
	public function test_variable_product_without_enabled_variations_is_ineligible(): void {
		$mapper   = new UcpProductMapper( 'USD' );
		$variable = WC_Helper_Product::create_variation_product();

		$this->assertTrue( $mapper->has_catalog_variants( $variable ) );

		// Disabling a variation sets it private; saving through the CRUD layer
		// invalidates the parent's cached children list.
		foreach ( $variable->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			$variation->set_status( 'private' );
			$variation->save();
		}

		$variable = wc_get_product( $variable->get_id() );

		$this->assertFalse( $mapper->has_catalog_variants( $variable ) );
		$this->assertSame( array(), $mapper->map_products( array( $variable ) ) );
	}
}
