<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use ReflectionClass;
use ReflectionException;
use WC_Helper_Product;
use WC_Unit_Test_Case;
use WP_Comment;

/**
 * Tests that product reviews page handler.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable
 */
class ReviewsListTableTest extends WC_Unit_Test_Case {

	/**
	 * Tests that can get the product reviews' page columns.
	 *
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
			$this->get_reviews_list_table()->get_columns()
		);
	}

	/**
	 * Tests that can get the primary column name.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_primary_column_name()
	 *
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_get_primary_column_name() {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_primary_column_name' );
		$method->setAccessible( true );

		$this->assertSame( 'comment', $method->invoke( $list_table ) );
	}

	/**
	 * Tests the output of the review type column.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_type()
	 * @dataProvider data_provider_test_column_type()
	 *
	 * @param string $comment_type The comment type (usually review or comment).
	 * @param string $expected_output The expected output.
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_type( $comment_type, $expected_output ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'column_type' );
		$method->setAccessible( true );

		$review = $this->get_test_review();
		$review->comment_type = $comment_type;

		ob_start();
		$method->invokeArgs( $list_table, [ $review ] );
		$output = ob_get_clean();

		$this->assertSame( $expected_output, $output );
	}

	/** @see test_column_type() */
	public function data_provider_test_column_type() {
		return [
			'review' => [ 'review', '&#9734;&nbsp;Review' ],
			'reply' => [ 'comment', 'Reply' ],
			'default to reply' => [ 'anything', 'Reply' ],
		];
	}

	/**
	 * Tests that can get a review author url for display.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_item_author_url_for_display()
	 * @dataProvider data_provider_test_get_item_author_url_for_display()
	 *
	 * @param string $author_url The author URL.
	 * @param string $author_url_for_display The author URL for display.
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_get_item_author_url_for_display( $author_url, $author_url_for_display ) {

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_item_author_url_for_display' );

		$this->assertSame( $author_url_for_display, $method->invokeArgs( $list_table, [ $author_url ] ) );
	}

	/** @see test_get_item_author_url_for_display() */
	public function data_provider_test_get_item_author_url_for_display() {
		$very_long_url = 'https://www.example.com/this-is-a-very-long-url-that-is-longer-than-the-maximum-allowed-length-of-the-url-for-display-purposes/';

		return [
			'Empty URL' => [ '', '' ],
			'Empty URL (http)' => [ 'http://', '' ],
			'Empty URL (https)' => [ 'https://', '' ],
			'Regular URL' => [ 'https://www.example.com', 'https://www.example.com' ],
			'Very long URL' => [ $very_long_url, substr( $very_long_url, 0, 49 ) . '&hellip;' ],
		];
	}

	/**
	 * Returns a new instance of the {@see ReviewsListTable} class.
	 *
	 * @return ReviewsListTable
	 */
	protected function get_reviews_list_table() : ReviewsListTable {
		return new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] );
	}

	/**
	 * Returns a test review object.
	 *
	 * @return WP_Comment|null
	 */
	protected function get_test_review() {

		$product = WC_Helper_Product::create_simple_product();

		$review_id = ProductHelper::create_product_review( $product->get_id() );

		$reviews = get_comments(
			[
				'id' => $review_id,
			]
		);

		return ! empty( $reviews ) ? current( $reviews ) : null;
	}
}
