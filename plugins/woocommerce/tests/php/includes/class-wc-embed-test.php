<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Embed class.
 */
class WC_Embed_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Password-protected product embeds should not expose the short description.
	 */
	public function test_embed_does_not_expose_password_protected_product_data(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '19.99' );
		$product->set_short_description( 'Protected short description' );
		$product->save();

		$review_id = WC_Helper_Product::create_product_review( $product->get_id() );
		update_comment_meta( $review_id, 'rating', 5 );
		WC_Comments::clear_transients( $product->get_id() );
		$this->assertGreaterThan( 0, (float) wc_get_product( $product->get_id() )->get_average_rating(), 'The product should have a rating to render.' );
		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'secret',
			)
		);

		$this->go_to_product_embed( $product );
		$this->assertTrue( post_password_required(), 'The product should require a password.' );

		ob_start();
		$excerpt = WC_Embed::the_excerpt( 'Password required' );
		WC_Embed::get_ratings();
		$output = ob_get_clean();

		$this->assertStringContainsString( '19.99', $output, 'The product price should continue to be rendered.' );
		$this->assertStringContainsString( 'Rated 5.00 out of 5', $output, 'The product rating should continue to be rendered.' );
		$this->assertStringContainsString( 'Password required', $excerpt, 'The password-protected excerpt should continue to be rendered.' );
		$this->assertStringNotContainsString( 'Protected short description', $excerpt, 'The protected short description should not replace the password-protected excerpt.' );
	}

	/**
	 * @testdox Public product embeds should continue to render product data.
	 */
	public function test_the_excerpt_renders_public_product_data(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '19.99' );
		$product->set_short_description( 'Public short description' );
		$product->save();

		$this->go_to_product_embed( $product );

		ob_start();
		$excerpt = WC_Embed::the_excerpt( 'Original excerpt' );
		$output  = ob_get_clean();

		$this->assertStringContainsString( '19.99', $output, 'The product price should be rendered.' );
		$this->assertStringContainsString( 'Public short description', $excerpt, 'The product short description should be rendered.' );
	}

	/**
	 * Set up the main query for a product embed request.
	 *
	 * @param WC_Product $product Product to query.
	 */
	private function go_to_product_embed( WC_Product $product ): void {
		$this->go_to( get_permalink( $product->get_id() ) );
		$GLOBALS['wp_query']->is_embed = true;

		$this->assertTrue( WC_Embed::is_embedded_product(), 'The test request should be recognized as an embedded product.' );
	}
}
