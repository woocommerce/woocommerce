<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use Automattic\WooCommerce\Internal\Utilities\ProductUtil;

/**
 * Tests for the internal ProductUtil class.
 */
class ProductUtilTest extends \WC_Unit_Test_Case {
	/**
	 * @testdox delete_product_transients_for_products deletes fixed-name transients once and fires hooks once per product.
	 */
	public function test_delete_product_transients_for_products_deletes_fixed_transients_and_fires_hooks() {
		$product_ids = array( 0, 123, 123, 456 );
		$deleted_ids = array();
		$track_hook  = static function ( $product_id ) use ( &$deleted_ids ) {
			$deleted_ids[] = (int) $product_id;
		};

		set_transient( 'wc_products_onsale', 'foobar' );
		add_action( 'woocommerce_delete_product_transients', $track_hook );
		try {
			wc_get_container()->get( ProductUtil::class )->delete_product_transients_for_products( $product_ids );
		} finally {
			remove_action( 'woocommerce_delete_product_transients', $track_hook );
		}

		$this->assertFalse( get_transient( 'wc_products_onsale' ) );
		$this->assertSame( array( 0, 123, 456 ), $deleted_ids );
	}

	/**
	 * @testdox delete_product_specific_transients deletes the transients for a product that is not a variation.
	 *
	 * @param bool $use_id True to pass the product id to delete_product_specific_transients, false to pass the product object.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_delete_product_specific_transients_deletes_transients_for_simple_product( bool $use_id ) {
		$product        = ProductHelper::create_simple_product();
		$transient_name = 'wc_related_' . $product->get_id();
		set_transient( $transient_name, 'foobar' );

		wc_get_container()->get( ProductUtil::class )->delete_product_specific_transients( $use_id ? $product->get_id() : $product );

		$this->assertFalse( get_transient( $transient_name ) );
	}

	/**
	 * delete_product_specific_transients deletes the transients for a variation product and also for its parent.
	 *
	 * @param bool $use_id True to pass the product id to delete_product_specific_transients, false to pass the product object.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_delete_product_specific_transients_deletes_transients_for_variation_and_parent( bool $use_id ) {
		$parent_product = ProductHelper::create_variation_product();
		$child_id       = $parent_product->get_children()[0];
		$child          = wc_get_product( $child_id );

		$parent_transient_name = 'wc_related_' . $parent_product->get_id();
		$child_transient_name  = 'wc_related_' . $child_id;

		set_transient( $parent_transient_name, 'foobar' );
		set_transient( $child_transient_name, 'foobar' );

		wc_get_container()->get( ProductUtil::class )->delete_product_specific_transients( $use_id ? $child_id : $child );

		$this->assertFalse( get_transient( $parent_transient_name ) );
		$this->assertFalse( get_transient( $child_transient_name ) );
	}

	/**
	 * @testdox delete_product_specific_transients_for_products deletes parent variation transients once for multiple variations.
	 */
	public function test_delete_product_specific_transients_for_products_coalesces_parent_variation_transient_deletes() {
		$parent_product  = ProductHelper::create_variation_product();
		$child_ids       = array_slice( $parent_product->get_children(), 0, 2 );
		$delete_attempts = 0;
		$track_deletes   = static function () use ( &$delete_attempts ) {
			++$delete_attempts;
		};

		add_action( 'delete_transient_wc_product_children_' . $parent_product->get_id(), $track_deletes );
		try {
			wc_get_container()->get( ProductUtil::class )->delete_product_specific_transients_for_products( $child_ids );
		} finally {
			remove_action( 'delete_transient_wc_product_children_' . $parent_product->get_id(), $track_deletes );
		}

		$this->assertSame( 1, $delete_attempts );
	}
}
