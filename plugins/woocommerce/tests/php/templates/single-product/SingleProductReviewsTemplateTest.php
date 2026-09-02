<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Templates\SingleProduct;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the single product reviews template.
 */
class SingleProductReviewsTemplateTest extends WC_Unit_Test_Case {

	/**
	 * Product title containing markup that survives post content filtering on save.
	 */
	private const PRODUCT_TITLE = 'Widget <strong>Deluxe</strong>';

	/**
	 * @testdox The reviews heading escapes markup in the product title and hands the built heading to the filter.
	 */
	public function test_reviews_title_escapes_product_title_and_passes_built_heading_to_filter(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( self::PRODUCT_TITLE );
		$product->set_review_count( 2 );
		$product->save();

		wp_update_post(
			array(
				'ID'             => $product->get_id(),
				'comment_status' => 'open',
			)
		);

		$this->go_to( get_permalink( $product->get_id() ) );

		$previous_product   = $GLOBALS['product'] ?? null;
		$GLOBALS['product'] = $product;

		$received_title = null;
		$title_filter   = static function ( $reviews_title ) use ( &$received_title ) {
			$received_title = $reviews_title;
			return $reviews_title;
		};
		add_filter( 'woocommerce_reviews_title', $title_filter );

		try {
			$html = wc_get_template_html( 'single-product-reviews.php' );
		} finally {
			remove_filter( 'woocommerce_reviews_title', $title_filter );
			$GLOBALS['product'] = $previous_product;
			WC_Helper_Product::delete_product( $product->get_id() );
		}

		preg_match( '#<h2 class="woocommerce-Reviews-title">(.*?)</h2>#s', $html, $heading_match );
		$heading = $heading_match[1] ?? '';

		$this->assertStringContainsString(
			'<span>Widget &lt;strong&gt;Deluxe&lt;/strong&gt;</span>',
			$heading,
			'The reviews heading should render the product title escaped inside the span.'
		);
		$this->assertStringNotContainsString(
			'<strong>',
			$heading,
			'The reviews heading should not render markup carried by the product title.'
		);
		$this->assertSame(
			'2 reviews for <span>Widget &lt;strong&gt;Deluxe&lt;/strong&gt;</span>',
			$received_title,
			'The woocommerce_reviews_title filter should receive the complete heading string.'
		);
	}
}
