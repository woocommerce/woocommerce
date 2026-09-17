<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\ProductReviews\ReviewVerificationService;

/**
 * Tests for ReviewVerificationService.
 */
final class ReviewVerificationServiceTest extends \WC_Unit_Test_Case {

	/**
	 * @var ReviewVerificationService
	 */
	private ReviewVerificationService $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( ReviewVerificationService::class );
	}

	/**
	 * Clean up scheduled actions and cursor.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( '', array(), ReviewVerificationService::PRODUCT_REVIEWS_GROUP );
		delete_option( 'woocommerce_review_verification_last_id' );
		parent::tearDown();
	}

	/**
	 * @testdox Verification resolves registered buyer, guest buyer, and non-buyer correctly.
	 */
	public function test_add_comment_purchase_verification(): void {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$buyer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		$order_1  = $this->create_completed_order( $product_id, $buyer_id );

		$guest_email = uniqid( 'guest-', true ) . '@example.test';
		$order_2     = $this->create_completed_order( $product_id, 0, $guest_email );

		$non_buyer_id = self::factory()->user->create( array( 'role' => 'customer' ) );

		$buyer_review     = $this->create_unverified_review( $product_id, '', $buyer_id, 'review' );
		$guest_review     = $this->create_unverified_review( $product_id, $guest_email, 0, 'review' );
		$non_buyer_review = $this->create_unverified_review( $product_id, '', $non_buyer_id, 'review' );

		$this->assertTrue( $this->sut->add_comment_purchase_verification( $buyer_review ) );
		$this->assertSame( '1', get_comment_meta( $buyer_review, 'verified', true ), 'Registered buyer must be verified.' );

		$this->assertTrue( $this->sut->add_comment_purchase_verification( $guest_review ) );
		$this->assertSame( '1', get_comment_meta( $guest_review, 'verified', true ), 'Guest buyer must be verified via email.' );

		$this->assertFalse( $this->sut->add_comment_purchase_verification( $non_buyer_review ) );
		$this->assertSame( '0', get_comment_meta( $non_buyer_review, 'verified', true ), 'Non-buyer must not be verified.' );

		$order_1->delete( true );
		$order_2->delete( true );
		$product->delete( true );
	}

	/**
	 * @testdox Orchestrator and batch processor handle target comment types and ignore non-review types.
	 *
	 * @testWith [ true ]
	 *           [ false ]
	 *
	 * @param bool $pre_fill Whether to pre-fill the verified meta before the test.
	 */
	public function test_scheduling_and_backfill_by_type_and_prefill( bool $pre_fill ): void {
		global $wpdb;

		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$buyer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		$order    = $this->create_completed_order( $product_id, $buyer_id );

		// Three target review types + one irrelevant type.
		$review_id  = $this->create_unverified_review( $product_id, '', $buyer_id, 'review' );
		$comment_id = $this->create_unverified_review( $product_id, '', $buyer_id, 'comment' );
		$empty_id   = $this->create_unverified_review( $product_id, '', $buyer_id, '' );
		$note_id    = $this->create_unverified_review( $product_id, '', $buyer_id, 'order_note' );

		// wp_insert_comment coerces '' to 'comment'; force the exact type in the DB.
		$wpdb->update( $wpdb->comments, array( 'comment_type' => '' ), array( 'comment_ID' => $empty_id ) );
		clean_comment_cache( $empty_id );

		if ( $pre_fill ) {
			foreach ( array( $review_id, $comment_id, $empty_id ) as $id ) {
				add_comment_meta( $id, 'verified', 0, true );
			}
		}

		// Orchestrator: schedules batch actions only when unverified target reviews exist.
		$this->sut->schedule_backfill_batches();

		$scheduled = as_get_scheduled_actions(
			array(
				'hook'   => ReviewVerificationService::BACKFILL_PROCESS_BATCH_ACTION,
				'status' => \ActionScheduler_Store::STATUS_PENDING,
				'group'  => ReviewVerificationService::PRODUCT_REVIEWS_GROUP,
			),
			'ids'
		);
		if ( $pre_fill ) {
			$this->assertCount( 0, $scheduled, 'No batches when all reviews are pre-filled.' );
		} else {
			$this->assertCount( 1, $scheduled, 'Batches must be scheduled when unverified reviews exist.' );
		}
		as_unschedule_all_actions( '', array(), ReviewVerificationService::PRODUCT_REVIEWS_GROUP );

		// Batch processor: only receives IDs the orchestrator selected (target types only).
		foreach ( array( $review_id, $comment_id, $empty_id ) as $id ) {
			delete_comment_meta( $id, 'verified' );
		}
		$this->sut->process_backfill_batch( array( $review_id, $comment_id, $empty_id ) );

		$this->assertSame( '1', get_comment_meta( $review_id, 'verified', true ), "'review'-type must be resolved." );
		$this->assertSame( '1', get_comment_meta( $comment_id, 'verified', true ), "'comment'-type must be resolved." );
		$this->assertSame( '1', get_comment_meta( $empty_id, 'verified', true ), 'Empty type must be resolved.' );
		$this->assertSame( '', get_comment_meta( $note_id, 'verified', true ), 'Order note must not be touched by orchestrator.' );

		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * @testdox Orchestrator chunks reviews into the expected number of batch actions.
	 */
	public function test_orchestrator_schedules_correct_batch_count(): void {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$this->create_unverified_review( $product_id, uniqid( 'review-', true ) . '@example.test', 0, 'review' );
		$this->create_unverified_review( $product_id, uniqid( 'comment-', true ) . '@example.test', 0, 'comment' );
		$this->create_unverified_review( $product_id, uniqid( 'empty-', true ) . '@example.test', 0, '' );

		// Batch size = 2 means 3 reviews → 2 batch actions (2 + 1).
		add_filter( 'woocommerce_review_verification_backfill_batch_size', static fn() => 2 );

		try {
			$this->sut->schedule_backfill_batches();

			$actions = as_get_scheduled_actions(
				array(
					'hook'   => ReviewVerificationService::BACKFILL_PROCESS_BATCH_ACTION,
					'status' => \ActionScheduler_Store::STATUS_PENDING,
					'group'  => ReviewVerificationService::PRODUCT_REVIEWS_GROUP,
				),
				'ids'
			);
			$this->assertCount( 2, $actions, '3 reviews at batch size 2 must produce 2 batch actions.' );
		} finally {
			remove_all_filters( 'woocommerce_review_verification_backfill_batch_size' );
		}

		$product->delete( true );
	}

	/**
	 * Insert an approved product review.
	 *
	 * @param int    $product_id Product post ID.
	 * @param string $email      Author email.
	 * @param int    $user_id    Author user ID (0 for guest).
	 * @param string $type       Comment type.
	 * @return int Comment ID.
	 */
	private function create_unverified_review( int $product_id, string $email, int $user_id, string $type ): int {
		$comment_id = (int) wp_insert_comment(
			array(
				'comment_post_ID'      => $product_id,
				'comment_author'       => 'Reviewer',
				'comment_author_email' => $email,
				'comment_content'      => 'Test review.',
				'comment_type'         => $type,
				'comment_approved'     => 1,
				'user_id'              => $user_id,
			)
		);
		delete_comment_meta( $comment_id, 'verified' );

		return $comment_id;
	}

	/**
	 * Create a completed order containing a product.
	 *
	 * @param int    $product_id Product ID.
	 * @param int    $customer   Customer user ID (0 for guest).
	 * @param string $email      Billing email.
	 * @return \WC_Order
	 */
	private function create_completed_order( int $product_id, int $customer, string $email = '' ): \WC_Order {
		$order = wc_create_order(
			array_filter(
				array(
					'status'      => OrderStatus::COMPLETED,
					'customer_id' => $customer,
				)
			)
		);
		$order->set_billing_email( $email );
		$order->add_product( wc_get_product( $product_id ), 1 );
		$order->save();

		return $order;
	}
}
