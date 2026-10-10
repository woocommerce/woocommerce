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

	/**
	 * A sale price outside its scheduled window is not the effective price, so
	 * advertising it as a list price would render a strikethrough equal to the price.
	 *
	 * @testdox A simple product whose sale window has passed should carry no list price.
	 */
	public function test_expired_sale_yields_no_list_price(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price'     => '72.22',
				'sale_price'        => '45.00',
				'date_on_sale_from' => time() - 20 * DAY_IN_SECONDS,
				'date_on_sale_to'   => time() - 10 * DAY_IN_SECONDS,
			)
		);

		$mapped = ( new UcpProductMapper( 'USD' ) )->map_product( $product );

		$this->assertSame( 7222, $mapped['price_range']['min']['amount'] );
		$this->assertArrayNotHasKey( 'list_price_range', $mapped );
		$this->assertArrayNotHasKey( 'list_price', $mapped['variants'][0] );
	}

	/**
	 * `get_variation_prices()` rewrites `sale_price` to the regular price when a sale
	 * is not active, so only the effective `price` distinguishes a real discount.
	 *
	 * @testdox A variable product should carry list prices only for variations on sale now.
	 */
	public function test_variable_product_list_prices_follow_active_sales_only(): void {
		$variable = new \WC_Product_Variable();
		$variable->set_name( 'Variable Sale Windows' );
		$variable->set_status( 'publish' );
		$variable->set_attributes( array( WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'medium', 'large' ) ) ) );
		$parent_id = $variable->save();

		// Active sale, sale that has expired, and sale that has not started.
		$windows = array(
			'small'  => array( '100', '80', null, null ),
			'medium' => array( '200', '150', time() - 20 * DAY_IN_SECONDS, time() - 10 * DAY_IN_SECONDS ),
			'large'  => array( '300', '250', time() + 10 * DAY_IN_SECONDS, time() + 20 * DAY_IN_SECONDS ),
		);

		foreach ( $windows as $size => $window ) {
			$variation = new \WC_Product_Variation();
			$variation->set_parent_id( $parent_id );
			$variation->set_attributes( array( 'pa_size' => $size ) );
			$variation->set_regular_price( $window[0] );
			$variation->set_sale_price( $window[1] );
			$variation->set_date_on_sale_from( $window[2] );
			$variation->set_date_on_sale_to( $window[3] );
			$variation->set_status( 'publish' );
			$variation->save();
		}

		\WC_Product_Variable::sync( $parent_id );
		wc_delete_product_transients( $parent_id );

		$mapped = ( new UcpProductMapper( 'USD' ) )->map_product( wc_get_product( $parent_id ) );

		$this->assertSame( 8000, $mapped['price_range']['min']['amount'] );
		$this->assertSame( 30000, $mapped['price_range']['max']['amount'] );

		// The range spans every regular price, because one variation is genuinely discounted.
		$this->assertSame( 10000, $mapped['list_price_range']['min']['amount'] );
		$this->assertSame( 30000, $mapped['list_price_range']['max']['amount'] );

		$list_prices = array();
		foreach ( $mapped['variants'] as $variant ) {
			$list_prices[ $variant['price']['amount'] ] = $variant['list_price']['amount'] ?? null;
		}

		$this->assertSame( 10000, $list_prices[8000] );
		$this->assertNull( $list_prices[20000] );
		$this->assertNull( $list_prices[30000] );
	}

	/**
	 * `description.plain` is declared plain text, so entities left in it would
	 * reach a shopper literally as `&nbsp;` or `&amp;`.
	 *
	 * @testdox A plain description should be decoded and free of HTML entities.
	 */
	public function test_description_decodes_html_entities(): void {
		$product = WC_Helper_Product::create_simple_product( false );
		$product->set_short_description( 'Rich &amp; smooth &mdash; 100&nbsp;% arabica <strong>bold</strong>' );
		$product->save();

		$mapped = ( new UcpProductMapper( 'USD' ) )->map_product( $product );

		$this->assertSame( "Rich & smooth \u{2014} 100 % arabica bold", $mapped['description']['plain'] );
		$this->assertStringNotContainsString( "\u{A0}", $mapped['description']['plain'] );
	}

	/**
	 * @testdox A description of only non-breaking spaces should map to an empty string.
	 */
	public function test_description_of_entities_only_is_trimmed_to_empty(): void {
		$product = WC_Helper_Product::create_simple_product( false );
		$product->set_short_description( "&nbsp;\r\n\r\n&nbsp;" );
		$product->save();

		$mapped = ( new UcpProductMapper( 'USD' ) )->map_product( $product );

		$this->assertSame( '', $mapped['description']['plain'] );
	}
}
