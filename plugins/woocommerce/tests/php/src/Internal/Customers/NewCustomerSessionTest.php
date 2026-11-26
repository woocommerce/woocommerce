<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Customers;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationEventListener;
use Automattic\WooCommerce\Internal\Customers\NewCustomerSession;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Customer;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the NewCustomerSession class.
 */
class NewCustomerSessionTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NewCustomerSession
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( 0 );
		WC()->session->set( 'new_customer', null );

		$this->sut = wc_get_container()->get( NewCustomerSession::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			// The session object is shared across tests and the base class does not reset its data.
			WC()->session->set( 'new_customer', null );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should create an account with a generated password, remember it in the session, and not log the shopper in.
	 */
	public function test_create_customer_creates_account_without_logging_in(): void {
		$customer_id = $this->sut->create_customer( 'new-shopper@example.com', array( 'first_name' => 'New' ) );

		$this->assertIsInt( $customer_id, 'An account should be created.' );
		$this->assertSame( 0, get_current_user_id(), 'The shopper should stay logged out.' );
		$this->assertSame( $customer_id, WC()->session->get( 'new_customer' )['id'], 'The session should remember the new account.' );
		$this->assertTrue( (bool) get_user_option( 'default_password_nag', $customer_id ), 'The account should have a generated password.' );
	}

	/**
	 * @testdox Should reuse the unverified account this session created when the email matches, ignoring case.
	 */
	public function test_create_customer_reuses_unverified_session_account(): void {
		$customer_id = $this->sut->create_customer( 'retry@example.com' );
		$created     = 0;
		$count_new   = function () use ( &$created ) {
			++$created;
		};
		add_action( 'woocommerce_created_customer', $count_new );

		$retry_id = $this->sut->create_customer( 'Retry@Example.com' );

		$this->assertSame( $customer_id, $retry_id, 'The retry should reuse the account created earlier in the session.' );
		$this->assertSame( 0, $created, 'No second account should be created.' );
	}

	/**
	 * @testdox Should use a chosen username, and keep it when a retry reuses the account.
	 */
	public function test_create_customer_uses_chosen_username(): void {
		$customer_id = $this->sut->create_customer( 'chosen@example.com', array(), 'chosen-login' );

		$retry_id = $this->sut->create_customer( 'chosen@example.com', array(), 'different-login' );

		$this->assertSame( 'chosen-login', get_userdata( $customer_id )->user_login, 'The account should use the chosen username.' );
		$this->assertSame( $customer_id, $retry_id, 'The retry should reuse the account.' );
		$this->assertSame( 'chosen-login', get_userdata( $retry_id )->user_login, 'A reused account keeps its username.' );
	}

	/**
	 * @testdox Should not reuse the session account once its email is verified.
	 */
	public function test_create_customer_does_not_reuse_verified_account(): void {
		$customer_id = $this->sut->create_customer( 'verified@example.com' );
		wc_get_container()->get( EmailVerificationService::class )->mark_verified( $customer_id, 'verified@example.com' );

		$result = $this->sut->create_customer( 'verified@example.com' );

		$this->assertWPError( $result, 'A verified account has a password, so the shopper must log in instead.' );
		$this->assertSame( 'registration-error-email-exists', $result->get_error_code() );
	}

	/**
	 * @testdox Should not reuse the session account for a different email address.
	 */
	public function test_create_customer_does_not_reuse_account_for_other_email(): void {
		$customer_id = $this->sut->create_customer( 'first@example.com' );

		$other_id = $this->sut->create_customer( 'second@example.com' );

		$this->assertIsInt( $other_id );
		$this->assertNotSame( $customer_id, $other_id, 'A different email should get its own account.' );
	}

	/**
	 * @testdox Should never reuse an existing account that this session did not create.
	 */
	public function test_create_customer_does_not_reuse_other_existing_account(): void {
		WC_Helper_Customer::create_customer( 'existing', 'password', 'existing@example.com' );

		$result = $this->sut->create_customer( 'existing@example.com' );

		$this->assertWPError( $result );
		$this->assertSame( 'registration-error-email-exists', $result->get_error_code() );
	}

	/**
	 * @testdox Should treat an order as a session customer order only for the unclaimed account this session created.
	 */
	public function test_is_session_customer_order(): void {
		$customer_id  = $this->sut->create_customer( 'order-owner@example.com' );
		$other_id     = WC_Helper_Customer::create_customer( 'other', 'password', 'other@example.com' )->get_id();
		$new_order    = wc_create_order( array( 'customer_id' => $customer_id ) );
		$other_order  = wc_create_order( array( 'customer_id' => $other_id ) );
		$guest_order  = wc_create_order();
		$verification = wc_get_container()->get( EmailVerificationService::class );

		$this->assertTrue( $this->sut->is_session_customer_order( $new_order ), 'The session account order should be recognised.' );
		$this->assertFalse( $this->sut->is_session_customer_order( $other_order ), 'Another customer order should not be recognised.' );
		$this->assertFalse( $this->sut->is_session_customer_order( $guest_order ), 'A guest order should not be recognised.' );

		$verification->mark_verified( $customer_id, 'order-owner@example.com' );

		$this->assertFalse( $this->sut->is_session_customer_order( $new_order ), 'A verified account can log in, so its orders need a login.' );
	}

	/**
	 * @testdox An order that created an unclaimed account counts as one in any session, until the account is claimed.
	 */
	public function test_is_unclaimed_customer_order_in_any_session(): void {
		$customer_id = $this->sut->create_customer( 'logged-out-access@example.com' );
		$order       = wc_create_order( array( 'customer_id' => $customer_id ) );
		$this->sut->mark_order_created_customer( $order, $customer_id );
		$order->save();
		WC()->session->set( 'new_customer', null );
		$order = wc_get_order( $order->get_id() );

		$this->assertTrue( $this->sut->is_unclaimed_customer_order( $order ), 'Another session should recognise the order.' );
		$this->assertTrue( current_user_can( 'pay_for_order', $order->get_id() ), 'Another session should be able to pay like a guest.' );
		$this->assertFalse( $this->sut->is_session_customer_order( $order ), 'Another session must not reuse the account.' );

		wc_get_container()->get( EmailVerificationService::class )->mark_verified( $customer_id, 'logged-out-access@example.com' );

		$this->assertFalse( $this->sut->is_unclaimed_customer_order( $order ), 'A verified account has a password, so its orders need a login.' );
		$this->assertFalse( current_user_can( 'pay_for_order', $order->get_id() ), 'Once verified, the shopper must log in to pay.' );

		wc_get_container()->get( EmailVerificationService::class )->clear_verification( $customer_id );

		$this->assertFalse( $this->sut->is_unclaimed_customer_order( $order ), 'Clearing the verification later should not reopen the order.' );
	}

	/**
	 * @testdox Guest orders and orders of other unclaimed accounts do not count, including an order moved to another customer.
	 */
	public function test_is_unclaimed_customer_order_only_for_marked_orders(): void {
		$older_customer = WC_Helper_Customer::create_customer( 'older', 'password', 'older-unverified@example.com' );
		$older_order    = wc_create_order( array( 'customer_id' => $older_customer->get_id() ) );

		$customer_id = $this->sut->create_customer( 'moved-order@example.com' );
		$moved_order = wc_create_order( array( 'customer_id' => $customer_id ) );
		$this->sut->mark_order_created_customer( $moved_order, $customer_id );
		$moved_order->set_customer_id( $older_customer->get_id() );
		$moved_order->save();
		WC()->session->set( 'new_customer', null );

		$this->assertFalse( $this->sut->is_unclaimed_customer_order( wc_create_order() ), 'A guest order has no account.' );
		$this->assertFalse( $this->sut->is_unclaimed_customer_order( $older_order ), 'An order without the marker needs a login.' );
		$this->assertFalse( $this->sut->is_unclaimed_customer_order( wc_get_order( $moved_order->get_id() ) ), 'The marker only applies to the account the order created.' );
	}

	/**
	 * @testdox A copy of the marker on another order of the same account, such as a subscription renewal, does not count.
	 */
	public function test_copied_marker_does_not_count_on_another_order(): void {
		$customer_id = $this->sut->create_customer( 'copied-marker@example.com' );
		$order       = new \WC_Order();
		$order->set_customer_id( $customer_id );
		$this->sut->mark_order_created_customer( $order, $customer_id );
		$order->save();

		$renewal = wc_create_order( array( 'customer_id' => $customer_id ) );
		$renewal->update_meta_data( '_created_customer_fingerprint', $order->get_meta( '_created_customer_fingerprint' ) );
		$renewal->save();
		WC()->session->set( 'new_customer', null );

		$this->assertTrue( $this->sut->is_unclaimed_customer_order( wc_get_order( $order->get_id() ) ), 'A new order marked before its first save should count.' );
		$this->assertFalse( $this->sut->is_unclaimed_customer_order( wc_get_order( $renewal->get_id() ) ), 'A copied marker should not count.' );
	}

	/**
	 * @testdox mark_order_created_customer() only marks an order that belongs to the given account.
	 */
	public function test_mark_order_created_customer_requires_matching_customer(): void {
		$customer_id = $this->sut->create_customer( 'mark-mismatch@example.com' );
		$guest_order = wc_create_order();

		$this->sut->mark_order_created_customer( $guest_order, $customer_id );
		$this->sut->mark_order_created_customer( $guest_order, 0 );

		$this->assertSame( '', $guest_order->get_meta( '_created_customer_fingerprint' ) );
	}

	/**
	 * @testdox The REST API returns the order's other meta, but not the account fingerprint.
	 */
	public function test_rest_order_response_leaves_out_the_fingerprint(): void {
		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$customer_id = $this->sut->create_customer( 'rest-meta@example.com' );
		$order       = wc_create_order( array( 'customer_id' => $customer_id ) );
		$this->sut->mark_order_created_customer( $order, $customer_id );
		$order->update_meta_data( 'gift_note', 'Happy birthday' );
		$order->save();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() ) );
		$keys     = wp_list_pluck( $response->get_data()['meta_data'], 'key' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertContains( 'gift_note', $keys );
		$this->assertNotContains( '_created_customer_fingerprint', $keys );
	}

	/**
	 * Capabilities a logged out shopper has for a guest order, and so for an unclaimed new account's order.
	 *
	 * @return array<string, array{string}>
	 */
	public function provider_guest_order_capabilities(): array {
		return array(
			'pay'    => array( 'pay_for_order' ),
			'cancel' => array( 'cancel_order' ),
		);
	}

	/**
	 * @testdox A logged out shopper can $capability for the order that created an unclaimed account, but not for other customer orders.
	 * @dataProvider provider_guest_order_capabilities
	 *
	 * @param string $capability Capability to check.
	 */
	public function test_guest_order_capability_for_unclaimed_account_order( string $capability ): void {
		$customer_id = $this->sut->create_customer( "{$capability}@example.com" );
		$other_id    = WC_Helper_Customer::create_customer( "{$capability}other", 'password', "{$capability}other@example.com" )->get_id();
		$new_order   = wc_create_order( array( 'customer_id' => $customer_id ) );
		$other_order = wc_create_order( array( 'customer_id' => $other_id ) );
		$this->sut->mark_order_created_customer( $new_order, $customer_id );
		$new_order->save();

		$this->assertTrue( current_user_can( $capability, $new_order->get_id() ), 'The new account order should be allowed while logged out.' );
		$this->assertFalse( current_user_can( $capability, $other_order->get_id() ), 'Another customer order should still need a login.' );

		wp_set_current_user( $other_id );
		$this->assertFalse( current_user_can( $capability, $new_order->get_id() ), 'A different logged in customer should not get access.' );

		wp_set_current_user( 0 );
		wc_get_container()->get( EmailVerificationService::class )->mark_verified( $customer_id, "{$capability}@example.com" );
		$this->assertFalse( current_user_can( $capability, $new_order->get_id() ), 'Once verified, the shopper must log in.' );
	}

	/**
	 * @testdox Should ignore the session account once anyone is logged in, because the guest session moves to that user.
	 */
	public function test_logged_in_user_does_not_inherit_session_account(): void {
		$customer_id = $this->sut->create_customer( 'inherit@example.com' );
		$order       = wc_create_order( array( 'customer_id' => $customer_id ) );
		$other_id    = WC_Helper_Customer::create_customer( 'inheritother', 'password', 'inheritother@example.com' )->get_id();

		wp_set_current_user( $other_id );

		$this->assertFalse( $this->sut->is_session_customer_order( $order ), 'A different logged in user should not get guest access to the session account orders.' );
	}

	/**
	 * @testdox Should ignore an account stored by another session, such as one cloned from a shared checkout link.
	 */
	public function test_session_account_is_tied_to_the_session_that_created_it(): void {
		$customer_id = $this->sut->create_customer( 'cloned@example.com' );
		$order       = wc_create_order( array( 'customer_id' => $customer_id ) );
		$stored      = WC()->session->get( 'new_customer' );

		$stored['session'] = 'another-session';
		WC()->session->set( 'new_customer', $stored );

		$this->assertFalse( $this->sut->is_session_customer_order( $order ), 'Another session should not get guest access to the account orders.' );
		$this->assertWPError( $this->sut->create_customer( 'cloned@example.com' ), 'Another session should not reuse the account.' );
	}

	/**
	 * @testdox A card saved for the logged out shopper goes to the account created in this session.
	 */
	public function test_guest_payment_token_goes_to_the_session_account(): void {
		$customer_id = $this->sut->create_customer( 'token-owner@example.com' );

		$token = $this->save_card_for_user( 0 );

		$this->assertSame( $customer_id, $token->get_user_id(), 'The card should belong to the new account.' );
		$this->assertTrue( $token->is_default(), 'The first card should be the default.' );
		$this->assertSame( $customer_id, \WC_Payment_Tokens::get( $token->get_id() )->get_user_id(), 'The owner should be saved.' );
	}

	/**
	 * @testdox A card saved for a logged out shopper without a session account stays unowned.
	 */
	public function test_guest_payment_token_without_session_account_is_unchanged(): void {
		$token = $this->save_card_for_user( 0 );

		$this->assertSame( 0, $token->get_user_id() );
	}

	/**
	 * @testdox Setting a password from the new account email verifies the account and links the shopper's past guest orders.
	 */
	public function test_password_set_from_new_account_email_verifies_account(): void {
		add_filter( 'pre_wp_mail', '__return_true' );
		// Send the new account email in this request, so its key exists before the assertions.
		add_filter( 'woocommerce_defer_transactional_emails', '__return_false' );
		$this->enable_transactional_emails();
		$emails = WC()->mailer()->get_emails();
		wc_get_container()->get( VerificationEventListener::class );
		$guest_order = wc_create_order();
		$guest_order->set_billing_email( 'set-password@example.com' );
		$guest_order->save();

		$customer_id = $this->sut->create_customer( 'set-password@example.com' );

		$this->assertIsInt( $customer_id, 'An account should be created.' );
		parse_str( (string) wp_parse_url( (string) $emails['WC_Email_Customer_New_Account']->set_password_url, PHP_URL_QUERY ), $query );
		$user = check_password_reset_key( (string) ( $query['key'] ?? '' ), get_user_by( 'id', $customer_id )->user_login );
		$this->assertInstanceOf( \WP_User::class, $user, 'The new account email should carry a valid set-password key.' );
		$verification = wc_get_container()->get( EmailVerificationService::class );
		$this->assertFalse( $verification->is_verified( $customer_id ), 'The account should stay unverified until the password is set.' );

		reset_password( $user, 'chosen-Password-123' );

		$this->assertTrue( $verification->is_verified( $customer_id ), 'Setting the password from the emailed link should verify the account.' );
		$this->assertSame( $customer_id, wc_get_order( $guest_order->get_id() )->get_customer_id(), 'The guest order should be linked to the verified account.' );
	}

	/**
	 * @testdox A password change ends the session link, even when it does not verify the email.
	 */
	public function test_password_change_ends_the_session_link(): void {
		$customer_id = $this->sut->create_customer( 'admin-set-password@example.com' );
		$order       = wc_create_order( array( 'customer_id' => $customer_id ) );

		$this->assertTrue( $this->sut->is_session_customer_order( $order ), 'The session account order should be recognised before the change.' );

		wp_set_password( 'admin-chosen-Password-123', $customer_id );

		$this->assertFalse( wc_get_container()->get( EmailVerificationService::class )->is_verified( $customer_id ), 'Setting the password directly should not verify the email.' );
		$this->assertFalse( $this->sut->is_session_customer_order( $order ), 'The session must not reuse an account that has a new password.' );
		$this->assertWPError( $this->sut->create_customer( 'admin-set-password@example.com' ), 'A retry should find the existing account instead of reusing it.' );
		$this->assertSame( 0, $this->save_card_for_user( 0 )->get_user_id(), 'A card saved later should not go to the account.' );
	}

	/**
	 * @testdox A password change ends the order link, so the order needs a login even when the email is unverified.
	 */
	public function test_password_change_ends_the_order_link(): void {
		$customer_id = $this->sut->create_customer( 'claimed-order@example.com' );
		$order       = wc_create_order( array( 'customer_id' => $customer_id ) );
		$this->sut->mark_order_created_customer( $order, $customer_id );
		$order->save();
		WC()->session->set( 'new_customer', null );
		$order = wc_get_order( $order->get_id() );

		$this->assertTrue( $this->sut->is_unclaimed_customer_order( $order ), 'The marked order should be recognised before the change.' );

		wp_set_password( 'admin-chosen-Password-123', $customer_id );

		$this->assertFalse( $this->sut->is_unclaimed_customer_order( $order ), 'The marker must not apply to an account that has a new password.' );
		$this->assertFalse( current_user_can( 'pay_for_order', $order->get_id() ), 'A logged out shopper must log in to pay.' );
		$this->assertFalse( OrderUtil::guest_can_unlock_order( $order, $order->get_order_key() ), 'A logged out shopper must log in to open the order.' );
	}

	/**
	 * @testdox Logging in to a new account, for example through a social login, ends both links even though the password and email are unchanged.
	 */
	public function test_login_ends_the_links(): void {
		$customer_id = $this->sut->create_customer( 'social-login@example.com' );
		$order       = wc_create_order( array( 'customer_id' => $customer_id ) );
		$this->sut->mark_order_created_customer( $order, $customer_id );
		$order->save();

		$this->assertTrue( $this->sut->is_session_customer_order( $order ), 'The session account order should be recognised before the login.' );

		do_action( 'set_auth_cookie', '', 0, 0, $customer_id, 'auth', '' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertFalse( $this->sut->is_session_customer_order( $order ), 'The session must not reuse an account someone has logged in to.' );
		$this->assertFalse( $this->sut->is_unclaimed_customer_order( wc_get_order( $order->get_id() ) ), 'The marker must not apply to an account someone has logged in to.' );
		$this->assertWPError( $this->sut->create_customer( 'social-login@example.com' ), 'A retry should find the existing account instead of reusing it.' );
	}

	/**
	 * @testdox A login claims the account even when a site turns off the generated password reminder, as single sign-on stores do.
	 */
	public function test_login_claims_account_without_password_reminder(): void {
		add_filter( 'get_user_option_default_password_nag', '__return_false' );
		$customer_id = $this->sut->create_customer( 'sso-login@example.com' );
		$order       = wc_create_order( array( 'customer_id' => $customer_id ) );
		$this->sut->mark_order_created_customer( $order, $customer_id );
		$order->save();

		$this->assertTrue( $this->sut->is_unclaimed_customer_order( $order ), 'The order should be linked before the login.' );

		do_action( 'set_auth_cookie', '', 0, 0, $customer_id, 'auth', '' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertFalse( $this->sut->is_unclaimed_customer_order( wc_get_order( $order->get_id() ) ), 'The login should end the order link.' );
		$this->assertFalse( $this->sut->has_unclaimed_customer(), 'The login should end the session link.' );
	}

	/**
	 * @testdox A plugin that logs the new customer in while the account is created claims it.
	 */
	public function test_login_during_account_creation_claims_account(): void {
		$log_in = static function ( $user_id ) {
			do_action( 'set_auth_cookie', '', 0, 0, $user_id, 'auth', '' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		};
		add_action( 'user_register', $log_in );

		$customer_id = $this->sut->create_customer( 'auto-login@example.com' );

		$this->assertGreaterThan( 0, $customer_id );
		$this->assertFalse( $this->sut->has_unclaimed_customer(), 'The session should not keep an account someone logged in to.' );
	}

	/**
	 * @testdox "Create an account" stays ticked while the session has an unclaimed account, whatever the username setting.
	 */
	public function test_create_account_box_stays_ticked_until_the_account_is_claimed(): void {
		update_option( 'woocommerce_registration_generate_username', 'yes' );
		$customer_id = $this->sut->create_customer( 'ticked@example.com' );

		$this->assertTrue( $this->sut->keep_create_account_checked( false ), 'The retry should use the account.' );

		wp_set_password( 'chosen-Password-123', $customer_id );

		$this->assertFalse( $this->sut->keep_create_account_checked( false ), 'A claimed account should not tick the box.' );
	}

	/**
	 * Save a card token for a user.
	 *
	 * @param int $user_id User ID.
	 * @return \WC_Payment_Token_CC
	 */
	private function save_card_for_user( int $user_id ): \WC_Payment_Token_CC {
		$token = new \WC_Payment_Token_CC();
		$token->set_token( 'tok_' . wp_generate_password( 8, false ) );
		$token->set_gateway_id( 'test_gateway' );
		$token->set_card_type( 'visa' );
		$token->set_last4( '3184' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2030' );
		$token->set_user_id( $user_id );
		$token->save();

		return $token;
	}
}
