<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Reviews;
use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
use ReflectionClass;
use WC_Unit_Test_Case;

/**
 * Tests for the admin reviews handler.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews
 */
class ReviewsTest extends WC_Unit_Test_Case {

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::get_instance()
	 */
	public function test_get_instance() {
		$this->assertInstanceOf( Reviews::class, Reviews::get_instance() );
	}

	/**
	 * Tests that `load_reviews_screen()` creates an instance of ReviewsListTable.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::load_reviews_screen()
	 */
	public function test_load_reviews_screen() {
		$reviews = new Reviews();

		// This has to be manually set, otherwise instantiating ReviewsListTable will throw an undefined index error.
		$hook_property = ( new ReflectionClass( $reviews ) )->getProperty( 'reviews_page_hook' );
		$hook_property->setAccessible( true );
		$hook_property->setValue( $reviews, 'product_page_product-reviews' );

		$list_table_property = ( new ReflectionClass( $reviews ) )->getProperty( 'reviews_list_table' );
		$list_table_property->setAccessible( true );

		$this->assertNull( $list_table_property->getValue( $reviews ) );

		$reviews->load_reviews_screen();

		$this->assertInstanceOf( ReviewsListTable::class, $list_table_property->getValue( $reviews ) );
	}

}
