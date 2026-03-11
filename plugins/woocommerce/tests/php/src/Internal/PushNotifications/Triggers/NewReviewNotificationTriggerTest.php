<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\NewReviewNotificationTrigger;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the NewReviewNotificationTrigger class.
 */
class NewReviewNotificationTriggerTest extends WC_Unit_Test_Case {
	/**
	 * An instance of NewReviewNotificationTrigger.
	 *
	 * @var NewReviewNotificationTrigger
	 */
	private $trigger;

	/**
	 * The notification store used by the trigger.
	 *
	 * @var PendingNotificationStore
	 */
	private $store;

	/**
	 * A test product ID.
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store = new PendingNotificationStore();
		$this->store->register();

		wc_get_container()->replace( PendingNotificationStore::class, $this->store );
		wc_get_container()->reset_all_resolved();

		$this->trigger = new NewReviewNotificationTrigger();

		$product          = WC_Helper_Product::create_simple_product();
		$this->product_id = $product->get_id();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'comment_post', array( $this->trigger, 'on_comment_post' ) );
		remove_action( 'shutdown', array( $this->store, 'dispatch_all' ) );

		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * @testdox Should register the comment_post hook.
	 */
	public function test_register_adds_comment_post_hook(): void {
		$this->trigger->register();

		$this->assertNotFalse(
			has_action(
				'comment_post',
				array( $this->trigger, 'on_comment_post' )
			),
			'comment_post hook should be registered'
		);
	}

	/**
	 * @testdox Should add a notification for an approved product review.
	 */
	public function test_adds_notification_for_approved_review(): void {
		$commentdata = $this->build_review_data( $this->product_id );

		$this->trigger->on_comment_post( 1, 1, $commentdata );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should add a notification for an unapproved product review.
	 */
	public function test_adds_notification_for_unapproved_review(): void {
		$commentdata = $this->build_review_data( $this->product_id );

		$this->trigger->on_comment_post( 1, 0, $commentdata );

		$this->assertSame( 1, $this->store->count() );
	}

	/**
	 * @testdox Should not add a notification for a spam review.
	 */
	public function test_ignores_spam_review(): void {
		$commentdata = $this->build_review_data( $this->product_id );

		$this->trigger->on_comment_post( 1, 'spam', $commentdata );

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should not add a notification for a regular comment that is not a review.
	 */
	public function test_ignores_non_review_comment(): void {
		$commentdata = array(
			'comment_post_ID'  => $this->product_id,
			'comment_author'   => 'test',
			'comment_content'  => 'A regular comment.',
			'comment_approved' => 1,
			'comment_type'     => '',
		);

		$this->trigger->on_comment_post( 1, 1, $commentdata );

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * @testdox Should not add a notification for a review on a non-product post.
	 */
	public function test_ignores_review_on_non_product(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'A blog post',
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$commentdata = array(
			'comment_post_ID'  => $post_id,
			'comment_author'   => 'test',
			'comment_content'  => 'A review on a blog post.',
			'comment_approved' => 1,
			'comment_type'     => 'review',
		);

		$this->trigger->on_comment_post( 1, 1, $commentdata );

		$this->assertSame( 0, $this->store->count() );
	}

	/**
	 * Builds the comment data array for a product review.
	 *
	 * @param int $product_id The product ID.
	 * @return array The comment data.
	 */
	private function build_review_data( int $product_id ): array {
		return array(
			'comment_post_ID'  => $product_id,
			'comment_author'   => 'Test Reviewer',
			'comment_content'  => 'Great product!',
			'comment_approved' => 1,
			'comment_type'     => 'review',
		);
	}
}
