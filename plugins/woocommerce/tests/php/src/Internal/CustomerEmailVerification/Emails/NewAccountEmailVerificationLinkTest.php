<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification\Emails;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\Emails\NewAccountEmailVerificationLink;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController;
use WC_Email_Customer_New_Account;
use WC_Unit_Test_Case;

/**
 * Tests for NewAccountEmailVerificationLink.
 *
 * @covers \Automattic\WooCommerce\Internal\CustomerEmailVerification\Emails\NewAccountEmailVerificationLink
 */
class NewAccountEmailVerificationLinkTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NewAccountEmailVerificationLink
	 */
	private $sut;

	/**
	 * Verification controller.
	 *
	 * @var VerificationController
	 */
	private $ctrl;

	/**
	 * Verification service.
	 *
	 * @var EmailVerificationService
	 */
	private $service;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		WC()->mailer()->init();

		$this->service = wc_get_container()->get( EmailVerificationService::class );
		$this->ctrl    = wc_get_container()->get( VerificationController::class );
		$this->sut     = wc_get_container()->get( NewAccountEmailVerificationLink::class );
	}

	/**
	 * Return a WC_Email_Customer_New_Account instance for the given user ID.
	 *
	 * @param bool $password_generated Whether to simulate an auto-generated password.
	 * @return WC_Email_Customer_New_Account
	 */
	private function make_email( bool $password_generated ): WC_Email_Customer_New_Account {
		/** @var WC_Email_Customer_New_Account $email */
		$email                     = WC()->mailer()->emails['WC_Email_Customer_New_Account'];
		$email->password_generated = $password_generated;
		return $email;
	}

	/**
	 * @testdox build_verification_url() returns a URL containing both wc_verify_email_key and wc_verify_email_user.
	 */
	public function test_build_verification_url_contains_required_args(): void {
		$user_id = wc_create_new_customer( 'buildurl@example.com', 'buildurluser', 'pw' );

		$url = $this->ctrl->build_verification_url( $user_id );

		$this->assertStringContainsString( 'wc_verify_email_key=', $url );
		$this->assertStringContainsString( 'wc_verify_email_user=' . $user_id, $url );
	}

	/**
	 * @testdox build_verification_url() stores a key that can be validated by check_verification_key().
	 */
	public function test_build_verification_url_creates_valid_key(): void {
		$user_id = wc_create_new_customer( 'buildvalidkey@example.com', 'buildvalidkeyuser', 'pw' );

		$url = $this->ctrl->build_verification_url( $user_id );

		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );

		$this->assertArrayHasKey( 'wc_verify_email_key', $args );
		$this->assertTrue(
			$this->service->check_verification_key( $user_id, $args['wc_verify_email_key'] ),
			'The key returned by build_verification_url() must pass check_verification_key()'
		);
	}

	/**
	 * @testdox append_verify_link() adds a verify link when the password was not generated.
	 */
	public function test_append_verify_link_added_when_password_not_generated(): void {
		$user_id = wc_create_new_customer( 'selfpass@example.com', 'selfpassuser', 'pw' );
		$user    = get_user_by( 'id', $user_id );
		$email   = $this->make_email( false );

		$result = $this->sut->append_verify_link( 'Existing content.', $user, $email );

		$this->assertStringContainsString( 'wc_verify_email_key=', $result );
		$this->assertStringContainsString( 'Existing content.', $result );
	}

	/**
	 * @testdox append_verify_link() does NOT add a verify link when the password was auto-generated.
	 */
	public function test_append_verify_link_not_added_when_password_generated(): void {
		$user_id = wc_create_new_customer( 'genpass@example.com', 'genpassuser', 'pw' );
		$user    = get_user_by( 'id', $user_id );
		$email   = $this->make_email( true );

		$result = $this->sut->append_verify_link( 'Existing content.', $user, $email );

		$this->assertStringNotContainsString( 'wc_verify_email_key=', $result );
		$this->assertSame( 'Existing content.', $result );
	}

	/**
	 * @testdox append_verify_link() returns content unchanged when $object is not a WP_User.
	 */
	public function test_append_verify_link_noop_when_object_not_wp_user(): void {
		$email = $this->make_email( false );

		$result = $this->sut->append_verify_link( 'Existing content.', false, $email );

		$this->assertSame( 'Existing content.', $result );
	}

	/**
	 * @testdox append_verify_link() with empty existing content returns only the verify paragraph (no leading separator).
	 */
	public function test_append_verify_link_when_no_existing_content(): void {
		$user_id = wc_create_new_customer( 'emptycontent@example.com', 'emptycontentuser', 'pw' );
		$user    = get_user_by( 'id', $user_id );
		$email   = $this->make_email( false );

		$result = $this->sut->append_verify_link( '', $user, $email );

		$this->assertStringContainsString( 'wc_verify_email_key=', $result );
		$this->assertStringNotContainsString( "\n\n", $result );
	}

	/**
	 * @testdox The filter is registered for woocommerce_email_additional_content_customer_new_account via register().
	 */
	public function test_register_attaches_filter(): void {
		// Ensure register() was called (it's idempotent).
		$this->sut->register();

		$this->assertNotFalse(
			has_filter( 'woocommerce_email_additional_content_customer_new_account', array( $this->sut, 'append_verify_link' ) )
		);
	}
}
