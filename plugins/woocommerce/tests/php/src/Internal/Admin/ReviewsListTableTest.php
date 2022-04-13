<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use Generator;
use ReflectionClass;
use ReflectionException;
use WC_Helper_Product;
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
			$this->get_reviews_list_table()->get_columns()
		);
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

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_type()
	 *
	 * @dataProvider data_provider_test_column_type()
	 * @param string $comment_type The comment type (usually review or comment).
	 * @param string $expected_output The expected output.
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
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_rating()
	 *
	 * @dataProvider data_provider_test_column_rating()
	 * @param string $meta_value The comment meta value for rating.
	 * @param string $expected_output The expected output.
	 */
	public function test_column_rating( $meta_value, $expected_output ) {

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'column_rating' );
		$method->setAccessible( true );

		$review = $this->get_test_review();

		if ( ! empty( $meta_value ) ) {
			update_comment_meta( $review->comment_ID, 'rating', $meta_value );
		}

		ob_start();
		$method->invokeArgs( $list_table, [ $review ] );
		$output = trim( ob_get_clean() );

		$this->assertSame( $expected_output, $output );
	}

	/** @see test_column_rating() */
	public function data_provider_test_column_rating() {
		return [
			'no rating' => [ '', '' ],
			'1 star' => [ '1', '<span aria-label="1 out of 5">&#9733;&#9734;&#9734;&#9734;&#9734;</span>' ],
			'2 stars' => [ '2', '<span aria-label="2 out of 5">&#9733;&#9733;&#9734;&#9734;&#9734;</span>' ],
			'3 stars' => [ '3', '<span aria-label="3 out of 5">&#9733;&#9733;&#9733;&#9734;&#9734;</span>' ],
			'4 stars' => [ '4', '<span aria-label="4 out of 5">&#9733;&#9733;&#9733;&#9733;&#9734;</span>' ],
			'5 stars' => [ '5', '<span aria-label="5 out of 5">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ],
			'2.5 stars (rounds down)' => [ '2.5', '<span aria-label="2 out of 5">&#9733;&#9733;&#9734;&#9734;&#9734;</span>' ],
		];
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_sortable_columns()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_sortable_columns() {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_sortable_columns' );
		$method->setAccessible( true );

		$this->assertSame(
			[
				'author'   => 'comment_author',
				'response' => 'comment_post_ID',
				'date'     => 'comment_date_gmt',
				'type'     => 'comment_type',
				'rating'   => 'rating',
			],
			$method->invoke( $list_table )
		);
	}

	/**
	 * @covers       \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_sort_arguments()
	 * @dataProvider provider_get_sort_arguments
	 *
	 * @param string|null $orderby       The orderby value that's set in the request.
	 * @param string|null $order         The order value that's set in the request.
	 * @param array       $expected_args Expected arguments.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_sort_arguments( ?string $orderby, ?string $order, array $expected_args ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_sort_arguments' );
		$method->setAccessible( true );

		if ( ! is_null( $orderby ) ) {
			$_REQUEST['orderby'] = $orderby;
		} else {
			unset( $_REQUEST['orderby'] );
		}

		if ( ! is_null( $order ) ) {
			$_REQUEST['order'] = $order;
		} else {
			unset( $_REQUEST['order'] );
		}

		$this->assertSame( $expected_args, $method->invoke( $list_table ) );
	}

	/** @see test_get_sort_arguments */
	public function provider_get_sort_arguments() : Generator {
		yield 'order by comment_author desc' => [
			'comment_author',
			'desc',
			[
				'orderby' => 'comment_author',
				'order'   => 'desc',
			],
		];

		yield 'order by comment_post_ID asc' => [
			'comment_post_ID',
			'asc',
			[
				'orderby' => 'comment_post_ID',
				'order'   => 'asc',
			],
		];

		yield 'order by rating desc' => [
			'rating',
			'desc',
			[
				'meta_key' => 'rating',
				'orderby'  => 'meta_value_num',
				'order'    => 'desc',
			],
		];

		yield 'order by comment type desc' => [
			'comment_type',
			'desc',
			[
				'orderby' => 'comment_type',
				'order'   => 'desc',
			],
		];

		yield 'order by comment date ASC uppercase' => [
			'comment_date_gmt',
			'ASC',
			[
				'orderby' => 'comment_date_gmt',
				'order'   => 'asc',
			],
		];

		yield 'invalid orderby, invalid order' => [
			'invalid-orderby',
			'invalid-order',
			[
				'orderby' => 'comment_date_gmt',
				'order'   => 'desc',
			],
		];

		yield 'missing orderby, missing order' => [
			null,
			null,
			[
				'orderby' => 'comment_date_gmt',
				'order'   => 'desc',
			],
		];
	}
}
