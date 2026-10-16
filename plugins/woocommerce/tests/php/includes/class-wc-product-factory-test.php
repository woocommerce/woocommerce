<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Product_Factory.
 */
class WC_Product_Factory_Test extends WC_Unit_Test_Case {
	/**
	 * @testdox get_product uses the global product post when no product is passed.
	 */
	public function test_get_product_uses_global_product_post(): void {
		$test_product = WC_Helper_Product::create_simple_product();
		$page_id      = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$old_post     = $GLOBALS['post'] ?? null;

		try {
			$GLOBALS['post'] = get_post( $test_product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored in finally.
			$product         = WC()->product_factory->get_product();
			$this->assertInstanceOf( WC_Product::class, $product );
			$this->assertSame( $test_product->get_id(), $product->get_id() );

			$GLOBALS['post'] = get_post( $page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored in finally.
			$this->assertFalse( WC()->product_factory->get_product(), 'A non-product global post must not resolve to a product.' );
		} finally {
			$GLOBALS['post'] = $old_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the prior global.
		}
	}
}
