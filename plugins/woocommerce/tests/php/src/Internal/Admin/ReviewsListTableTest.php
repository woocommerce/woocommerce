<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
use WC_Unit_Test_Case;

/**
 * Tests that product reviews page handler.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable
 */
class ReviewsListTableTest extends WC_Unit_Test_Case {

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_columns()
	 */
	public function test_get_columns() {
		$this->assertSame( [
			'cb' => '<input type="checkbox" />',
			'type' => _x( 'Type', 'review type', 'woocommerce' ),
			'author' => __( 'Author', 'woocommerce' ),
			'rating' => __( 'Rating', 'woocommerce' ),
			'comment' => _x( 'Review', 'column name', 'woocommerce' ),
			'response' => __( 'Product', 'woocommerce' ),
			'date' => _x( 'Submitted on', 'column name', 'woocommerce' ),
		], ( new ReviewsListTable() )->get_columns() );
	}

}
