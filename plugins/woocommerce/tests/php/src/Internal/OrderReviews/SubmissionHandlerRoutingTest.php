<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility;
use Automattic\WooCommerce\Internal\OrderReviews\SubmissionHandler;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * End-to-end wiring test for the submit_order_reviews AJAX action.
 *
 * Complements SubmissionHandlerTest (which calls handle() directly) by proving
 * the registered admin-ajax action actually routes to the handler for guests.
 */
class SubmissionHandlerRoutingTest extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_customer_review_request_enabled', 'yes' );
		WC()->maybe_init_order_reviews();
		update_option( 'comment_moderation', '0' );
		wp_set_current_user( 0 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$handler = wc_get_container()->get( SubmissionHandler::class );
		remove_action( 'wp_ajax_' . SubmissionHandler::ACTION, array( $handler, 'handle' ) );
		remove_action( 'wp_ajax_nopriv_' . SubmissionHandler::ACTION, array( $handler, 'handle' ) );
		delete_option( 'woocommerce_feature_customer_review_request_enabled' );
		delete_option( 'comment_moderation' );
		$_POST = array();
		ItemEligibility::reset_cache();
		remove_all_filters( 'wp_die_ajax_handler' );
		remove_all_filters( 'wp_doing_ajax' );
		parent::tearDown();
	}

	/**
	 * @testdox The nopriv AJAX action is registered through the production init path.
	 */
	public function test_nopriv_action_is_registered(): void {
		$handler = wc_get_container()->get( SubmissionHandler::class );

		$this->assertNotFalse(
			has_action( 'wp_ajax_nopriv_' . SubmissionHandler::ACTION, array( $handler, 'handle' ) ),
			'Guest submit_order_reviews action should be wired when the feature is enabled.'
		);
	}

	/**
	 * @testdox A guest request routes through admin-ajax to the handler and inserts a review.
	 */
	public function test_guest_submission_routes_end_to_end(): void {
		$order = wc_create_order( array( 'status' => OrderStatus::COMPLETED ) );
		$order->set_billing_first_name( 'John' );
		$order->set_billing_email( 'john@example.com' );
		$product = WC_Helper_Product::create_simple_product();
		$order->add_product( $product, 1 );
		$order->save();

		$item_id = 0;
		foreach ( $order->get_items() as $item ) {
			$item_id = $item->get_id();
		}

		$_POST['_wcnonce'] = wp_create_nonce( SubmissionHandler::ACTION );
		$_POST['order_id'] = $order->get_id();
		$_POST['key']      = $order->get_order_key();
		$_POST['reviews']  = array(
			array(
				'order_item_id' => $item_id,
				'product_id'    => $product->get_id(),
				'rating'        => 5,
				'text'          => 'Great product!',
			),
		);

		$response = $this->dispatch();

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'ok', $response['data']['results'][0]['status'] );

		$comment = get_comment( $response['data']['results'][0]['comment_id'] );
		$this->assertSame( 'review', $comment->comment_type );
		$this->assertSame( $product->get_id(), (int) $comment->comment_post_ID );
	}

	/**
	 * Fire the registered nopriv action and capture the JSON envelope it emits.
	 *
	 * @return array{success:bool,data:mixed}
	 */
	private function dispatch(): array {
		$response = array(
			'success' => false,
			'data'    => null,
		);

		add_filter(
			'wp_send_json',
			static function ( $payload ) use ( &$response ) {
				$response['success'] = ! empty( $payload['success'] );
				$response['data']    = $payload['data'] ?? null;
				return $payload;
			}
		);
		add_filter( 'wp_die_ajax_handler', static fn() => static fn() => null );
		add_filter( 'wp_doing_ajax', '__return_true' );

		do_action( 'wp_ajax_nopriv_' . SubmissionHandler::ACTION ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		return $response;
	}
}
