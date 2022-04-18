<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
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
	 * Returns a new instance of the {@see ReviewsListTable} class.
	 *
	 * @return ReviewsListTable
	 */
	private function get_reviews_list_table() : ReviewsListTable {
		return new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] );
	}

	/**
	 * Tests that can process the row output for a review or reply.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::single_row()
	 *
	 * @return void
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
	 * Tests that can handle the row actions for a review in the Reviews page table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::handle_row_actions()
	 * @dataProvider data_provider_test_handle_row_actions
	 *
	 * @param string $review_status  The review status.
	 * @param string $column_name    The current column name being output.
	 * @param string $primary_column The primary colum name.
	 * @param bool   $user_can_edit  Whether the current user can edit reviews.
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_handle_row_actions( $review_status, $column_name, $primary_column, $user_can_edit ) {
		global $comment_status;

		$comment_status = 'test'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$list_table = $this->get_reviews_list_table();
		$reflection = new ReflectionClass( $list_table );
		$method = $reflection->getMethod( 'handle_row_actions' );
		$method->setAccessible( true );
		$property = $reflection->getProperty( 'current_user_can_edit_review' );
		$property->setAccessible( true );

		$property->setValue( $list_table, $user_can_edit );

		$review = $this->factory()->comment->create_and_get(
			[
				'comment_approved' => $review_status,
			]
		);

		$actions = $method->invokeArgs( $list_table, [ $review, $column_name, $primary_column ] );

		if ( $column_name !== $primary_column || ! $user_can_edit ) {

			$this->assertEmpty( $actions );

		} else {

			if ( '1' === $review_status ) {
				$review_status = 'approved';
			} elseif ( '0' === $review_status ) {
				$review_status = 'unapproved';
			}

			if ( 'approved' === $review_status || 'unapproved' === $review_status ) {
				$this->assertStringContainsString( 'approved' === $review_status ? 'Unapprove' : 'Approve', $actions );
				$this->assertStringContainsString( 'Spam', $actions );
			} elseif ( 'spam' === $review_status ) {
				$this->assertStringContainsString( 'Not Spam', $actions );
			} elseif ( 'trash' === $review_status ) {
				$this->assertStringContainsString( 'Restore', $actions );
			}

			if ( 'trash' === $review_status || 'spam' === $review_status ) {
				$this->assertStringContainsString( 'Delete Permanently', $actions );
			} else {
				$this->assertStringContainsString( 'Trash', $actions );
			}
		}
	}

	/** @see test_handle_row_actions */
	public function data_provider_test_handle_row_actions() : Generator {
		yield 'Not the primary column'   => [ 'foo', 'bar', 'baz', true ];
		yield 'User cannot edit reviews' => [ 'test', 'foo', 'foo', false ];
		yield 'Approved review'          => [ '1', 'foo', 'foo', true ];
		yield 'Unapproved review'        => [ '0', 'foo', 'foo', true ];
		yield 'Trashed review'           => [ 'trash', 'foo', 'foo', true ];
		yield 'Spam review'              => [ 'spam', 'foo', 'foo', true ];
	}

	/**
	 * Tests that can get the product reviews' page columns.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_columns()
	 *
	 * @return void
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
	 * Tests that can get the product reviews' page columns when a filter is applied.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_columns()
	 *
	 * @return void
	 */
	public function test_get_columns_filtered() {
		$filter_callback = function( $columns ) {
			return [
				'custom_column' => 'Custom column',
			];
		};

		add_filter( 'woocommerce_product_reviews_table_columns', $filter_callback );

		$this->assertSame(
			[
				'custom_column' => 'Custom column',
			],
			$this->get_reviews_list_table()->get_columns()
		);

		remove_filter( 'woocommerce_product_reviews_table_columns', $filter_callback );
	}

	/**
	 * Tests that can get the primary column name.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_primary_column_name()
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_get_primary_column_name() {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_primary_column_name' );
		$method->setAccessible( true );

		$this->assertSame( 'comment', $method->invoke( $list_table ) );
	}

	/**
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::cb()
	 *
	 * @dataProvider data_provider_test_column_cb()
	 * @param bool   $current_user_can_edit Whether the current user has the capability to edit this review.
	 * @param string $expected_output The expected output.
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_cb( bool $current_user_can_edit, string $expected_output ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'column_cb' );
		$method->setAccessible( true );

		$property = ( new ReflectionClass( $list_table ) )->getProperty( 'current_user_can_edit_review' );
		$property->setAccessible( true );
		$property->setValue( $list_table, $current_user_can_edit );

		$review = $this->factory()->comment->create_and_get();

		$review->comment_ID = 123;

		ob_start();
		$method->invokeArgs( $list_table, [ $review ] );
		$output = trim( ob_get_clean() );

		$this->assertSame( $expected_output, $output );
	}

	/** @see test_column_cb() */
	public function data_provider_test_column_cb() {
		return [
			'user has the capability' => [
				true,
				'<label class="screen-reader-text" for="cb-select-123">Select review</label>
			<input
				id="cb-select-123"
				type="checkbox"
				name="delete_comments[]"
				value="123"
			/>',
			],
			'user does not have the capability' => [ false, '' ],
		];
	}

	/**
	 * Tests the output of the review type column.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_type()
	 * @dataProvider data_provider_test_column_type()
	 *
	 * @param string $comment_type The comment type (usually review or comment).
	 * @param string $expected_output The expected output.
	 * @return void
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
	 * Tests that can generate the column rating HTML output.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_rating()
	 * @dataProvider data_provider_test_column_rating()
	 *
	 * @param string $meta_value The comment meta value for rating.
	 * @param string $expected_output The expected output.
	 * @return void
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
	public function data_provider_test_column_rating() : array {
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
	 * @dataProvider data_provider_column_author
	 *
	 * @param bool $show_avatars          Value for the `show_avatars` option.
	 * @param bool $should_contain_avatar If the HTML should contain an avatar.
	 * @return void
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
	public function data_provider_column_author() : Generator {
		yield 'avatars disabled' => [ false, false ];
		yield 'avatars enabled'  => [ true, true ];
	}

	/**
	 * Tests that can get the item author URL.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_item_author_url()
	 * @dataProvider data_provider_test_get_item_author_url
	 *
	 * @param string $comment_author_url The comment author URL.
	 * @param string $expected_author_url The expected author URL.
	 * @return void
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
	 * @return void
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
	 * @return void
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
			'No product'   => [ false, 1 ],
			'Not approved' => [ true, 0 ],
			'Approved'     => [ true, 1 ],
		];
	}

	/**
	 * Tests that it will output the product information for the corresponding review column.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_response()
	 *
	 * @return void
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
	 * Tests that can output the review or reply content.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_comment()
	 *
	 * @return void
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
	 * @return void
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
	 * Tests that can get the bulk actions for the product reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_bulk_actions()
	 * @dataProvider data_provider_get_bulk_actions
	 *
	 * @param string $current_comment_status Currently set status.
	 * @param array  $expected_actions       Keys of the expected actions.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_bulk_actions( string $current_comment_status, array $expected_actions ) {
		$list_table = $this->get_reviews_list_table();
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
	public function data_provider_get_bulk_actions() : Generator {
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
	 * Tests that can set the product to filter reviews by.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::set_review_product()
	 *
	 * @return void
	 * @throws ReflectionException If the method or the property do not exist.
	 */
	public function test_set_review_product() {
		$list_table = $this->get_reviews_list_table();
		$reflection = new ReflectionClass( $list_table );
		$method = $reflection->getMethod( 'set_review_product' );
		$method->setAccessible( true );
		$property = $reflection->getProperty( 'current_product_for_reviews' );
		$property->setAccessible( true );

		$_REQUEST['product_id'] = 0;

		$method->invoke( $list_table );

		$this->assertNull( $property->getValue( $list_table ) );

		$product = WC_Helper_Product::create_simple_product( true );

		$_REQUEST['product_id'] = $product->get_id();

		$method->invoke( $list_table );

		$this->assertSame( $product->get_id(), $property->getValue( $list_table )->get_id() );

		WC_Helper_Product::delete_product( $product->get_id() );

		unset( $_REQUEST['product_id'] );
	}

	/**
	 * Tests that can set the review status for the current request.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::set_review_status()
	 * @dataProvider data_provider_set_review_status
	 *
	 * @param string|null $request_status          Status that's in the request.
	 * @param string      $expected_comment_status Expected value for the global variable.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_set_review_status( ?string $request_status, string $expected_comment_status ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'set_review_status' );
		$method->setAccessible( true );

		$_REQUEST['comment_status'] = $request_status;

		$method->invoke( $list_table );

		global $comment_status;

		$this->assertSame( $expected_comment_status, $comment_status );
	}

	/** @see test_set_review_status */
	public function data_provider_set_review_status() : Generator {
		yield 'not set'          => [ null, 'all' ];
		yield 'invalid status'   => [ 'invalid', 'all' ];
		yield 'moderated status' => [ 'moderated', 'moderated' ];
		yield 'all statuses'     => [ 'all', 'all' ];
		yield 'approved status'  => [ 'approved', 'approved' ];
		yield 'spam status'      => [ 'spam', 'spam' ];
		yield 'trash status'     => [ 'trash', 'trash' ];
	}

	/**
	 * Tests that can set the review type when preparing items.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::set_review_type()
	 * @dataProvider data_provider_set_review_type
	 *
	 * @param string $review_type          Review type.
	 * @param string $expected_review_type Expected review type to be set.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_set_review_type( $review_type, $expected_review_type ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'set_review_type' );
		$method->setAccessible( true );

		if ( null !== $review_type ) {
			$_REQUEST['review_type'] = $review_type;
		} else {
			unset( $_REQUEST['review_type'] );
		}

		$method->invoke( $list_table );

		global $comment_type;

		$this->assertSame( $expected_review_type, $comment_type );
	}

	/** @see test_set_review_type */
	public function data_provider_set_review_type() : Generator {
		yield 'Type not set' => [ null, null ];
		yield 'All types'    => [ 'all', null ];
		yield 'Replies'      => [ 'comment', 'comment' ];
		yield 'Reviews'      => [ 'review', 'review' ];
		yield 'Other'        => [ 'other', 'other' ];
	}

	/**
	 * Tests that can get the sortable columns for the reviews table.
	 *
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
	 * Tests that can get the sort arguments for the current request.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_sort_arguments()
	 * @dataProvider data_provider_get_sort_arguments
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

		if ( null !== $orderby ) {
			$_REQUEST['orderby'] = $orderby;
		} else {
			unset( $_REQUEST['orderby'] );
		}

		if ( null !== $order ) {
			$_REQUEST['order'] = $order;
		} else {
			unset( $_REQUEST['order'] );
		}

		$this->assertSame( $expected_args, $method->invoke( $list_table ) );
	}

	/** @see test_get_sort_arguments */
	public function data_provider_get_sort_arguments() : Generator {
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
	 * Tests that can get the comment type argument for the current request.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_filter_type_arguments()
	 * @dataProvider data_provider_get_filter_type_arguments
	 *
	 * @param string $review_type  The requested review type.
	 * @param string $comment_type The resulting comment type.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_filter_type_arguments( $review_type, $comment_type ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_filter_type_arguments' );
		$method->setAccessible( true );

		if ( null !== ( $review_type ) ) {
			$_REQUEST['review_type'] = $review_type;
		} else {
			unset( $_REQUEST['review_type'] );
		}

		$args = $method->invoke( $list_table );

		$this->assertSame( $comment_type, $args['type'] ?? null );
	}

	/** @see test_get_filter_type_arguments */
	public function data_provider_get_filter_type_arguments() : Generator {
		yield 'No requested type' => [ null, null ];
		yield 'All types'         => [ 'all', null ];
		yield 'Replies'           => [ 'comment', 'comment' ];
		yield 'Reviews'           => [ 'review', 'review' ];
		yield 'Other'             => [ 'other', 'other' ];
	}

	/**
	 * Tests that can set the filter rating for the current request.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_filter_rating_arguments()
	 *
	 * @return void
	 * @throws ReflectionException If reflected method or property don't exist.
	 */
	public function test_get_filter_rating_arguments() {
		$list_table = $this->get_reviews_list_table();
		$reflection = new ReflectionClass( $list_table );
		$method = $reflection->getMethod( 'get_filter_rating_arguments' );
		$method->setAccessible( true );
		$property = $reflection->getProperty( 'current_reviews_rating' );
		$property->setAccessible( true );

		$property->setValue( $list_table, 0 );

		$args = $method->invoke( $list_table );

		$this->assertSame( [], $args );

		$property->setValue( $list_table, 5 );

		$args = $method->invoke( $list_table );

		$this->assertEquals(
			[
				'meta_query' => [
					[
						'key'     => 'rating',
						'value'   => 5,
						'compare' => '=',
						'type'    => 'NUMERIC',
					],
				],
			],
			$args
		);
	}

	/**
	 * Tests that can get the post ID argument for the current request.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::get_filter_product_arguments()
	 *
	 * @return void
	 * @throws ReflectionException If the method or the property don't exist.
	 */
	public function test_get_filter_product_arguments() {
		$list_table = $this->get_reviews_list_table();
		$reflection = new ReflectionClass( $list_table );
		$method = $reflection->getMethod( 'get_filter_product_arguments' );
		$method->setAccessible( true );
		$property = $reflection->getProperty( 'current_product_for_reviews' );
		$property->setAccessible( true );

		$this->assertSame( [], $method->invoke( $list_table ) );

		$product = WC_Helper_Product::create_simple_product( true );

		$property->setValue( $list_table, $product );

		$this->assertSame( [ 'post_id' => $product->get_id() ], $method->invoke( $list_table ) );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Tests that can output the text for when no reviews are found.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::no_items()
	 * @dataProvider data_provider_no_items
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
	public function data_provider_no_items() : \Generator {
		yield 'moderated filter' => [ 'moderated', 'No reviews awaiting moderation.' ];
		yield 'no filter'        => [ '', 'No reviews found.' ];
		yield 'spam filter'      => [ 'spam', 'No reviews found.' ];
	}

	/**
	 * Tests that can render the extra controls for the product reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::extra_tablenav()
	 * @dataProvider data_provider_test_extra_tablenav()
	 *
	 * @param string   $position                  Position (top or bottom).
	 * @param bool     $has_items                 Whether the table has items.
	 * @param bool     $current_user_can_moderate Whether the current user has the capability to moderate comments.
	 * @param string   $status                    Filtered status.
	 * @param string   $expected_start            Output should start with this string.
	 * @param string[] $expected_elements         Output should contain these elements.
	 * @param string   $expected_end              Output should end with this string.
	 * @param string[] $not_expected_elements     Output should not contain these elements.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
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
	public function data_provider_test_extra_tablenav() : Generator {
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

	/**
	 * Tests that can output a filter by review type dropdown element.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::review_type_dropdown()
	 * @dataProvider data_provider_test_review_type_dropdown
	 *
	 * @param string $chosen_type The chosen review type to filter for.
	 * @return void
	 * @throws ReflectionException If the method is not defined.
	 */
	public function test_review_type_dropdown( $chosen_type ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'review_type_dropdown' );
		$method->setAccessible( true );

		ob_start();

		$method->invokeArgs( $list_table, [ $chosen_type ] );

		$output = ob_get_clean();

		$this->assertStringContainsString( '<label class="screen-reader-text" for="filter-by-review-type">Filter by review type</label>', $output );
		$this->assertStringContainsString( '<select id="filter-by-review-type" name="review_type">', $output );

		if ( ! in_array( $chosen_type, [ 'all', 'comment', 'review' ], true ) ) {
			$this->assertStringNotContainsString( '<option value="' . $chosen_type . '"  selected', $output );
		} else {
			$this->assertStringContainsString( '<option value="' . $chosen_type . '"  selected', $output );
		}
	}

	/** @see test_review_type_dropdown */
	public function data_provider_test_review_type_dropdown() : Generator {
		yield 'Unknown type' => [ 'invalid' ];
		yield 'All'          => [ 'all' ];
		yield 'Replies'      => [ 'comment' ];
		yield 'Reviews'      => [ 'review' ];
	}

	/**
	 * Tests that can output a filter dropdown for review ratings.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::review_rating_dropdown()
	 * @dataProvider data_provider_test_review_rating_dropdown
	 *
	 * @param string $chosen_rating The rating to filter reviews for.
	 * @return void
	 * @throws ReflectionException If the method is not defined.
	 */
	public function test_review_rating_dropdown( $chosen_rating ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'review_rating_dropdown' );
		$method->setAccessible( true );

		ob_start();

		$method->invokeArgs( $list_table, [ $chosen_rating ] );

		$output = ob_get_clean();

		$this->assertStringContainsString( '<label class="screen-reader-text" for="filter-by-review-rating">Filter by review rating</label>', $output );
		$this->assertStringContainsString( '<select id="filter-by-review-rating" name="review_rating">', $output );
		$this->assertStringContainsString( '<option value="' . $chosen_rating . '"  selected', $output );
	}

	/** @see test_review_type_dropdown */
	public function data_provider_test_review_rating_dropdown() : Generator {
		yield 'All ratings'    => [ 0 ];
		yield '1 star'         => [ 1 ];
		yield '2 stars'        => [ 2 ];
		yield '3 stars'        => [ 3 ];
		yield '4 stars'        => [ 4 ];
		yield '5 stars'        => [ 5 ];
	}

	/**
	 * Tests that can output the default column content.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::column_default()
	 * @dataProvider data_provider_column_default
	 *
	 * @param callable|null $hook_callback   Optional callback to add to the action.
	 * @param string        $expected_output Expected output from the method.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_column_default( ?callable $hook_callback, string $expected_output ) {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'column_default' );
		$method->setAccessible( true );

		$comment = new \WP_Comment(
			(object) [
				'comment_ID' => '123',
			]
		);

		if ( ! empty( $hook_callback ) ) {
			add_action( 'woocommerce_product_reviews_table_custom_column', $hook_callback, 10, 2 );
		} else {
			remove_all_actions( 'woocommerce_product_reviews_table_custom_column' );
		}

		ob_start();

		$method->invoke( $list_table, $comment, 'column-name' );

		$this->assertSame( $expected_output, ob_get_clean() );
	}

	/** @see test_column_default */
	public function data_provider_column_default() : Generator {
		yield 'no callback' => [ null, '' ];

		yield 'custom callback' => [
			'hook_callback' => static function ( $column_name, $review_id ) {
				echo 'Column name: ' . $column_name . ' for ID ' . $review_id . '.'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			},
			'expected_output' => 'Column name: column-name for ID 123.',
		];
	}

	/**
	 * Tests that can output a product search field for the product in context.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ReviewsListTable::product_search()
	 *
	 * @return void
	 * @throws ReflectionException If the method is not defined.
	 */
	public function test_product_search() {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'product_search' );
		$method->setAccessible( true );

		$product = WC_Helper_Product::create_simple_product( false );

		ob_start();

		$method->invokeArgs( $list_table, [ $product ] );

		$output = ob_get_clean();

		$this->assertStringContainsString( '<label class="screen-reader-text" for="filter-by-product">Filter by product</label>', $output );
		$this->assertStringContainsString( '<option value="' . $product->get_id() . '"', $output );
	}

}
