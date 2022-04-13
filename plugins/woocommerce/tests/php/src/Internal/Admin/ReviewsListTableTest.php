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

		$review = $this->factory()->comment->create_and_get(
			[
				'comment_type' => $comment_type,
			]
		);

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

		$review = $this->factory()->comment->create_and_get();

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
	 * Tests that it will output the product information for the corresponding review column.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_response()
	 *
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_response() {
		global $post;

		$product = $this->factory()->post->create_and_get(
			[
				'post_title' => 'Test product',
				'post_type'  => 'product',
			]
		);

		$post = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'column_response' );
		$method->setAccessible( true );

		ob_start();

		$method->invoke( $list_table );

		$product_output = ob_get_clean();

		$this->assertStringContainsString( 'Test product', $product_output );
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
	 * Tests that can output the review or reply content.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_comment()
	 *
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_comment() {

		$review = $this->factory()->comment->create_and_get(
			[
				'comment_content' => 'Test review',
				'comment_parent'  => 0,
			]
		);

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'column_comment' );
		$method->setAccessible( true );

		ob_start();

		$method->invokeArgs( $list_table, [ $review ] );

		$column_content = ob_get_clean();

		$this->assertStringNotContainsString( 'In reply to', $column_content );
		$this->assertStringContainsString( '<div class="comment-text">Test review</div>', $column_content );

		$reply = $this->factory()->comment->create_and_get(
			[
				'comment_content' => 'Test reply',
				'comment_parent'  => $review->comment_ID,
			]
		);

		ob_start();

		$method->invokeArgs( $list_table, [ $reply ] );

		$column_content = ob_get_clean();

		$this->assertStringContainsString( 'In reply to', $column_content );
		$this->assertStringContainsString( '<div class="comment-text">Test reply</div>', $column_content );
	}

	/**
	 * Tests that can get the in reply to review text message for the review content column.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_in_reply_to_review_text()
	 *
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_get_in_reply_to_review_text() {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_in_reply_to_review_text' );
		$method->setAccessible( true );

		$review = $this->factory()->comment->create_and_get(
			[
				'comment_parent' => 0,
			]
		);

		$output = $method->invokeArgs( $list_table, [ $review ] );

		$this->assertSame( '', $output );

		$reply = $this->factory()->comment->create_and_get(
			[
				'comment_parent' => $review->comment_ID,
			]
		);

		$output = $method->invokeArgs( $list_table, [ $reply ] );

		$this->assertSame( 'In reply to <a href="' . get_comment_link( $review ) . '">' . get_comment_author( $review ) . '</a>.', $output );
	}

	/**
	 * @dataProvider provider_get_bulk_actions
	 *
	 * @param string $current_comment_status Currently set status.
	 * @param array  $expected_actions       Keys of the expected actions.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_bulk_actions( string $current_comment_status, array $expected_actions ) {
		$list_table = new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] );
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_bulk_actions' );
		$method->setAccessible( true );

		global $comment_status;
		$comment_status = $current_comment_status; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

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

		yield 'approved status' => [
			'current_comment_status' => 'approved',
			'expected_actions' => [
				'unapprove',
				'spam',
				'trash',
			],
		];

		yield 'moderated status' => [
			'current_comment_status' => 'moderated',
			'expected_actions' => [
				'approve',
				'spam',
				'trash',
			],
		];

		yield 'trash status' => [
			'current_comment_status' => 'trash',
			'expected_actions' => [
				'spam',
				'untrash',
				'delete',
			],
		];

		yield 'spam status' => [
			'current_comment_status' => 'spam',
			'expected_actions' => [
				'unspam',
				'delete',
			],
		];
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::set_review_status()
	 * @dataProvider provider_set_review_status
	 *
	 * @param string|null $request_status          Status that's in the request.
	 * @param string      $expected_comment_status Expected value for the global variable.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_set_review_status( ?string $request_status, string $expected_comment_status ) {
		$list_table = new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] );
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'set_review_status' );
		$method->setAccessible( true );

		$_REQUEST['comment_status'] = $request_status;

		$method->invoke( $list_table );

		global $comment_status;

		$this->assertSame( $expected_comment_status, $comment_status );
	}

	/** @see test_set_review_status */
	public function provider_set_review_status() : Generator {
		yield 'not set' => [ null, 'all' ];
		yield 'invalid status' => [ 'invalid', 'all' ];
		yield 'moderated status' => [ 'moderated', 'moderated' ];
		yield 'all status' => [ 'all', 'all' ];
		yield 'approved status' => [ 'approved', 'approved' ];
		yield 'spam status' => [ 'spam', 'spam' ];
		yield 'trash status' => [ 'trash', 'trash' ];
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

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::no_items()
	 * @dataProvider provider_no_items
	 *
	 * @param string $status   Filtered status.
	 * @param string $expected Expected text.
	 * @return void
	 */
	public function test_no_items( string $status, string $expected ) {
		global $comment_status;
		$comment_status = $status; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		ob_start();

		$this->get_reviews_list_table()->no_items();

		$this->assertSame( $expected, ob_get_clean() );
	}

	/** @see test_no_items */
	public function provider_no_items() : \Generator {
		yield 'moderated filter' => [ 'moderated', 'No reviews awaiting moderation.' ];
		yield 'no filter' => [ '', 'No reviews found.' ];
		yield 'spam filter' => [ 'spam', 'No reviews found.' ];
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::extra_tablenav()
	 * @dataProvider provider_test_extra_tablenav()
	 *
	 * @param string   $position                  Position (top or bottom).
	 * @param bool     $has_items                 Whether the table has items.
	 * @param bool     $current_user_can_moderate Whether the current user has the capability to moderate comments.
	 * @param string   $status                    Filtered status.
	 * @param string   $expected_start            Output should start with this string.
	 * @param string[] $expected_elements         Output should contain these elements.
	 * @param string   $expected_end              Output should end with this string.
	 * @param string[] $not_expected_elements     Output should not contain these elements.
	 */
	public function test_extra_tablenav( string $position, bool $has_items, bool $current_user_can_moderate, string $status, string $expected_start, array $expected_elements, string $expected_end, array $not_expected_elements ) {
		global $comment_status;
		$comment_status = $status; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'extra_tablenav' );
		$method->setAccessible( true );

		$review = $this->factory()->comment->create_and_get();
		$property = ( new ReflectionClass( $list_table ) )->getProperty( 'items' );
		$property->setAccessible( true );
		$property->setValue( $list_table, $has_items ? [ $review ] : [] );

		$property = ( new ReflectionClass( $list_table ) )->getProperty( 'current_user_can_moderate_reviews' );
		$property->setAccessible( true );
		$property->setValue( $list_table, $current_user_can_moderate );

		ob_start();

		$method->invokeArgs( $list_table, [ $position ] );

		$output = ob_get_clean();

		$this->assertStringStartsWith( $expected_start, $output );

		foreach ( $expected_elements as $element ) {
			$this->assertStringContainsString( $element, $output );
		}

		foreach ( $not_expected_elements as $element ) {
			$this->assertStringNotContainsString( $element, $output );
		}

		$this->assertStringEndsWith( $expected_end, $output );
	}

	/** @see test_extra_tablenav() */
	public function provider_test_extra_tablenav() : Generator {
		yield 'no items top' => [
			'position' => 'top',
			'has_items' => false,
			'current_user_can_moderate' => true,
			'status' => '',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [],
			'expected_end' => '</div>',
			'not_expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply"',
			],
		];

		yield 'no items bottom' => [
			'position' => 'bottom',
			'has_items' => false,
			'current_user_can_moderate' => true,
			'status' => '',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [],
			'expected_end' => '</div>',
			'not_expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply"',
			],
		];

		yield 'unfiltered with items top' => [
			'position' => 'top',
			'has_items' => true,
			'current_user_can_moderate' => true,
			'status' => '',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
			],
			'expected_end' => '</div>',
			'not_expected_elements' => [
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply"',
			],
		];

		yield 'unfiltered with items bottom' => [
			'position' => 'bottom',
			'has_items' => true,
			'current_user_can_moderate' => true,
			'status' => '',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [],
			'expected_end' => '</div>',
			'not_expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply"',
			],
		];

		yield 'spam with items top' => [
			'position' => 'top',
			'has_items' => true,
			'current_user_can_moderate' => true,
			'status' => 'spam',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply" value="Empty Spam"',
			],
			'expected_end' => '</div>',
			'not_expected_elements' => [],
		];

		yield 'spam with items bottom' => [
			'position' => 'bottom',
			'has_items' => true,
			'current_user_can_moderate' => true,
			'status' => 'spam',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply" value="Empty Spam"',
			],
			'expected_end' => '</div>',
			'not_expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
			],
		];

		yield 'trash with items top' => [
			'position' => 'top',
			'has_items' => true,
			'current_user_can_moderate' => true,
			'status' => 'trash',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply" value="Empty Trash"',
			],
			'expected_end' => '</div>',
			'not_expected_elements' => [],
		];

		yield 'trash with items bottom' => [
			'position' => 'bottom',
			'has_items' => true,
			'current_user_can_moderate' => true,
			'status' => 'trash',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply" value="Empty Trash"',
			],
			'expected_end' => '</div>',
			'not_expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
			],
		];

		yield 'trash with items top and user cannot moderate' => [
			'position' => 'top',
			'has_items' => true,
			'current_user_can_moderate' => false,
			'status' => 'trash',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
			],
			'expected_end' => '</div>',
			'not_expected_elements' => [
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply"',
			],
		];

		yield 'trash with items bottom and user cannot moderate' => [
			'position' => 'bottom',
			'has_items' => true,
			'current_user_can_moderate' => false,
			'status' => 'trash',
			'expected_start' => '<div class="alignleft actions">',
			'expected_elements' => [],
			'expected_end' => '</div>',
			'not_expected_elements' => [
				'<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter"',
				'<input type="hidden" id="_destroy_nonce" name="_destroy_nonce"',
				'<input type="hidden" name="_wp_http_referer"',
				'<input type="submit" name="delete_all" id="delete_all" class="button apply"',
			],
		];
	}

}
