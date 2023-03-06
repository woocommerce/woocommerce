<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\ProductReviews;

use Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable;
use Generator;
use ReflectionClass;
use ReflectionException;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests that product reviews page handler.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable
 */
class ReviewsListTableTest extends WC_Unit_Test_Case {

	/**
	 * Returns a new instance of the {@see \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable} class.
	 *
	 * @return ReviewsListTable
	 */
	private function get_reviews_list_table() : ReviewsListTable {
		return new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] );
	}

	/**
	 * @testdox `display` outputs the overall HTML content of product reviews list table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::display()
	 *
	 * @return void
	 */
	public function test_display() : void {
		$this->factory()->comment->create_many( 2 );

		ob_start();

		$this->get_reviews_list_table()->display();

		$output = ob_get_clean();

		$this->assertStringContainsString( '<table class="wp-list-table', $output );
		$this->assertStringContainsString( '<tbody id="the-comment-list" data-wp-lists="list:comment">', $output );
	}

	/**
	 * @testdox `single_row` will process the row output content for an individual review or reply in the product reviews page table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::single_row()
	 *
	 * @return void
	 */
	public function test_single_row() : void {
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
	 * @testdox `handle_row_actions` displays admin action links pertaining each review or comment.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::handle_row_actions()
	 * @dataProvider data_provider_test_handle_row_actions
	 *
	 * @param string $review_status  The review status.
	 * @param string $column_name    The current column name being output.
	 * @param string $primary_column The primary colum name.
	 * @param bool   $user_can_edit  Whether the current user can edit reviews.
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_handle_row_actions( string $review_status, string $column_name, string $primary_column, bool $user_can_edit ) : void {
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

			// Should not contain any tags with _only_ a pipe separator, but no label.
			$this->assertStringNotContainsString( '> | </span>', $actions );
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
	 * @testdox `get_columns` returns the product reviews page table column names and labels.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_columns()
	 *
	 * @return void
	 */
	public function test_get_columns() : void {
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
	 * @testdox `get_columns` returns columns for the product reviews page that can be filtered.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_columns()
	 *
	 * @return void
	 */
	public function test_get_columns_filtered() : void {
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
	 * @testdox `get_primary_column_name` will return the `comment` column as the primary column of the product reviews page list table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_primary_column_name()
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_get_primary_column_name() : void {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_primary_column_name' );
		$method->setAccessible( true );

		$this->assertSame( 'comment', $method->invoke( $list_table ) );
	}

	/**
	 * @testodx `cb` outputs the WordPress standard checkbox HTML for the product reviews page list table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::cb()
	 *
	 * @dataProvider data_provider_test_column_cb()
	 * @param bool   $current_user_can_edit Whether the current user has the capability to edit this review.
	 * @param string $expected_output The expected output.
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_cb( bool $current_user_can_edit, string $expected_output ) : void {
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
	public function data_provider_test_column_cb() : array {
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
	 * @testdox `column_type` outputs the HTML column content displaying the type of product review in the product reviews list table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::column_type()
	 * @dataProvider data_provider_test_column_type()
	 *
	 * @param string $comment_type The comment type (usually review or comment).
	 * @param string $expected_output The expected output.
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_type( string $comment_type, string $expected_output ) : void {
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
	public function data_provider_test_column_type() : array {
		return [
			'review' => [ 'review', '&#9734;&nbsp;Review' ],
			'reply' => [ 'comment', 'Reply' ],
			'default to reply' => [ 'anything', 'Reply' ],
		];
	}

	/**
	 * @testdox `column_rating` outputs the HTML column content displaying the review rating in the product reviews list table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::column_rating()
	 * @dataProvider data_provider_test_column_rating()
	 *
	 * @param string $meta_value The comment meta value for rating.
	 * @param string $expected_output The expected output.
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_rating( string $meta_value, string $expected_output ) : void {

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
	 * @testdox `column_author` outputs the HTML column content displaying the review author in the product reviews list table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::column_author()
	 * @dataProvider data_provider_column_author
	 *
	 * @param bool $show_avatars          Value for the `show_avatars` option.
	 * @param bool $should_contain_avatar If the HTML should contain an avatar.
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_author( bool $show_avatars, bool $should_contain_avatar ) : void {
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
	 * @testdox `get_item_author_url` returns the URL of the review author as output by {@see get_comment_author_url()}.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_item_author_url()
	 * @dataProvider data_provider_test_get_item_author_url
	 *
	 * @param string $comment_author_url The comment author URL.
	 * @param string $expected_author_url The expected author URL.
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_get_item_author_url( string $comment_author_url, string $expected_author_url ) : void {
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
	public function data_provider_test_get_item_author_url() : array {
		return [
			'No URL' => [ '', '' ],
			'Empty URL (http)' => [ 'http://', '' ],
			'Empty URL (https)' => [ 'https://', '' ],
			'Valid URL' => [ 'https://example.com', 'https://example.com' ],
		];
	}

	/**
	 * @testdox `get_item_author_url_for_display` will build the HTML output for the product review author URL when available.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_item_author_url_for_display()
	 * @dataProvider data_provider_test_get_item_author_url_for_display()
	 *
	 * @param string $author_url The author URL.
	 * @param string $author_url_for_display The author URL for display.
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_get_item_author_url_for_display( string $author_url, string $author_url_for_display ) : void {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_item_author_url_for_display' );
		$method->setAccessible( true );

		$this->assertSame( $author_url_for_display, $method->invokeArgs( $list_table, [ $author_url ] ) );
	}

	/** @see test_get_item_author_url_for_display() */
	public function data_provider_test_get_item_author_url_for_display() : array {
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
	 * @testdox `column_date` outputs the date HTML for the current review in the product reviews page table as output by {@see get_comment_date()}.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::column_date()
	 * @dataProvider data_provider_test_column_date
	 *
	 * @param bool $has_product   Whether the review is for a valid product object.
	 * @param int  $approved_flag The review (comment) approved flag.
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_date( bool $has_product, int $approved_flag ) : void {
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
	public function data_provider_test_column_date() : array {
		return [
			'No product'   => [ false, 1 ],
			'Not approved' => [ true, 0 ],
			'Approved'     => [ true, 1 ],
		];
	}

	/**
	 * @testdox `column_response` outputs the response HTML for the current review in the product reviews page table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::column_response()
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_response() : void {
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

		$method->invokeArgs( $list_table, [ null ] );

		$product_output = ob_get_clean();

		$this->assertStringContainsString( 'Test product', $product_output );
	}

	/**
	 * @testdox `column_content` outputs the comment content HTML for the current review in the product reviews page table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::column_comment()
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_column_comment() : void {

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
	 * @testdox `get_in_reply_to_review_text` returns the in-reply-to-review text message for the review content column.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_in_reply_to_review_text()
	 *
	 * @return void
	 * @throws ReflectionException If the method does not exist.
	 */
	public function test_get_in_reply_to_review_text() : void {
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
	 * @testdox `get_bulk_actions` returns the bulk actions available in the product reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_bulk_actions()
	 * @dataProvider data_provider_get_bulk_actions
	 *
	 * @param string $current_comment_status Currently set status.
	 * @param array  $expected_actions       Keys of the expected actions.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_bulk_actions( string $current_comment_status, array $expected_actions ) : void {
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
	 * @testdox `set_review_product` will set a given product post in the comments query when filtering reviews by product.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::set_review_product()
	 *
	 * @return void
	 * @throws ReflectionException If the method or the property do not exist.
	 */
	public function test_set_review_product() : void {
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
	 * @testdox `set_review_status` will set the comment status in the comment query to filter results accordingly in the product reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::set_review_status()
	 * @dataProvider data_provider_set_review_status
	 *
	 * @param string|null $request_status          Status that's in the request.
	 * @param string      $expected_comment_status Expected value for the global variable.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_set_review_status( ?string $request_status, string $expected_comment_status ) : void {
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
	 * @testdox `set_review_type` will set the review type (e.g. review, reply) in the comments query to filter results in the product reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::set_review_type()
	 * @dataProvider data_provider_set_review_type
	 *
	 * @param string|null $review_type          Review type.
	 * @param string|null $expected_review_type Expected review type to be set.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_set_review_type( ?string $review_type, ?string $expected_review_type ) : void {
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
	 * @testdox `get_sortable_columns` returns a list of columns that can be sorted in the product reviews page table.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_sortable_columns()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_sortable_columns() : void {
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
	 * @testdox `get_sort_arguments` will grab any sort arguments in the current request and return normalized comment query sort arguments for product reviews.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_sort_arguments()
	 * @dataProvider data_provider_get_sort_arguments
	 *
	 * @param string|null $orderby       The orderby value that's set in the request.
	 * @param string|null $order         The order value that's set in the request.
	 * @param array       $expected_args Expected arguments.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_sort_arguments( ?string $orderby, ?string $order, array $expected_args ) : void {
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
	 * @testdox `get_filter_type_arguments` will grab any review type arguments for the current request and return normalized comment query arguments for filtering product reviews by type.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_filter_type_arguments()
	 * @dataProvider data_provider_get_filter_type_arguments
	 *
	 * @param string|null $review_type  The requested review type.
	 * @param string|null $comment_type The resulting comment type.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_filter_type_arguments( ?string $review_type, ?string $comment_type ) : void {
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
	 * @testdox `get_filter_rating_arguments` will grab any review rating argument for the current request and return normalized comment query arguments for filtering product reviews by rating.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_filter_rating_arguments()
	 *
	 * @return void
	 * @throws ReflectionException If reflected method or property don't exist.
	 */
	public function test_get_filter_rating_arguments() : void {
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
	 * @testdox `get_filter_product_arguments` will grab any product ID for the current request and return a normalized comment query argument for filtering product reviews by product.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_filter_product_arguments()
	 *
	 * @return void
	 * @throws ReflectionException If the method or the property don't exist.
	 */
	public function test_get_filter_product_arguments() : void {
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
	 * @testdox `get_status_arguments` will grab any status for the current request and return a normalized comment query argument for filtering product reviews by status.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_status_arguments()
	 * @dataProvider provider_get_status_arguments
	 *
	 * @param string $status        Current status for the request.
	 * @param array  $expected_args Expected result of the method.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_status_arguments( string $status, array $expected_args ) : void {
		global $comment_status;
		$comment_status = $status; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_status_arguments' );
		$method->setAccessible( true );

		$this->assertSame( $expected_args, $method->invoke( $list_table ) );
	}

	/** @see test_get_status_arguments */
	public function provider_get_status_arguments() : Generator {
		yield 'all statuses' => [ 'all', [] ];
		yield 'moderated status' => [ 'moderated', [ 'status' => '0' ] ];
		yield 'approved status' => [ 'approved', [ 'status' => '1' ] ];
		yield 'spam status' => [ 'spam', [ 'status' => 'spam' ] ];
		yield 'trash status' => [ 'trash', [ 'status' => 'trash' ] ];
		yield 'invalid status' => [ 'not-valid', [] ];
	}

	/**
	 * @testdox `get_search_arguments` will grab any search term for the current request and return a normalized comment query argument for filtering product reviews by that term.
	 *
	 * @covers       \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_search_arguments()
	 * @dataProvider provider_get_search_arguments
	 *
	 * @param mixed $search_value  Current search value in the request.
	 * @param array $expected_args Expected result of the method.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_search_arguments( $search_value, array $expected_args ) : void {
		$_REQUEST['s'] = $search_value;

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_search_arguments' );
		$method->setAccessible( true );

		$this->assertSame( $expected_args, $method->invoke( $list_table ) );
	}

	/** @see test_get_search_arguments */
	public function provider_get_search_arguments() : Generator {
		yield 'no search' => [ null, [] ];
		yield 'search value' => [ 'test', [ 'search' => 'test' ] ];
	}

	/**
	 * @testdox `no_items` returns custom text when no reviews are found for a given request.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::no_items()
	 * @dataProvider data_provider_no_items
	 *
	 * @param string $status   Filtered status.
	 * @param string $expected Expected text.
	 *
	 * @return void
	 */
	public function test_no_items( string $status, string $expected ) : void {
		global $comment_status;
		$comment_status = $status; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		ob_start();

		$this->get_reviews_list_table()->no_items();

		$this->assertSame( $expected, ob_get_clean() );
	}

	/** @see test_no_items */
	public function data_provider_no_items() : Generator {
		yield 'moderated filter' => [ 'moderated', 'No reviews awaiting moderation.' ];
		yield 'no filter'        => [ '', 'No reviews found.' ];
		yield 'spam filter'      => [ 'spam', 'No reviews found.' ];
	}

	/**
	 * @testdox `extra_tablenav` will offer additional UI elements allowing merchants to filter the reviews to display.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::extra_tablenav()
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
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_extra_tablenav( string $position, bool $has_items, bool $current_user_can_moderate, string $status, string $expected_start, array $expected_elements, string $expected_end, array $not_expected_elements ) : void {
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
	 * @testdox `review_type_dropdown` outputs a dropdown HTML to filter reviews by type in the product reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::review_type_dropdown()
	 * @dataProvider data_provider_test_review_type_dropdown
	 *
	 * @param string $chosen_type The chosen review type to filter for.
	 * @return void
	 * @throws ReflectionException If the method is not defined.
	 */
	public function test_review_type_dropdown( string $chosen_type ) : void {
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
	 * @testdox `review_rating_dropdown` outputs a dropdown HTML to filter reviews by rating in the product reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::review_rating_dropdown()
	 * @dataProvider data_provider_test_review_rating_dropdown
	 *
	 * @param int $chosen_rating The rating to filter reviews for.
	 *
	 * @return void
	 * @throws ReflectionException If the method is not defined.
	 */
	public function test_review_rating_dropdown( int $chosen_rating ) : void {
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
	 * @testdox `column_default` will render any custom columns HTML in the product reviews page added by third parties.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::column_default()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_column_default() : void {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'column_default' );
		$method->setAccessible( true );

		$review = $this->factory()->comment->create_and_get();
		$callback = function( $review ) {
			echo 'Custom content for "custom_column" for ID ' . esc_html( $review->comment_ID );
		};

		add_action( 'woocommerce_product_reviews_table_column_custom_column', $callback, 10, 2 );

		ob_start();

		$method->invokeArgs( $list_table, [ $review, 'custom_column' ] );

		$this->assertSame( 'Custom content for "custom_column" for ID ' . $review->comment_ID, ob_get_clean() );

		remove_action( 'woocommerce_product_reviews_table_column_custom_column', $callback );
	}

	/**
	 * @testdox `filter_column_output` allows columns in the product reviews page table to be filtered by third parties.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::filter_column_output()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_filter_column_output() : void {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'filter_column_output' );
		$method->setAccessible( true );

		$review = $this->factory()->comment->create_and_get();
		$callback = function( $content, $review ) {
			return 'Additional content to "' . $content . '" for test column belonging to review with ID: ' . esc_html( $review->comment_ID );
		};

		add_filter( 'woocommerce_product_reviews_table_column_test_content', $callback, 10, 2 );

		$output = $method->invokeArgs( $list_table, [ 'test', 'test content', $review ] );

		$this->assertSame( 'Additional content to "test content" for test column belonging to review with ID: ' . (string) $review->comment_ID, $output );

		remove_filter( 'woocommerce_product_reviews_table_column_test_content', $callback );
	}

	/**
	 * @testdox `product_search` will render the product search form in the product reviews page to filter reviews for the given product.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::product_search()
	 *
	 * @return void
	 * @throws ReflectionException If the method is not defined.
	 */
	public function test_product_search() : void {
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

	/**
	 * @testdox `get_status_filters` will return the status filters for the product reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_status_filters()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_status_filters() : void {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_status_filters' );
		$method->setAccessible( true );

		$this->assertSame(
			[
				'all'       => [
					'All <span class="count">(%s)</span>',
					'All <span class="count">(%s)</span>',
					'product reviews',
					'singular' => 'All <span class="count">(%s)</span>',
					'plural'   => 'All <span class="count">(%s)</span>',
					'context'  => 'product reviews',
					'domain'   => 'woocommerce',
				],
				'moderated' => [
					'Pending <span class="count">(%s)</span>',
					'Pending <span class="count">(%s)</span>',
					'product reviews',
					'singular' => 'Pending <span class="count">(%s)</span>',
					'plural'   => 'Pending <span class="count">(%s)</span>',
					'context'  => 'product reviews',
					'domain'   => 'woocommerce',
				],
				'approved'  => [
					'Approved <span class="count">(%s)</span>',
					'Approved <span class="count">(%s)</span>',
					'product reviews',
					'singular' => 'Approved <span class="count">(%s)</span>',
					'plural'   => 'Approved <span class="count">(%s)</span>',
					'context'  => 'product reviews',
					'domain'   => 'woocommerce',
				],
				'spam'      => [
					'Spam <span class="count">(%s)</span>',
					'Spam <span class="count">(%s)</span>',
					'product reviews',
					'singular' => 'Spam <span class="count">(%s)</span>',
					'plural'   => 'Spam <span class="count">(%s)</span>',
					'context'  => 'product reviews',
					'domain'   => 'woocommerce',
				],
				'trash'     => [
					'Trash <span class="count">(%s)</span>',
					'Trash <span class="count">(%s)</span>',
					'product reviews',
					'singular' => 'Trash <span class="count">(%s)</span>',
					'plural'   => 'Trash <span class="count">(%s)</span>',
					'context'  => 'product reviews',
					'domain'   => 'woocommerce',
				],
			],
			$method->invoke( $list_table )
		);
	}

	/**
	 * @testodx `get_view_url` returns the admin URL for the product reviews page, which may include a specific type or product ID to filter default results for.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_view_url()
	 * @dataProvider provider_get_view_url
	 *
	 * @param string $comment_type Current type filter.
	 * @param int    $post_id      Current post ID filter.
	 * @param string $expected     Expected URL from the method.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_view_url( string $comment_type, int $post_id, string $expected ) : void {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_view_url' );
		$method->setAccessible( true );

		$this->assertSame(
			$expected,
			$method->invoke( $list_table, $comment_type, $post_id )
		);
	}

	/** @see test_get_view_url */
	public function provider_get_view_url() : Generator {
		yield 'empty type, empty post ID' => [
			'comment_type' => '',
			'post_id'      => 0,
			'expected'     => 'http://example.org/wp-admin/edit.php?post_type=product&page=product-reviews',
		];

		yield 'review type, empty post ID' => [
			'comment_type' => 'review',
			'post_id'      => 0,
			'expected'     => 'http://example.org/wp-admin/edit.php?post_type=product&page=product-reviews&comment_type=review',
		];

		yield 'reply type, with post ID' => [
			'comment_type' => 'reply',
			'post_id'      => 123,
			'expected'     => 'http://example.org/wp-admin/edit.php?post_type=product&page=product-reviews&comment_type=reply&p=123',
		];

		yield 'all type, with post ID' => [
			'comment_type' => 'all',
			'post_id'      => 123,
			'expected'     => 'http://example.org/wp-admin/edit.php?post_type=product&page=product-reviews&p=123',
		];
	}

	/**
	 * @testdox `convert_status_to_query_value` normalizes a review status to a query value for filtering results in the product reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::convert_status_to_query_value()
	 * @dataProvider provider_convert_status_string_to_comment_approved
	 *
	 * @param string $status              Status to pass in to the method.
	 * @param string $expected_conversion Expected result.
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_convert_status_string_to_comment_approved( string $status, string $expected_conversion ) : void {
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

	/**
	 * @testdox `get_review_count` gets the number of reviews of a given status for a given product.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_review_count()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_review_count() : void {
		// Add a normal post with some comments -- these should not appear in our counts.
		$post_id = $this->factory()->post->create(
			[
				'post_type' => 'post',
			]
		);
		$this->factory()->comment->create_many(
			3,
			[
				'comment_post_ID'  => $post_id,
			]
		);

		// Now add a product with a bunch of reviews -- these _should_ appear in our counts.
		$product_id = $this->factory()->post->create(
			[
				'post_type' => 'product',
			]
		);

		// 3 moderated comments.
		$this->factory()->comment->create_many(
			3,
			[
				'comment_type'     => 'comment',
				'comment_post_ID'  => $product_id,
				'comment_approved' => '0',
			]
		);

		// 2 approved comments.
		$this->factory()->comment->create_many(
			2,
			[
				'comment_type'     => 'review',
				'comment_post_ID'  => $product_id,
				'comment_approved' => '1',
			]
		);

		// 1 trashed comment.
		$this->factory()->comment->create(
			[
				'comment_type'     => 'review',
				'comment_post_ID'  => $product_id,
				'comment_approved' => 'trash',
			]
		);

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_review_count' );
		$method->setAccessible( true );

		// Should have 3 moderated.
		$this->assertSame( 3, $method->invoke( $list_table, 'moderated', 0 ) );

		// Should have 2 approved.
		$this->assertSame( 2, $method->invoke( $list_table, 'approved', 0 ) );

		// Should have 1 trashed.
		$this->assertSame( 1, $method->invoke( $list_table, 'trash', 0 ) );

		// Should have 5 in total (trash is not included in "all").
		$this->assertSame( 5, $method->invoke( $list_table, 'all', 0 ) );

		// Should also have 5 in total when filtering by product ID (trash is not included in "all").
		$this->assertSame( 5, $method->invoke( $list_table, 'all', $product_id ) );
	}

	/**
	 * @testdox `get_views` Returns a list of comment status links that point to matching views.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_views()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_views() : void {
		global $comment_status;
		$comment_status = 'all'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_views' );
		$method->setAccessible( true );

		$this->assertSame(
			[
				'all'       => '<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;comment_type=other&#038;comment_status=all" class="current" aria-current="page">All <span class="count">(<span class="all-count">0</span>)</span></a>',
				'moderated' => '<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;comment_type=other&#038;comment_status=moderated">Pending <span class="count">(<span class="pending-count">0</span>)</span></a>',
				'approved'  => '<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;comment_type=other&#038;comment_status=approved">Approved <span class="count">(<span class="approved-count">0</span>)</span></a>',
				'spam'      => '<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;comment_type=other&#038;comment_status=spam">Spam <span class="count">(<span class="spam-count">0</span>)</span></a>',
				'trash'     => '<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;comment_type=other&#038;comment_status=trash">Trash <span class="count">(<span class="trash-count">0</span>)</span></a>',
			],
			$method->invoke( $list_table )
		);
	}

	/**
	 * @testdox `get_offset_arguments` ensures that the current comment query in the product reviews page displays the expected results according to pagination.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_offset_arguments()
	 * @dataProvider provider_get_offset_arguments
	 *
	 * @param mixed    $request_start_value `$_REQUEST['start']` value.
	 * @param int|null $current_page_number Current page number (used if `$_REQUEST['start']` isn't set).
	 * @param array    $expected_args       Expected result of the method.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_offset_arguments( $request_start_value, ?int $current_page_number, array $expected_args ) : void {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_offset_arguments' );
		$method->setAccessible( true );

		if ( ! is_null( $request_start_value ) ) {
			$_REQUEST['start'] = $request_start_value;
		} else {
			unset( $_REQUEST['start'] );
		}

		$_REQUEST['paged'] = $current_page_number;

		$this->assertSame( $expected_args, $method->invoke( $list_table ) );
	}

	/** @see test_get_offset_arguments */
	public function provider_get_offset_arguments() : Generator {
		yield 'start value has offset' => [ 5, null, [ 'offset' => 5 ] ];
		yield 'start value set, but empty string' => [ '', null, [ 'offset' => 0 ] ];
		yield 'page 1' => [ null, 1, [ 'offset' => 0 ] ];
		yield 'page 2' => [ null, 2, [ 'offset' => 20 ] ];
	}

	/**
	 * @testdox `get_total_comments_arguments` will return the product reviews total count.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_total_comments_arguments()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_total_comments_arguments() : void {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_total_comments_arguments' );
		$method->setAccessible( true );

		$default_query_args = [
			'offset'  => 20,
			'number'  => 20,
			'status'  => '1',
			'post_id' => 5,
		];

		$this->assertSame(
			[
				'offset'  => 0,
				'number'  => 0,
				'status'  => '1',
				'post_id' => 5,
				'count'   => true,
			],
			$method->invoke( $list_table, $default_query_args )
		);
	}

	/**
	 * @testdox `get_per_page` will return the number of product reviews to display per page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::get_per_page()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_per_page() {
		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'get_per_page' );
		$method->setAccessible( true );

		$this->assertSame( 20, $method->invoke( $list_table ) );
	}

	/**
	 * @testdox `comments_bubble` displays a bubble with information about pending and approved reviews for the corresponding product.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::comments_bubble()
	 * @dataProvider provider_comments_bubble
	 *
	 * @param int    $approved_review_count Number of approved reviews on the product.
	 * @param int    $pending_review_count  Number of pending reviews on the product.
	 * @param bool   $product_is_trashed    If the product has the "trash" status.
	 * @param string $expected_html         Expected result of the method. If a product ID is expected then that will be
	 *                                      in this string as `PRODUCT_ID` and we'll replace it after creating the
	 *                                      product object.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_comments_bubble( int $approved_review_count, int $pending_review_count, bool $product_is_trashed, string $expected_html ) : void {
		global $post;

		$product_id = $this->factory()->post->create( [ 'post_type' => 'product' ] );

		if ( $product_is_trashed ) {
			wp_trash_post( $product_id );
		}

		if ( $approved_review_count > 0 ) {
			$this->factory()->comment->create_many(
				$approved_review_count,
				[
					'comment_type'     => 'review',
					'comment_post_ID'  => $product_id,
					'comment_approved' => '1',
				]
			);
		}

		$post = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$list_table = $this->get_reviews_list_table();
		$method = ( new ReflectionClass( $list_table ) )->getMethod( 'comments_bubble' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $list_table, $product_id, $pending_review_count );
		$actual_html = ob_get_clean();

		$this->assertSame( str_replace( 'PRODUCT_ID', $product_id, $expected_html ), $actual_html );
	}

	/** @see test_comments_bubble */
	public function provider_comments_bubble(): Generator {
		yield 'no reviews' => [
			'approved_review_count' => 0,
			'pending_review_count' => 0,
			'product_is_trashed' => false,
			'expected_html' => '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">No reviews</span><span class="post-com-count post-com-count-pending post-com-count-no-pending"><span class="comment-count comment-count-no-pending" aria-hidden="true">0</span><span class="screen-reader-text">No reviews</span></span>',
		];

		yield 'approved reviews only' => [
			'approved_review_count' => 2,
			'pending_review_count' => 0,
			'product_is_trashed' => false,
			'expected_html' => '<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;product_id=PRODUCT_ID&#038;comment_status=approved" class="post-com-count post-com-count-approved"><span class="comment-count-approved" aria-hidden="true">2</span><span class="screen-reader-text">2 reviews</span></a><span class="post-com-count post-com-count-pending post-com-count-no-pending"><span class="comment-count comment-count-no-pending" aria-hidden="true">0</span><span class="screen-reader-text">No pending reviews</span></span>',
		];

		yield 'approved and pending reviews' => [
			'approved_review_count' => 1,
			'pending_review_count' => 1,
			'product_is_trashed' => false,
			'expected_html' => '<a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;product_id=PRODUCT_ID&#038;comment_status=approved" class="post-com-count post-com-count-approved"><span class="comment-count-approved" aria-hidden="true">1</span><span class="screen-reader-text">1 approved review</span></a><a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;product_id=PRODUCT_ID&#038;comment_status=moderated" class="post-com-count post-com-count-pending"><span class="comment-count-pending" aria-hidden="true">1</span><span class="screen-reader-text">1 pending review</span></a>',
		];

		yield 'pending reviews only' => [
			'approved_review_count' => 0,
			'pending_review_count' => 2,
			'product_is_trashed' => false,
			'expected_html' => '<span class="post-com-count post-com-count-no-comments"><span class="comment-count comment-count-no-comments" aria-hidden="true">0</span><span class="screen-reader-text">No approved reviews</span></span><a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;product_id=PRODUCT_ID&#038;comment_status=moderated" class="post-com-count post-com-count-pending"><span class="comment-count-pending" aria-hidden="true">2</span><span class="screen-reader-text">2 pending reviews</span></a>',
		];

		yield 'approved and pending reviews, but product is trashed' => [
			'approved_review_count' => 2,
			'pending_review_count' => 1,
			'product_is_trashed' => true,
			'expected_html' => '<span class="post-com-count post-com-count-approved"><span class="comment-count-approved" aria-hidden="true">2</span><span class="screen-reader-text">2 approved reviews</span></span><a href="http://example.org/wp-admin/edit.php?post_type=product&#038;page=product-reviews&#038;product_id=PRODUCT_ID&#038;comment_status=moderated" class="post-com-count post-com-count-pending"><span class="comment-count-pending" aria-hidden="true">1</span><span class="screen-reader-text">1 pending review</span></a>',
		];
	}

	/**
	 * @testdox `current_action` is expected to return `delete_all` if certain query args are present in the request.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsListTable::current_action()
	 * @dataProvider provider_current_action
	 *
	 * @param bool $delete_all_isset   If `delete_all` isset.
	 * @param bool $delete_all_2_isset If `delete_all2` isset.
	 * @return void
	 */
	public function test_current_action( bool $delete_all_isset, bool $delete_all_2_isset ) {
		if ( $delete_all_isset ) {
			$_REQUEST['delete_all'] = 'Empty Trash';
		} else {
			unset( $_REQUEST['delete_all'] );
		}
		if ( $delete_all_2_isset ) {
			$_REQUEST['delete_all2'] = 'Empty Trash';
		} else {
			unset( $_REQUEST['delete_all2'] );
		}

		if ( ! $delete_all_isset && ! $delete_all_2_isset ) {
			$this->assertNotSame( 'delete_all', $this->get_reviews_list_table()->current_action() );
		} else {
			$this->assertSame( 'delete_all', $this->get_reviews_list_table()->current_action() );
		}
	}

	/** @see test_current_action */
	public function provider_current_action(): Generator {
		yield 'delete all isset' => [ true, false ];
		yield 'delete all 2 isset' => [ false, true ];
		yield 'both deletes are set' => [ true, true ];
		yield 'neither deletes are set' => [ false, false ];
	}

}
