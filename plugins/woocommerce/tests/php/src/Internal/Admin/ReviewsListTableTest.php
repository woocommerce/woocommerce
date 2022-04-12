<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
use ReflectionClass;
use WC_Unit_Test_Case;

/**
 * Tests that product reviews page handler.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable
 */
class ReviewsListTableTest extends WC_Unit_Test_Case {

	/**
	 * Returns a new instance of the ReviewsListTable class.
	 *
	 * @return ReviewsListTable
	 */
	protected function get_reviews_list_table() : ReviewsListTable {
		return new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] );
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_primary_column_name()
	 */
	public function test_get_primary_column_name() {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_primary_column_name' );
		$method->setAccessible( true );

		$this->assertSame( 'comment', $method->invoke( $list_table ) );
	}

}
