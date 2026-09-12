<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ProductReviews;

require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

use Automattic\WooCommerce\Internal\Admin\ProductReviews\Reviews;
use WC_Helper_Product;
use WP_Ajax_UnitTestCase;
use WP_Comment;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * Tests Product Reviews registered AJAX handlers.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\Reviews::handle_edit_review
 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\Reviews::handle_reply_to_review
 */
class ReviewsAjaxTest extends WP_Ajax_UnitTestCase {
	/**
	 * Registered Product Reviews handler.
	 *
	 * @var Reviews
	 */
	private $reviews;

	/** @var bool Whether this test added the edit hook. */
	private $added_edit_hook = false;

	/** @var bool Whether this test added the reply hook. */
	private $added_reply_hook = false;

	/** @var bool Whether this test added the core edit hook. */
	private $added_core_edit_hook = false;

	/** @var bool Whether this test added the core reply hook. */
	private $added_core_reply_hook = false;

	/** @var array<string, mixed> Original POST payload. */
	private $original_post = array();

	/** @var array<string, mixed> Original request payload. */
	private $original_request = array();

	/** @var int Original current user ID. */
	private $original_user_id = 0;

	/**
	 * Set up the registered Woo handlers.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->original_post         = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Snapshots test-process state; no request data is processed.
		$this->original_request      = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Snapshots test-process state; no request data is processed.
		$this->original_user_id      = get_current_user_id();
		$this->added_edit_hook       = false;
		$this->added_reply_hook      = false;
		$this->added_core_edit_hook  = false;
		$this->added_core_reply_hook = false;
		$this->reviews               = wc_get_container()->get( Reviews::class );

		if ( false === has_action( 'wp_ajax_edit-comment', array( $this->reviews, 'handle_edit_review' ) ) ) {
			add_action( 'wp_ajax_edit-comment', array( $this->reviews, 'handle_edit_review' ), -1 );
			$this->added_edit_hook = true;
		}
		if ( false === has_action( 'wp_ajax_replyto-comment', array( $this->reviews, 'handle_reply_to_review' ) ) ) {
			add_action( 'wp_ajax_replyto-comment', array( $this->reviews, 'handle_reply_to_review' ), -1 );
			$this->added_reply_hook = true;
		}
		if ( false === has_action( 'wp_ajax_edit-comment', 'wp_ajax_edit_comment' ) ) {
			add_action( 'wp_ajax_edit-comment', 'wp_ajax_edit_comment', 1 );
			$this->added_core_edit_hook = true;
		}
		if ( false === has_action( 'wp_ajax_replyto-comment', 'wp_ajax_replyto_comment' ) ) {
			add_action( 'wp_ajax_replyto-comment', 'wp_ajax_replyto_comment', 1 );
			$this->added_core_reply_hook = true;
		}

		$this->assertSame( -1, has_action( 'wp_ajax_edit-comment', array( $this->reviews, 'handle_edit_review' ) ) );
		$this->assertSame( -1, has_action( 'wp_ajax_replyto-comment', array( $this->reviews, 'handle_reply_to_review' ) ) );
	}

	/**
	 * Reset mutable request and response state.
	 */
	public function tear_down(): void {
		if ( $this->added_edit_hook ) {
			remove_action( 'wp_ajax_edit-comment', array( $this->reviews, 'handle_edit_review' ), -1 );
		}
		if ( $this->added_reply_hook ) {
			remove_action( 'wp_ajax_replyto-comment', array( $this->reviews, 'handle_reply_to_review' ), -1 );
		}
		if ( $this->added_core_edit_hook ) {
			remove_action( 'wp_ajax_edit-comment', 'wp_ajax_edit_comment', 1 );
		}
		if ( $this->added_core_reply_hook ) {
			remove_action( 'wp_ajax_replyto-comment', 'wp_ajax_replyto_comment', 1 );
		}

		$_POST                = $this->original_post;
		$_REQUEST             = $this->original_request;
		$this->_last_response = '';
		wp_set_current_user( $this->original_user_id );

		parent::tear_down();
	}

	/**
	 * Editing a product review uses Woo's registered handler and row renderer.
	 */
	public function test_edit_review_via_registered_ajax_persists_and_returns_woo_row(): void {
		$product = null;
		$review  = null;

		try {
			$product = WC_Helper_Product::create_simple_product();
			$review  = $this->create_review( $product->get_id(), 'Original review' );
			$this->_setRole( 'administrator' );

			$_POST = array(
				'_ajax_nonce-replyto-comment' => wp_create_nonce( 'replyto-comment' ),
				'comment_ID'                  => $review->comment_ID,
				'content'                     => 'Updated review from AJAX',
				'position'                    => 7,
				'status'                      => 'approved',
			);

			$xml           = $this->dispatch_successful_ajax( 'edit-comment' );
			$response      = $xml->response[0]->edit_comment;
			$response_data = (string) $response->response_data;
			$fresh_review  = get_comment( $review->comment_ID );

			$this->assertInstanceOf( WP_Comment::class, $fresh_review );
			$this->assertSame( 'Updated review from AJAX', $fresh_review->comment_content );
			$this->assertSame( 'approved', $fresh_review->comment_approved );
			$this->assertSame( 'edit-comment_' . $review->comment_ID, (string) $xml->response['action'] );
			$this->assertSame( (string) $review->comment_ID, (string) $response['id'] );
			$this->assertSame( '7', (string) $response['position'] );
			$this->assertStringContainsString( 'id="comment-' . $review->comment_ID . '"', $response_data );
			$this->assertStringContainsString( 'data-colname="Rating"', $response_data );
			$this->assertStringContainsString( 'data-colname="Product"', $response_data );
		} finally {
			if ( $review ) {
				wp_delete_comment( (int) $review->comment_ID, true );
			}
			if ( $product ) {
				$product->delete( true );
			}
		}
	}

	/**
	 * Non-product comments fall through to WordPress core's registered handler.
	 */
	public function test_edit_non_product_comment_via_registered_ajax_falls_through_to_core(): void {
		$post_id = 0;
		$comment = null;

		try {
			$post_id = $this->factory()->post->create();
			$comment = $this->factory()->comment->create_and_get(
				array(
					'comment_post_ID' => $post_id,
					'comment_content' => 'Original core comment',
				)
			);
			$this->_setRole( 'administrator' );

			$_POST = array(
				'_ajax_nonce-replyto-comment' => wp_create_nonce( 'replyto-comment' ),
				'comment_ID'                  => $comment->comment_ID,
				'content'                     => 'Updated by WordPress core',
				'position'                    => 3,
			);

			$xml           = $this->dispatch_successful_ajax( 'edit-comment' );
			$response_data = (string) $xml->response[0]->edit_comment->response_data;
			$fresh_comment = get_comment( $comment->comment_ID );

			$this->assertInstanceOf( WP_Comment::class, $fresh_comment );
			$this->assertSame( 'Updated by WordPress core', $fresh_comment->comment_content );
			$this->assertSame( 'edit-comment_' . $comment->comment_ID, (string) $xml->response['action'] );
			$this->assertSame( '3', (string) $xml->response[0]->edit_comment['position'] );
			$this->assertStringNotContainsString( 'data-colname="Rating"', $response_data );
			$this->assertStringNotContainsString( 'data-colname="Product"', $response_data );
		} finally {
			if ( $comment ) {
				wp_delete_comment( (int) $comment->comment_ID, true );
			}
			if ( $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}
	}

	/**
	 * Editing a review without permission is rejected without persistence.
	 */
	public function test_edit_review_via_registered_ajax_rejects_unauthorized_request(): void {
		$product = null;
		$review  = null;

		try {
			$product = WC_Helper_Product::create_simple_product();
			$review  = $this->create_review( $product->get_id(), 'Protected review' );
			$this->_setRole( 'subscriber' );

			$_POST = array(
				'_ajax_nonce-replyto-comment' => wp_create_nonce( 'replyto-comment' ),
				'comment_ID'                  => $review->comment_ID,
				'content'                     => 'Unauthorized edit',
			);

			$this->assert_ajax_stops_with( 'edit-comment', '-1' );

			$fresh_review = get_comment( $review->comment_ID );
			$this->assertInstanceOf( WP_Comment::class, $fresh_review );
			$this->assertSame( 'Protected review', $fresh_review->comment_content );
		} finally {
			if ( $review ) {
				wp_delete_comment( (int) $review->comment_ID, true );
			}
			if ( $product ) {
				$product->delete( true );
			}
		}
	}

	/**
	 * Replying to a product review persists a child and returns Woo's row.
	 */
	public function test_reply_to_review_via_registered_ajax_persists_child_and_returns_woo_row(): void {
		$product  = null;
		$review   = null;
		$reply_id = 0;

		try {
			$product = WC_Helper_Product::create_simple_product();
			$review  = $this->create_review( $product->get_id(), 'Parent review' );
			$this->_setRole( 'administrator' );
			$current_user = wp_get_current_user();

			$_POST = array(
				'_ajax_nonce-replyto-comment' => wp_create_nonce( 'replyto-comment' ),
				'comment_ID'                  => $review->comment_ID,
				'comment_post_ID'             => $product->get_id(),
				'comment_type'                => 'comment',
				'content'                     => 'Store reply from AJAX',
				'position'                    => 5,
			);

			$xml           = $this->dispatch_successful_ajax( 'replyto-comment' );
			$response      = $xml->response[0]->comment;
			$response_data = (string) $response->response_data;
			$reply_id      = (int) $response['id'];
			$fresh_reply   = get_comment( $reply_id );

			$this->assertGreaterThan( 0, $reply_id );
			$this->assertInstanceOf( WP_Comment::class, $fresh_reply );
			$this->assertSame( $product->get_id(), (int) $fresh_reply->comment_post_ID );
			$this->assertSame( (int) $review->comment_ID, (int) $fresh_reply->comment_parent );
			$this->assertSame( 'comment', $fresh_reply->comment_type );
			$this->assertSame( 'Store reply from AJAX', $fresh_reply->comment_content );
			$this->assertSame( $current_user->display_name, $fresh_reply->comment_author );
			$this->assertSame( $current_user->user_email, $fresh_reply->comment_author_email );
			$this->assertSame( 'replyto-comment_' . $reply_id, (string) $xml->response['action'] );
			$this->assertSame( '5', (string) $response['position'] );
			$this->assertStringContainsString( 'id="comment-' . $reply_id . '"', $response_data );
			$this->assertStringContainsString( 'data-colname="Product"', $response_data );
			$this->assertStringContainsString( 'In reply to', $response_data );
		} finally {
			if ( $reply_id ) {
				wp_delete_comment( $reply_id, true );
			}
			if ( $review ) {
				wp_delete_comment( (int) $review->comment_ID, true );
			}
			if ( $product ) {
				$product->delete( true );
			}
		}
	}

	/**
	 * Replying without permission is rejected without creating a child.
	 */
	public function test_reply_to_review_via_registered_ajax_rejects_unauthorized_request(): void {
		$product = null;
		$review  = null;

		try {
			$product = WC_Helper_Product::create_simple_product();
			$review  = $this->create_review( $product->get_id(), 'Protected parent review' );
			$this->_setRole( 'subscriber' );

			$_POST = array(
				'_ajax_nonce-replyto-comment' => wp_create_nonce( 'replyto-comment' ),
				'comment_ID'                  => $review->comment_ID,
				'comment_post_ID'             => $product->get_id(),
				'content'                     => 'Unauthorized reply',
			);

			$this->assert_ajax_stops_with( 'replyto-comment', '-1' );

			$this->assertSame(
				array(),
				get_comments(
					array(
						'parent'  => $review->comment_ID,
						'post_id' => $product->get_id(),
					)
				)
			);
		} finally {
			if ( $review ) {
				wp_delete_comment( (int) $review->comment_ID, true );
			}
			if ( $product ) {
				$product->delete( true );
			}
		}
	}

	/**
	 * Create a product review fixture.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $content    Review content.
	 * @return WP_Comment
	 */
	private function create_review( int $product_id, string $content ): WP_Comment {
		$review = $this->factory()->comment->create_and_get(
			array(
				'comment_post_ID'      => $product_id,
				'comment_author'       => 'Review Author',
				'comment_author_email' => 'reviewer@example.com',
				'comment_content'      => $content,
				'comment_approved'     => '1',
				'comment_type'         => 'review',
			)
		);
		update_comment_meta( $review->comment_ID, 'rating', 4 );

		return $review;
	}

	/**
	 * Dispatch an AJAX request that must return a well-formed XML response.
	 *
	 * @param string $action AJAX action.
	 * @return \SimpleXMLElement
	 */
	private function dispatch_successful_ajax( string $action ): \SimpleXMLElement {
		$this->_last_response = '';
		$buffer_level         = ob_get_level();

		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $exception ) {
			unset( $exception );
		} finally {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
			while ( ob_get_level() < $buffer_level ) {
				ob_start();
			}
		}

		$xml = simplexml_load_string( (string) $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );
		$this->assertInstanceOf( \SimpleXMLElement::class, $xml, (string) $this->_last_response );

		return $xml;
	}

	/**
	 * Assert that a registered AJAX request terminates with the expected error.
	 *
	 * @param string $action  AJAX action.
	 * @param string $message Expected exception message.
	 */
	private function assert_ajax_stops_with( string $action, string $message ): void {
		$this->_last_response = '';
		$did_stop             = false;

		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieStopException $exception ) {
			$did_stop = true;
			$this->assertSame( $message, $exception->getMessage() );
		}

		$this->assertTrue( $did_stop, 'The AJAX request should terminate before reaching the core handler.' );
	}
}
