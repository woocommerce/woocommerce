<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
use Generator;
use ReflectionClass;
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
		$this->assertSame(
			[
				'cb'       => '<input type="checkbox" />',
				'type'     => _x( 'Type', 'review type', 'woocommerce' ),
				'author'   => __( 'Author', 'woocommerce' ),
				'rating'   => __( 'Rating', 'woocommerce' ),
				'comment'  => _x( 'Review', 'column name', 'woocommerce' ),
				'response' => __( 'Product', 'woocommerce' ),
				'date'     => _x( 'Submitted on', 'column name', 'woocommerce' ),
			],
			( new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] ) )->get_columns()
		);
	}

	/**
	 * @dataProvider provider_get_bulk_actions
	 *
	 * @param string $current_comment_status Currently set status.
	 * @param array  $expected_actions       Keys of the expected actions.
	 * @return void
	 */
	public function test_get_bulk_actions( string $current_comment_status, array $expected_actions ) {
		$list_table = new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] );
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_bulk_actions' );
		$method->setAccessible( true );

		// @TODO PHPCS doesn't like this {agibson 2022-04-12}
		// global $comment_status;
		// $comment_status = $current_comment_status;

		$this->assertEqualsCanonicalizing(
			$expected_actions,
			array_keys( $method->invoke( $list_table ) )
		);
	}

	/** @see test_get_bulk_actions */
	public function provider_get_bulk_actions() : Generator {
		yield 'all statuses' => [
			'current_comment_status' => 'all',
			'expected_actions' => [
				'unapprove',
				'approve',
				'spam',
				'trash',
			],
		];
	}

}
