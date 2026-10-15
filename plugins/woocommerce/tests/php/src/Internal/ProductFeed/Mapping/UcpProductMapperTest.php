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

	/**
	 * UCP requires `selected` to describe the variants returned, so an
	 * unmatched selection is relaxed from its last option rather than echoed.
	 *
	 * @testdox A selection matching no variation should be relaxed and reported as relaxed.
	 */
	public function test_detail_selection_is_relaxed_until_a_variation_matches(): void {
		$variable = new \WC_Product_Variable();
		$variable->set_name( 'Relaxed Selection' );
		$variable->set_status( 'publish' );
		$variable->set_attributes( array( WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large' ) ) ) );
		$parent_id = $variable->save();

		foreach ( array( 'small', 'large' ) as $size ) {
			$variation = new \WC_Product_Variation();
			$variation->set_parent_id( $parent_id );
			$variation->set_attributes( array( 'pa_size' => $size ) );
			$variation->set_regular_price( '10' );
			$variation->set_status( 'publish' );
			$variation->save();
		}

		\WC_Product_Variable::sync( $parent_id );
		$mapper  = new UcpProductMapper( 'USD' );
		$product = wc_get_product( $parent_id );

		$size  = array(
			'name'  => 'size',
			'label' => 'large',
		);
		$color = array(
			'name'  => 'color',
			'label' => 'red',
		);

		$mapped = $mapper->map_detail_product( $product, array( $size, $color ) );
		$this->assertSame( array( $size ), $mapped['selected'] );
		$this->assertCount( 1, $mapped['variants'] );

		$mapped = $mapper->map_detail_product(
			$product,
			array(
				array(
					'name'  => 'size',
					'label' => 'medium',
				),
			)
		);
		$this->assertSame( array(), $mapped['selected'] );
		$this->assertCount( 2, $mapped['variants'] );
	}

	/**
	 * The storefront treats an "Any" variation attribute as a wildcard and
	 * `options` advertises every term for it, so a selection must match it too.
	 *
	 * @testdox A variation left as "Any" for an attribute should match every selection of it.
	 */
	public function test_detail_selection_matches_any_variations(): void {
		$variable = new \WC_Product_Variable();
		$variable->set_name( 'Any Color' );
		$variable->set_status( 'publish' );
		$variable->set_attributes(
			array(
				WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large' ) ),
				WC_Helper_Product::create_product_attribute_object( 'color', array( 'red', 'blue' ) ),
			)
		);
		$parent_id = $variable->save();

		foreach ( array( 'small', 'large' ) as $size ) {
			$variation = new \WC_Product_Variation();
			$variation->set_parent_id( $parent_id );
			$variation->set_attributes(
				array(
					'pa_size'  => $size,
					'pa_color' => '',
				)
			);
			$variation->set_regular_price( '10' );
			$variation->set_status( 'publish' );
			$variation->save();
		}

		\WC_Product_Variable::sync( $parent_id );

		$selected = array(
			array(
				'name'  => 'color',
				'label' => 'red',
			),
			array(
				'name'  => 'size',
				'label' => 'large',
			),
		);
		$mapped   = ( new UcpProductMapper( 'USD' ) )->map_detail_product( wc_get_product( $parent_id ), $selected );

		$this->assertSame( $selected, $mapped['selected'] );
		$this->assertCount( 1, $mapped['variants'] );
		$this->assertCount( 2, $mapped['options'][1]['values'] );
	}

	/**
	 * Build a variable product with size and intensity attributes.
	 *
	 * @param array $variations Each `[ size, intensity ]`; '' leaves the attribute as "Any".
	 * @return \WC_Product_Variable
	 */
	private function create_size_intensity_product( array $variations ): \WC_Product_Variable {
		$variable = new \WC_Product_Variable();
		$variable->set_name( 'Roast' );
		$variable->set_status( 'publish' );
		$variable->set_attributes(
			array(
				WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large' ) ),
				WC_Helper_Product::create_product_attribute_object( 'intensity', array( 'mild', 'bold' ) ),
			)
		);
		$parent_id = $variable->save();

		foreach ( $variations as list( $size, $intensity ) ) {
			$variation = new \WC_Product_Variation();
			$variation->set_parent_id( $parent_id );
			$variation->set_attributes(
				array(
					'pa_size'      => $size,
					'pa_intensity' => $intensity,
				)
			);
			$variation->set_regular_price( '10' );
			$variation->set_status( 'publish' );
			$variation->save();
		}

		\WC_Product_Variable::sync( $parent_id );

		return wc_get_product( $parent_id );
	}

	/**
	 * Selected option helper.
	 *
	 * @param string $name Option name.
	 * @param string $label Option label.
	 * @return array
	 */
	private function option( string $name, string $label ): array {
		return array(
			'name'  => $name,
			'label' => $label,
		);
	}

	/**
	 * Variant titles of a mapped product.
	 *
	 * @param array $mapped Mapped product.
	 * @return array
	 */
	private function titles( array $mapped ): array {
		return array_column( $mapped['variants'], 'title' );
	}

	/**
	 * UCP gives `selected` order no meaning, so relaxation must not depend on it.
	 *
	 * @testdox Without preferences, relaxation should keep the satisfiable option whatever the selection order.
	 */
	public function test_detail_relaxation_is_order_independent_without_preferences(): void {
		$product = $this->create_size_intensity_product( array( array( 'small', 'mild' ), array( 'large', 'mild' ) ) );
		$mapper  = new UcpProductMapper( 'USD' );
		$size    = $this->option( 'size', 'large' );
		$bold    = $this->option( 'intensity', 'bold' );

		foreach ( array( array( $size, $bold ), array( $bold, $size ) ) as $selected ) {
			$mapped = $mapper->map_detail_product( $product, $selected );

			$this->assertSame( array( $size ), $mapped['selected'] );
			$this->assertSame( array( 'large / mild' ), $this->titles( $mapped ) );
		}
	}

	/**
	 * @testdox Without preferences, variations satisfying different options should tie and report no selection.
	 */
	public function test_detail_relaxation_tie_reports_no_selection(): void {
		$product = $this->create_size_intensity_product( array( array( 'small', 'bold' ), array( 'large', 'mild' ) ) );

		$mapped = ( new UcpProductMapper( 'USD' ) )->map_detail_product(
			$product,
			array( $this->option( 'size', 'large' ), $this->option( 'intensity', 'bold' ) )
		);

		$this->assertSame( array(), $mapped['selected'] );
		$this->assertCount( 2, $mapped['variants'] );
	}

	/**
	 * UCP: "the server drops options from the end of this list first".
	 *
	 * @testdox With preferences, the option named last should be dropped first.
	 */
	public function test_detail_relaxation_follows_preferences(): void {
		$product = $this->create_size_intensity_product( array( array( 'small', 'bold' ), array( 'large', 'mild' ) ) );
		$mapper  = new UcpProductMapper( 'USD' );
		$size    = $this->option( 'size', 'large' );
		$bold    = $this->option( 'intensity', 'bold' );

		$mapped = $mapper->map_detail_product( $product, array( $size, $bold ), array( 'Size', 'Intensity' ) );
		$this->assertSame( array( $size ), $mapped['selected'] );
		$this->assertSame( array( 'large / mild' ), $this->titles( $mapped ) );

		$mapped = $mapper->map_detail_product( $product, array( $size, $bold ), array( 'Intensity', 'Size' ) );
		$this->assertSame( array( $bold ), $mapped['selected'] );
		$this->assertSame( array( 'small / bold' ), $this->titles( $mapped ) );
	}

	/**
	 * @testdox With preferences, an option they do not name should be dropped before any they do.
	 */
	public function test_detail_relaxation_drops_unlisted_options_first(): void {
		$product = $this->create_size_intensity_product( array( array( 'small', 'bold' ), array( 'large', 'mild' ) ) );
		$size    = $this->option( 'size', 'large' );
		$bold    = $this->option( 'intensity', 'bold' );

		$mapped = ( new UcpProductMapper( 'USD' ) )->map_detail_product( $product, array( $size, $bold ), array( 'Intensity' ) );

		$this->assertSame( array( $bold ), $mapped['selected'] );
		$this->assertSame( array( 'small / bold' ), $this->titles( $mapped ) );
	}

	/**
	 * @testdox A selection nothing satisfies should report no selection and every variant, with or without preferences.
	 */
	public function test_detail_relaxation_to_nothing_returns_every_variant(): void {
		$product  = $this->create_size_intensity_product( array( array( 'small', 'mild' ), array( 'large', 'mild' ) ) );
		$mapper   = new UcpProductMapper( 'USD' );
		$selected = array( $this->option( 'size', 'medium' ), $this->option( 'intensity', 'bold' ) );

		foreach ( array( array(), array( 'Size', 'Intensity' ) ) as $preferences ) {
			$mapped = $mapper->map_detail_product( $product, $selected, $preferences );

			$this->assertSame( array(), $mapped['selected'] );
			$this->assertCount( 2, $mapped['variants'] );
		}
	}

	/**
	 * Agents compare titles, SKUs and option labels literally, so the storefront
	 * HTML formatter must not reach them and both option lists must agree.
	 *
	 * @testdox Mapped strings should be raw and option labels should be term names on both lists.
	 */
	public function test_strings_are_raw_and_option_labels_are_term_names(): void {
		$variable = new \WC_Product_Variable();
		$variable->set_name( 'Frame 10x20 "Classic"' );
		$variable->set_status( 'publish' );
		$variable->set_attributes( array( WC_Helper_Product::create_product_attribute_object( 'size', array( 'Extra Large' ) ) ) );
		$parent_id = $variable->save();

		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $parent_id );
		$variation->set_attributes( array( 'pa_size' => 'extra-large' ) );
		$variation->set_regular_price( '10' );
		$variation->set_sku( 'FRAME-10x20' );
		$variation->set_status( 'publish' );
		$variation->save();

		\WC_Product_Variable::sync( $parent_id );

		$mapped = ( new UcpProductMapper( 'USD' ) )->map_product( wc_get_product( $parent_id ) );

		$this->assertSame( 'Frame 10x20 "Classic"', $mapped['title'] );
		$this->assertSame( 'FRAME-10x20', $mapped['variants'][0]['sku'] );
		$this->assertSame(
			array(
				array(
					'name'   => 'size',
					'values' => array( array( 'label' => 'Extra Large' ) ),
				),
			),
			$mapped['options']
		);
		$this->assertSame(
			array(
				array(
					'name'  => 'size',
					'label' => 'Extra Large',
				),
			),
			$mapped['variants'][0]['options']
		);
	}
}
