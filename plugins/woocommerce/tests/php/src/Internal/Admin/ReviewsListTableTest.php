<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use Generator;
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
	 * Tests that can process the row output for a review or reply.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::single_row()
	 */
	public function test_single_row() {
		$post_id = $this->factory()->post->create();
		$review = $this->factory()->comment->create_and_get(
			[
				'comment_post_ID'  => $post_id,
			]
		);

		$reviews_list_table = $this->get_reviews_list_table();

		ob_start();

		$reviews_list_table->single_row( $review );

		$row_output = trim( ob_get_clean() );

		$this->assertStringStartsWith( '<tr id="comment-' . $review->comment_ID . '"', $row_output );

		foreach ( $reviews_list_table->get_columns() as $column_id => $column_name ) {
			if ( 'cb' !== $column_id ) {
				$this->assertStringContainsString( 'data-colname="' . $column_name . '"', $row_output );
			} else {
				$this->assertStringContainsString( '<th scope="row" class="check-column"></th>', $row_output );
			}
		}

		$this->assertStringEndsWith( '</tr>', $row_output );
	}

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
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_rating()
	 *
	 * @dataProvider data_provider_test_column_rating()
	 * @param string $meta_value The comment meta value for rating.
	 * @param string $expected_output The expected output.
	 * @throws ReflectionException If the method does not exist.
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
	 * Tests that can output the author information.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_author()
	 * @dataProvider provider_column_author
	 *
	 * @param bool $show_avatars          Value for the `show_avatars` option.
	 * @param bool $should_contain_avatar If the HTML should contain an avatar.
	 *
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_author( bool $show_avatars, bool $should_contain_avatar ) {
		global $comment;

		$review = $this->factory()->comment->create_and_get(
			[
				'comment_author_url' => 'https://example.com',
			]
		);

		$comment = $review; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'column_author' );
		$method->setAccessible( true );

		update_option( 'show_avatars', $show_avatars );

		ob_start();

		$method->invokeArgs( $list_table, [ $review ] );

		$author_output = ob_get_clean();

		$author = get_comment_author( $review->comment_ID );

		$this->assertStringContainsString( $author, $author_output );

		if ( $should_contain_avatar ) {
			$this->assertStringContainsString( "<img alt='' src='", $author_output );
			$this->assertStringContainsString( 'gravatar.com/avatar/', $author_output );
		} else {
			$this->assertStringNotContainsString( "<img alt='' src='", $author_output );
			$this->assertStringNotContainsString( 'gravatar.com/avatar/', $author_output );
		}

		$this->assertStringContainsString( '<a title="https://example.com" href="https://example.com" rel="noopener noreferrer">example.com</a>', $author_output );
	}

	/** @see test_column_author */
	public function provider_column_author() : Generator {
		yield 'avatars disabled' => [ false, false ];
		yield 'avatars enabled' => [ true, true ];
	}

	/**
	 * Tests that can get the item author URL.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_item_author_url()
	 * @dataProvider data_provider_test_get_item_author_url
	 *
	 * @param string $comment_author_url The comment author URL.
	 * @param string $expected_author_url The expected author URL.
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_get_item_author_url( $comment_author_url, $expected_author_url ) {
		global $comment;

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_item_author_url' );
		$method->setAccessible( true );

		$the_comment = $this->factory()->comment->create_and_get(
			[
				'comment_author_url' => $comment_author_url,
			]
		);

		$comment = $the_comment; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->assertSame( $expected_author_url, $method->invoke( $list_table ) );
	}

	/** @see test_get_item_author_url() */
	public function data_provider_test_get_item_author_url() {
		return [
			'No URL' => [ '', '' ],
			'Empty URL (http)' => [ 'http://', '' ],
			'Empty URL (https)' => [ 'https://', '' ],
			'Valid URL' => [ 'https://example.com', 'https://example.com' ],
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
		$method->setAccessible( true );

		$this->assertSame( $author_url_for_display, $method->invokeArgs( $list_table, [ $author_url ] ) );
	}

	/** @see test_get_item_author_url_for_display() */
	public function data_provider_test_get_item_author_url_for_display() {
		$very_long_url = 'https://www.example.com/this-is-a-very-long-url-that-is-longer-than-the-maximum-allowed-length-of-the-url-for-display-purposes/';

		return [
			'Empty URL' => [ '', '' ],
			'Empty URL (http)' => [ 'http://', '' ],
			'Empty URL (https)' => [ 'https://', '' ],
			'Regular URL' => [ 'https://www.example.com', 'example.com' ],
			'Very long URL' => [ $very_long_url, substr( str_replace( 'https://www.', '', $very_long_url ), 0, 49 ) . '&hellip;' ],
		];
	}

	/**
	 * Tests that can output the review or reply date column.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_date()
	 * @dataProvider data_provider_test_column_date
	 *
	 * @param bool $has_product   Whether the review is for a valid product object.
	 * @param int  $approved_flag The review (comment) approved flag.
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_date( $has_product, $approved_flag ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'column_date' );
		$method->setAccessible( true );

		$post_id = $has_product ? $this->factory()->post->create() : 0;
		$review = $this->factory()->comment->create_and_get(
			[
				'comment_post_ID'  => $post_id,
				'comment_approved' => (string) $approved_flag,
			]
		);

		ob_start();

		$method->invokeArgs( $list_table, [ $review ] );

		$date_output = ob_get_clean();

		$submitted_on = sprintf(
			'%1$s at %2$s',
			get_comment_date( 'Y/m/d', $review ),
			get_comment_date( 'g:i a', $review )
		);

		$this->assertStringContainsString( $submitted_on, $date_output );

		if ( $has_product && $approved_flag ) {
			$this->assertStringContainsString( get_comment_link( $review ), $date_output );
		} else {
			$this->assertStringNotContainsString( get_comment_link( $review ), $date_output );
		}
	}

	/** @see test_column_date() */
	public function data_provider_test_column_date() {
		return [
			'No product' => [ false, 1 ],
			'Not approved' => [ true, 0 ],
			'Approved' => [ true, 1 ],
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

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_status_filters()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_status_filters() {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_status_filters' );
		$method->setAccessible( true );

		$this->assertSame(
			[
				'all'       => 'All',
				'moderated' => 'Pending',
				'approved'  => 'Approved',
				'spam'      => 'Spam',
				'trash'     => 'Trash',
			],
			$method->invoke( $list_table )
		);
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::convert_status_to_query_value()
	 * @dataProvider provider_convert_status_string_to_comment_approved
	 *
	 * @param string $status              Status to pass in to the method.
	 * @param string $expected_conversion Expected result.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_convert_status_string_to_comment_approved( string $status, string $expected_conversion ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'convert_status_to_query_value' );
		$method->setAccessible( true );

		$this->assertSame( $expected_conversion, $method->invoke( $list_table, $status ) );
	}

	/** @see test_convert_status_string_to_comment_approved */
	public function provider_convert_status_string_to_comment_approved() : Generator {
		yield 'spam' => [ 'spam', 'spam' ];
		yield 'trash' => [ 'trash', 'trash' ];
		yield 'moderated' => [ 'moderated', '0' ];
		yield 'approved' => [ 'approved', '1' ];
		yield 'all' => [ 'all', 'all' ];
		yield 'empty string' => [ '', 'all' ];
	}

}
