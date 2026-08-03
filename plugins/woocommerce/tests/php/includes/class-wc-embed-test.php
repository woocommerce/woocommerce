<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Embed class.
 */
class WC_Embed_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Password-protected product embeds should not expose the image, price, short description, or rating.
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
		$this->assertSame( 0, WC_Embed::handle_embed_thumbnail_id( 123 ), 'The product image should not be rendered.' );
		$response_data = WC_Embed::handle_oembed_response_data(
			array(
				'title'            => 'Protected product',
				'thumbnail_url'    => 'https://example.com/image.jpg',
				'thumbnail_width'  => 600,
				'thumbnail_height' => 600,
			),
			get_post( $product->get_id() )
		);
		$this->assertSame( 'Protected product', $response_data['title'], 'The product title should remain in the oEmbed response.' );
		$this->assertArrayNotHasKey( 'thumbnail_url', $response_data, 'The product image URL should not be in the oEmbed response.' );
		$this->assertArrayNotHasKey( 'thumbnail_width', $response_data, 'The product image width should not be in the oEmbed response.' );
		$this->assertArrayNotHasKey( 'thumbnail_height', $response_data, 'The product image height should not be in the oEmbed response.' );

		ob_start();
		$excerpt = WC_Embed::the_excerpt( 'Password required' );
		WC_Embed::get_ratings();
		$output = ob_get_clean();

		$this->assertSame( '', $output, 'The product price and rating should not be rendered.' );
		$this->assertSame( 'Password required', $excerpt, 'The protected short description should not replace the password-protected excerpt.' );
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
		$this->assertSame( 123, WC_Embed::handle_embed_thumbnail_id( 123 ), 'The product image should be rendered.' );
		$response_data = array(
			'title'         => 'Public product',
			'thumbnail_url' => 'https://example.com/image.jpg',
		);
		$this->assertSame(
			$response_data,
			WC_Embed::handle_oembed_response_data( $response_data, get_post( $product->get_id() ) ),
			'The public product image should remain in the oEmbed response.'
		);

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
