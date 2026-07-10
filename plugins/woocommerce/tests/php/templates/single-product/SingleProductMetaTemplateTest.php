<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Templates\SingleProduct;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the single product meta template.
 */
class SingleProductMetaTemplateTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Single product meta renders assigned categories in breadcrumb order.
	 */
	public function test_single_product_meta_renders_categories_in_breadcrumb_order(): void {
		$had_previous_product = array_key_exists( 'product', $GLOBALS );
		$previous_product     = $GLOBALS['product'] ?? null;
		$suffix               = wp_unique_id();
		$root_name            = 'Template Root ' . $suffix;
		$mid_name             = 'Template Mid ' . $suffix;
		$leaf_name            = 'Template Leaf ' . $suffix;
		$root                 = wp_insert_term( $root_name, 'product_cat' );
		$mid                  = wp_insert_term( $mid_name, 'product_cat', array( 'parent' => $root['term_id'] ) );
		$leaf                 = wp_insert_term( $leaf_name, 'product_cat', array( 'parent' => $mid['term_id'] ) );
		$test_product         = WC_Helper_Product::create_simple_product();
		$terms_filter         = null;

		try {
			wp_set_object_terms( $test_product->get_id(), array( $leaf['term_id'], $root['term_id'], $mid['term_id'] ), 'product_cat' );
			$GLOBALS['product'] = wc_get_product( $test_product->get_id() );

			$content = preg_replace( '/\s+/', ' ', wp_strip_all_tags( wc_get_template_html( 'single-product/meta.php' ) ) );

			$this->assertMatchesRegularExpression(
				'/Categor(?:y|ies): ' . preg_quote( "{$root_name}, {$mid_name}, {$leaf_name}", '/' ) . '/',
				$content,
				'Single product meta should render product categories in root-to-leaf order.'
			);

			$terms_filter = static function ( $terms, $post_id, $taxonomy ) use ( $test_product ) {
				return $test_product->get_id() === $post_id && 'product_cat' === $taxonomy ? new \WP_Error( 'category-list-error' ) : $terms;
			};
			add_filter( 'get_the_terms', $terms_filter, 10, 3 );

			$this->assertStringNotContainsString( 'class="posted_in"', wc_get_template_html( 'single-product/meta.php' ) );
		} finally {
			if ( null !== $terms_filter ) {
				remove_filter( 'get_the_terms', $terms_filter, 10 );
			}
			WC_Helper_Product::delete_product( $test_product->get_id() );
			wp_delete_term( $leaf['term_id'], 'product_cat' );
			wp_delete_term( $mid['term_id'], 'product_cat' );
			wp_delete_term( $root['term_id'], 'product_cat' );

			if ( $had_previous_product ) {
				$GLOBALS['product'] = $previous_product;
			} else {
				unset( $GLOBALS['product'] );
			}
		}
	}
}
