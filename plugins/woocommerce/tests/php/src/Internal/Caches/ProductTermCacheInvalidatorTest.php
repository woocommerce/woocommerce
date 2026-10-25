<?php
/**
 * ProductTermCacheInvalidator tests.
 *
 * @package WooCommerce\Tests\Internal\Caches
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\ProductTermCacheInvalidator;
use WC_Cache_Helper;
use WC_Helper_Product;
use WC_Product;
use WC_Unit_Test_Case;
use WP_Term;

/**
 * Tests for ProductTermCacheInvalidator.
 */
final class ProductTermCacheInvalidatorTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var ProductTermCacheInvalidator
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( ProductTermCacheInvalidator::class );
	}

	/**
	 * @testdox Reordering product terms invalidates cached results.
	 */
	public function test_reordering_product_terms_invalidates_cached_results(): void {
		$taxonomy = 'product_tag';
		$suffix   = (string) wp_rand( 1000, 9999 );
		$names    = array( 'First tag ' . $suffix, 'Second tag ' . $suffix );
		$term_ids = array();
		$product  = null;

		try {
			foreach ( $names as $index => $name ) {
				$term = wp_insert_term( $name, $taxonomy );
				$this->assertIsArray( $term, "The {$name} term should be created." );

				$term_ids[] = $term['term_id'];
				update_term_meta( $term['term_id'], 'order', $index + 1 );
			}

			$product = WC_Helper_Product::create_simple_product();
			wp_set_object_terms( $product->get_id(), $term_ids, $taxonomy );

			$args         = array(
				'fields'     => 'all',
				'menu_order' => 'ASC',
			);
			$cached_terms = wc_get_product_terms( $product->get_id(), $taxonomy, $args );
			$this->assertSame(
				$names,
				wp_list_pluck( $cached_terms, 'name' ),
				'Product terms should initially use the configured order.'
			);

			$term_to_move = get_term( $term_ids[1], $taxonomy );
			$this->assertInstanceOf( WP_Term::class, $term_to_move );

			wc_reorder_terms( $term_to_move, $term_ids[0], $taxonomy );

			$reordered_terms = wc_get_product_terms( $product->get_id(), $taxonomy, $args );
			$this->assertSame(
				array_reverse( $names ),
				wp_list_pluck( $reordered_terms, 'name' ),
				'Product terms should use the new order without saving the product.'
			);
		} finally {
			if ( $product instanceof WC_Product ) {
				$product->delete( true );
			}

			foreach ( $term_ids as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}
	}
}
