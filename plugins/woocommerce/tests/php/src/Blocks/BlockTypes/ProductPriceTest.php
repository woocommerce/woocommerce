<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Product_Variable;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductPrice block type.
 */
class ProductPriceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox When all variations cost the same, the Product Price block switches to the selected variation's price only if taxes are on, the product is taxable, and the price suffix uses a tag like {price_including_tax}.
	 * @testWith ["{price_including_tax} (inc vat)", "yes", "taxable", true]
	 *           ["inc. VAT", "yes", "taxable", false]
	 *           ["{price_including_tax} (inc vat)", "no", "taxable", false]
	 *           ["{price_including_tax} (inc vat)", "yes", "none", false]
	 *
	 * @param string $suffix               The price display suffix option.
	 * @param string $calc_taxes           The calc taxes option.
	 * @param string $tax_status           The variable product tax status.
	 * @param bool   $expected_interactive Whether the block switches to the selected variation's price.
	 */
	public function test_same_price_variations_switch_price_only_for_tax_tag_suffix( string $suffix, string $calc_taxes, string $tax_status, bool $expected_interactive ): void {
		update_option( 'woocommerce_calc_taxes', $calc_taxes );
		update_option( 'woocommerce_price_display_suffix', $suffix );
		$product = WC_Helper_Product::create_variation_product();
		$product->set_tax_status( $tax_status );
		$product->save();
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			$child->set_regular_price( '10' );
			$child->set_sale_price( '' );
			$child->save();
		}
		WC_Product_Variable::sync( $product->get_id() );

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><!-- wp:woocommerce/product-price /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertSame( $expected_interactive, str_contains( $markup, 'data-wp-watch="callbacks.updateValue"' ) );
	}
}
