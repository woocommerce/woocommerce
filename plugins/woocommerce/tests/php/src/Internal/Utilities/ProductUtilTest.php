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
}
