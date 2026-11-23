<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationEventListener;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests that emailed password credentials verify only their bound recipient.
 */
class ImplicitVerificationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var EmailVerificationService
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( EmailVerificationService::class );
		WC()->mailer()->get_emails();

		// Resolve the listener explicitly when the subsystem was bootstrapped before this test.
		wc_get_container()->get( VerificationEventListener::class );
	}

	/**
	 * @testdox Emailed reset and onboarding credentials verify their recipient even when separate confirmation is disabled.
	 * @testWith ["reset"]
	 *           ["new-account"]
	 *           ["core"]
	 * @param string $flow Email delivery flow.
	 */
	public function test_emailed_credential_verifies_bound_address( string $flow ): void {
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		update_option( 'woocommerce_customer_verify_email_settings', array( 'enabled' => 'no' ) );
		$user_id = self::factory()->user->create(
			array(
				'user_email' => 'bound@example.com',
				'role'       => 'customer',
			)
		);
		$user    = get_user_by( 'id', $user_id );
		$order   = WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $user->user_email );
		$order->save();
		$key       = $this->deliver_credential( $user, $flow );
		$validated = check_password_reset_key( $key, $user->user_login );
		$this->assertInstanceOf( \WP_User::class, $validated );

		reset_password( $validated, 'new-password-123' );

		$this->assertTrue( $this->sut->is_verified( $user_id ), 'The emailed credential must verify only its unchanged recipient.' );
		$this->assertSame( $user_id, wc_get_order( $order->get_id() )->get_customer_id(), 'The verified owner should regain matching guest orders.' );
		$this->assertTrue( wp_check_password( 'new-password-123', get_user_by( 'id', $user_id )->user_pass, $user_id ) );
	}

	/**
	 * @testdox Reset credentials cannot verify a different recipient, changed address or superseded issuance.
	 * @testWith ["email-change"]
	 *           ["recipient-change"]
	 *           ["superseded"]
	 *           ["unbound"]
	 *           ["multiple-recipients"]
	 * @param string $scenario Credential mismatch.
	 */
	public function test_reset_binding_rejects_mismatches( string $scenario ): void {
		$user_id = self::factory()->user->create(
			array(
				'user_email' => 'binding@example.com',
				'role'       => 'customer',
			)
		);
		$user    = get_user_by( 'id', $user_id );
		if ( in_array( $scenario, array( 'recipient-change', 'multiple-recipients' ), true ) ) {
			$recipient = 'multiple-recipients' === $scenario ? 'binding@example.com,other@example.com' : 'different@example.com';
			add_filter(
				'woocommerce_email_recipient_customer_reset_password',
				static fn () => $recipient
			);
		}
		$key       = 'unbound' === $scenario ? get_password_reset_key( $user ) : $this->deliver_credential( $user, 'reset' );
		$validated = check_password_reset_key( $key, $user->user_login );
		$this->assertInstanceOf( \WP_User::class, $validated );
		if ( 'email-change' === $scenario ) {
			wp_update_user(
				array(
					'ID'         => $user_id,
					'user_email' => 'changed@example.com',
				)
			);
		} elseif ( 'superseded' === $scenario ) {
			$this->deliver_credential( $user, 'reset' );
		}

		reset_password( $validated, 'new-password-123' );

		$this->assertFalse( $this->sut->is_verified( $user_id ), 'A reset must not turn a mismatched credential into email proof.' );
	}

	/**
	 * @testdox A consumed credential cannot verify the account a second time.
	 */
	public function test_reset_proof_is_consumed(): void {
		$user_id   = self::factory()->user->create(
			array(
				'user_email' => 'once@example.com',
				'role'       => 'customer',
			)
		);
		$user      = get_user_by( 'id', $user_id );
		$key       = $this->deliver_credential( $user, 'reset' );
		$validated = check_password_reset_key( $key, $user->user_login );
		reset_password( $validated, 'new-password-123' );
		$this->assertTrue( $this->sut->is_verified( $user_id ) );
		$this->sut->clear_verification( $user_id );
		wc_get_container()->get( VerificationEventListener::class )->on_password_reset( $validated );
		$this->assertFalse( $this->sut->is_verified( $user_id ) );
	}

	/**
	 * Deliver through a production email flow with the transport intercepted locally.
	 *
	 * @param \WP_User $user Recipient account.
	 * @param string   $flow Email flow.
	 * @return string The synthetic reset key used by the test.
	 */
	private function deliver_credential( \WP_User $user, string $flow ): string {
		add_filter( 'pre_wp_mail', '__return_true' );
		if ( 'new-account' === $flow ) {
			$email = new \WC_Email_Customer_New_Account();
			$email->trigger( $user->ID, '', true );
			parse_str( (string) wp_parse_url( $email->set_password_url, PHP_URL_QUERY ), $query );
			return $query['key'];
		}
		if ( 'core' === $flow ) {
			$key     = '';
			$capture = static function ( $email, $issued_key ) use ( &$key ) {
				$key = $issued_key;
				return $email;
			};
			add_filter( 'retrieve_password_notification_email', $capture, 10, 2 );
			try {
				retrieve_password( $user->user_login );
			} finally {
				remove_filter( 'retrieve_password_notification_email', $capture );
			}
			return $key;
		}
		$key   = get_password_reset_key( $user );
		$email = new \WC_Email_Customer_Reset_Password();
		$email->trigger( $user->user_login, $key );
		return $key;
	}

	/**
	 * @testdox A password reset succeeds without verifying an unverified email.
	 */
	public function test_password_reset_does_not_verify_email(): void {
		$user_id = wc_create_new_customer( 'reset@example.com', 'resetuser', 'pw' );
		$user    = get_user_by( 'id', $user_id );
		$order   = WC_Helper_Order::create_order();
		$order->set_customer_id( 0 );
		$order->set_billing_email( 'reset@example.com' );
		$order->save();
		$verification_events = did_action( 'woocommerce_customer_email_verified' );

		reset_password( $user, 'newpassword123' );
		$updated_user = get_user_by( 'id', $user_id );

		$this->assertTrue( wp_check_password( 'newpassword123', $updated_user->user_pass, $user_id ), 'The ordinary password reset should still succeed.' );
		$this->assertFalse( $this->sut->is_verified( $user_id ), 'A reset must not verify an email without issuance-bound proof.' );
		$this->assertSame( $verification_events, did_action( 'woocommerce_customer_email_verified' ), 'Reset must not emit the email-verification event.' );
		$this->assertSame( 0, wc_get_order( $order->get_id() )->get_customer_id(), 'A reset must not link a matching guest order.' );
	}

	/**
	 * @testdox A password reset preserves verification for an unchanged email.
	 */
	public function test_password_reset_preserves_existing_verification(): void {
		$user_id = wc_create_new_customer( 'verified@example.com', 'verifieduser', 'pw' );
		$this->sut->mark_verified( $user_id, 'verified@example.com' );
		$verification_events = did_action( 'woocommerce_customer_email_verified' );

		reset_password( get_user_by( 'id', $user_id ), 'newpassword123' );

		$this->assertTrue( $this->sut->is_verified( $user_id ), 'An unchanged, already-verified email should remain verified.' );
		$this->assertSame( $verification_events, did_action( 'woocommerce_customer_email_verified' ), 'Reset must not emit another verification event.' );
	}

	/**
	 * @testdox Resetting a password after an email change does not verify the new address.
	 */
	public function test_password_reset_after_email_change_requires_verification(): void {
		$user_id = wc_create_new_customer( 'before@example.com', 'changeduser', 'pw' );
		$this->sut->mark_verified( $user_id, 'before@example.com' );
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'after@example.com',
			)
		);

		reset_password( get_user_by( 'id', $user_id ), 'newpassword123' );

		$this->assertFalse( $this->sut->is_verified( $user_id ), 'The changed address still requires its own verification.' );
	}

	/**
	 * @testdox Direct calls to the compatibility listener cannot verify an email.
	 */
	public function test_legacy_listener_does_not_verify_email(): void {
		$user_id  = wc_create_new_customer( 'legacy@example.com', 'legacyuser', 'pw' );
		$listener = wc_get_container()->get( VerificationEventListener::class );

		$listener->on_password_reset( get_user_by( 'id', $user_id ) );

		$this->assertFalse( $this->sut->is_verified( $user_id ), 'The compatibility method must not bypass explicit verification.' );
	}
}
