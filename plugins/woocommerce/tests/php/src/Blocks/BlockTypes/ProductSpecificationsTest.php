<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Product_Simple;
use WC_Unit_Test_Case;
use WP_Block;

/**
 * Tests for the Product Specifications block type.
 */
class ProductSpecificationsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Product specifications render plain text when a term link cannot be resolved.
	 */
	public function test_render_does_not_crash_for_deleted_term(): void {
		global $wc_product_attributes;

		$previous_attributes = $wc_product_attributes;
		$attribute           = WC_Helper_Product::create_product_attribute_object( 'Archive Finish', array( 'Matte & Gloss' ) );
		$taxonomy            = $attribute->get_name();

		try {
			$wc_product_attributes[ $taxonomy ]->attribute_public = true;

			$product = new WC_Product_Simple();
			$product->set_attributes( array( $attribute ) );
			$product->save();

			$term = get_term( $attribute->get_options()[0], $taxonomy );
			wp_delete_term( $term->term_id, $taxonomy );

			add_filter( 'woocommerce_get_product_terms', static fn() => array( $term ) );

			$sut = new WP_Block(
				array(
					'blockName'    => 'woocommerce/product-specifications',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
				array( 'postId' => $product->get_id() )
			);

			$markup = $sut->render();
		} finally {
			unregister_taxonomy( $taxonomy );
			$wc_product_attributes = $previous_attributes;
		}

		$this->assertStringContainsString( 'Matte &amp; Gloss', $markup, 'The term name should be rendered as escaped text.' );
		$this->assertStringNotContainsString( '<a ', $markup, 'The deleted term should not be linked.' );
	}
}
