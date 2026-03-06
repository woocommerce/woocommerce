<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Notifications;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewReviewNotification;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the NewReviewNotification class.
 */
class NewReviewNotificationTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should return a payload with all required keys for an existing review.
	 */
	public function test_to_payload_contains_required_keys(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$comment_id = WC_Helper_Product::create_product_review( $product->get_id() );

		$notification = new NewReviewNotification( $comment_id );
		$payload      = $notification->to_payload();

		$this->assertArrayHasKey( 'type', $payload );
		$this->assertArrayHasKey( 'blog_id', $payload );
		$this->assertArrayHasKey( 'resource_id', $payload );
		$this->assertArrayHasKey( 'title', $payload );
		$this->assertArrayHasKey( 'format', $payload['title'] );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertArrayHasKey( 'format', $payload['message'] );
		$this->assertArrayHasKey( 'args', $payload['message'] );
		$this->assertArrayHasKey( 'icon', $payload );
		$this->assertArrayHasKey( 'meta', $payload );
		$this->assertArrayHasKey( 'comment_id', $payload['meta'] );
	}

	/**
	 * @testdox Should return store_review as the notification type.
	 */
	public function test_type_is_store_review(): void {
		$notification = new NewReviewNotification( 1 );

		$this->assertSame( 'store_review', $notification->get_type() );
	}

	/**
	 * @testdox Should return the comment ID as the resource ID.
	 */
	public function test_resource_id_matches_comment_id(): void {
		$notification = new NewReviewNotification( 42 );

		$this->assertSame( 42, $notification->get_resource_id() );
	}

	/**
	 * @testdox Should include the reviewer name, product name, and review
	 * content in the message args.
	 */
	public function test_to_payload_message_args_contains_expected_values(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$comment_id = WC_Helper_Product::create_product_review( $product->get_id() );
		$comment    = get_comment( $comment_id );

		$notification = new NewReviewNotification( $comment_id );
		$payload      = $notification->to_payload();

		$this->assertSame( $comment->comment_author, $payload['message']['args'][0] );
		$this->assertSame( $product->get_name(), $payload['message']['args'][1] );
		$this->assertSame( $comment->comment_content, $payload['message']['args'][2] );
	}

	/**
	 * @testdox Should strip HTML tags, and script tags including content, from
	 * reviewer name in message args.
	 */
	public function test_to_payload_strips_html_and_script_content_from_comment_author(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $product->get_id(),
				'comment_author'       => '<b>Evil</b> <script>alert("xss")</script>Author',
				'comment_author_email' => 'test@test.local',
				'comment_content'      => 'A clean review.',
				'comment_approved'     => 1,
				'comment_type'         => 'review',
			)
		);

		$notification = new NewReviewNotification( $comment_id );
		$payload      = $notification->to_payload();

		$this->assertSame( 'Evil Author', $payload['message']['args'][0] );
	}

	/**
	 * @testdox Should strip HTML tags, and script tags including content, from
	 * review content in message args.
	 */
	public function test_to_payload_strips_html_and_script_content_from_comment_content(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $product->get_id(),
				'comment_author'       => 'Reviewer',
				'comment_author_email' => 'test@test.local',
				'comment_content'      => '<p>Great product!</p> <script>alert("xss")</script>',
				'comment_approved'     => 1,
				'comment_type'         => 'review',
			)
		);

		$notification = new NewReviewNotification( $comment_id );
		$payload      = $notification->to_payload();

		$this->assertSame( 'Great product!', $payload['message']['args'][2] );
	}

	/**
	 * @testdox Should return null when the comment no longer exists.
	 */
	public function test_to_payload_returns_null_for_deleted_comment(): void {
		$notification = new NewReviewNotification( 999999 );

		$this->assertNull( $notification->to_payload() );
	}
}
