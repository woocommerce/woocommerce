<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\Ucp;

use Automattic\WooCommerce\StoreApi\Routes\V1\Ucp\AbstractCatalogRoute;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Catalog lookup reachability for Search results only products.
 */
class CatalogReachabilityTest extends WC_Unit_Test_Case {
	/**
	 * Whether a product is reachable through catalog lookup.
	 *
	 * @param \WC_Product $product Product.
	 */
	private function reachable( \WC_Product $product ): bool {
		$method = new \ReflectionMethod( AbstractCatalogRoute::class, 'is_reachable' );
		$method->setAccessible( true );
		return (bool) $method->invoke( null, $product );
	}

	/**
	 * @testdox A published Search results only product can be looked up.
	 */
	public function test_published_search_only_product_is_reachable(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_catalog_visibility( 'search' );
		$product->save();

		$this->assertTrue( $this->reachable( wc_get_product( $product->get_id() ) ) );
	}

	/**
	 * @testdox A trashed Search results only product stays out of catalog lookup, including for a user who can edit it.
	 */
	public function test_trashed_search_only_product_is_not_reachable_to_an_editor(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_catalog_visibility( 'search' );
		$product->set_status( 'trash' );
		$product->save();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$loaded = wc_get_product( $product->get_id() );
		$this->assertInstanceOf( \WC_Product::class, $loaded );
		$this->assertFalse( $this->reachable( $loaded ) );
	}
}
