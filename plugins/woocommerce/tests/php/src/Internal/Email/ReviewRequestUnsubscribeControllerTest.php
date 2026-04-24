<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\ReviewRequestScheduler;
use Automattic\WooCommerce\Internal\Email\ReviewRequestUnsubscribeController;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Email_Customer_Review_Request;
use WC_Unit_Test_Case;

/**
 * ReviewRequestUnsubscribeController test.
 *
 * @covers \Automattic\WooCommerce\Internal\Email\ReviewRequestUnsubscribeController
 */
class ReviewRequestUnsubscribeControllerTest extends WC_Unit_Test_Case {

	/**
	 * System Under Test.
	 *
	 * @var ReviewRequestUnsubscribeController
	 */
	private ReviewRequestUnsubscribeController $sut;

	/**
	 * A registered customer used for scenarios that need a non-guest order.
	 *
	 * @var int
	 */
	private int $customer_id;

	/**
	 * Set up the mailer and enable the review-request email.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( ReviewRequestUnsubscribeController::class );

		WC()->mailer();
		$email = $this->get_email();
		$email->update_option( 'enabled', 'yes' );
		$email->enabled = 'yes';

		$this->customer_id = $this->factory()->user->create( array( 'role' => 'customer' ) );
	}

	/**
	 * Reset between tests.
	 */
	public function tearDown(): void {
		$email = $this->get_email();
		$email->update_option( 'enabled', 'no' );
		$email->enabled = 'no';

		delete_user_meta( $this->customer_id, ReviewRequestUnsubscribeController::CUSTOMER_UNSUBSCRIBED_META );
		wp_delete_user( $this->customer_id );

		parent::tearDown();
	}

	/**
	 * @testdox get_unsubscribe_url() returns a tokenised URL for a registered customer's order.
	 */
	public function test_unsubscribe_url_contains_token_for_registered_customer(): void {
		$order = $this->create_registered_customer_order();
		$email = $this->get_email();
		$email->trigger( $order->get_id() );

		$url = $email->get_unsubscribe_url();

		$this->assertStringContainsString( ReviewRequestUnsubscribeController::QUERY_ARG_ORDER . '=' . $order->get_id(), $url );
		$this->assertStringContainsString( ReviewRequestUnsubscribeController::QUERY_ARG_KEY . '=', $url );

		$stored = wc_get_order( $order->get_id() )->get_meta( ReviewRequestUnsubscribeController::UNSUBSCRIBE_KEY_META );
		$this->assertNotEmpty( $stored );
	}

	/**
	 * @testdox get_unsubscribe_url() returns empty string for guest orders so the template hides the link.
	 */
	public function test_unsubscribe_url_is_empty_for_guest_order(): void {
		$order = OrderHelper::create_order();
		$order->set_customer_id( 0 );
		$order->save();

		$email = $this->get_email();
		$email->trigger( $order->get_id() );

		$this->assertSame( '', $email->get_unsubscribe_url() );
	}

	/**
	 * @testdox Calling get_unsubscribe_url() twice returns the same token (idempotent).
	 */
	public function test_unsubscribe_url_is_idempotent(): void {
		$order = $this->create_registered_customer_order();
		$email = $this->get_email();
		$email->trigger( $order->get_id() );

		$first  = $email->get_unsubscribe_url();
		$second = $email->get_unsubscribe_url();

		$this->assertSame( $first, $second );
	}

	/**
	 * @testdox respect_unsubscribe_flags returns false when the customer has opted out.
	 */
	public function test_filter_honors_customer_flag(): void {
		update_user_meta( $this->customer_id, ReviewRequestUnsubscribeController::CUSTOMER_UNSUBSCRIBED_META, 'yes' );

		$order = $this->create_registered_customer_order();

		$this->assertFalse( $this->sut->respect_unsubscribe_flags( true, $order ) );
	}

	/**
	 * @testdox respect_unsubscribe_flags passes through for registered customers who have not opted out.
	 */
	public function test_filter_passes_through_when_not_unsubscribed(): void {
		$order = $this->create_registered_customer_order();

		$this->assertTrue( $this->sut->respect_unsubscribe_flags( true, $order ) );
	}

	/**
	 * @testdox respect_unsubscribe_flags always lets guest orders through (guests are out of scope).
	 */
	public function test_filter_always_allows_guest_orders(): void {
		$order = OrderHelper::create_order();
		$order->set_customer_id( 0 );
		$order->save();

		$this->assertTrue( $this->sut->respect_unsubscribe_flags( true, $order ) );
	}

	/**
	 * @testdox respect_unsubscribe_flags short-circuits when an earlier callback already returned false.
	 */
	public function test_filter_short_circuits_when_already_false(): void {
		$order = $this->create_registered_customer_order();

		$this->assertFalse( $this->sut->respect_unsubscribe_flags( false, $order ) );
	}

	/**
	 * @testdox An opted-out customer's future orders are not scheduled.
	 */
	public function test_customer_flag_blocks_future_orders(): void {
		update_user_meta( $this->customer_id, ReviewRequestUnsubscribeController::CUSTOMER_UNSUBSCRIBED_META, 'yes' );

		$future_order = $this->create_registered_customer_order();
		$future_order->set_status( 'pending' );
		$future_order->save();

		$future_order->update_status( 'completed' );

		$this->assertFalse(
			(bool) as_next_scheduled_action( ReviewRequestScheduler::ACTION_HOOK, array( $future_order->get_id() ) )
		);
	}

	/**
	 * @testdox An invalid token does not flip the user meta flag.
	 */
	public function test_invalid_token_is_rejected(): void {
		$order = $this->create_registered_customer_order();
		$email = $this->get_email();
		$email->trigger( $order->get_id() );
		// Force the key to exist.
		$email->get_unsubscribe_url();

		$_GET[ ReviewRequestUnsubscribeController::QUERY_ARG_ORDER ] = $order->get_id();
		$_GET[ ReviewRequestUnsubscribeController::QUERY_ARG_KEY ]   = 'tampered-key';

		$this->sut->maybe_process_unsubscribe();

		$this->assertEmpty(
			get_user_meta( $this->customer_id, ReviewRequestUnsubscribeController::CUSTOMER_UNSUBSCRIBED_META, true )
		);

		unset( $_GET[ ReviewRequestUnsubscribeController::QUERY_ARG_ORDER ] );
		unset( $_GET[ ReviewRequestUnsubscribeController::QUERY_ARG_KEY ] );
	}

	/**
	 * Create an order that belongs to the test customer.
	 */
	private function create_registered_customer_order(): \WC_Order {
		$order = OrderHelper::create_order();
		$order->set_customer_id( $this->customer_id );
		$order->save();
		return $order;
	}

	/**
	 * Get the review-request email instance from the mailer.
	 */
	private function get_email(): WC_Email_Customer_Review_Request {
		$emails = WC()->mailer()->get_emails();
		return $emails['WC_Email_Customer_Review_Request'];
	}
}
