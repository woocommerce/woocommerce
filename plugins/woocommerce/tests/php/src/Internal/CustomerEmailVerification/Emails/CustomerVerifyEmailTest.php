<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification\Emails;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\Emails\CustomerVerifyEmail;
use WC_Unit_Test_Case;

/**
 * Tests for CustomerVerifyEmail.
 *
 * @covers \Automattic\WooCommerce\Internal\CustomerEmailVerification\Emails\CustomerVerifyEmail
 */
class CustomerVerifyEmailTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CustomerVerifyEmail
	 */
	private $sut;

	/**
	 * Initialise the mailer (loads the WC_Email base class) before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		WC()->mailer()->init();

		$this->sut = new CustomerVerifyEmail();
	}

	/**
	 * @testdox Class is registered with the WC mailer so the Settings > Emails page renders it.
	 */
	public function test_is_registered_with_wc_emails(): void {
		$emails = WC()->mailer()->get_emails();

		$this->assertArrayHasKey( 'WC_Email_Customer_Verify_Email', $emails );
		$this->assertInstanceOf( CustomerVerifyEmail::class, $emails['WC_Email_Customer_Verify_Email'] );
	}

	/**
	 * @testdox trigger() sends an email to the customer containing the verification code.
	 */
	public function test_trigger_sends_email_with_code(): void {
		$user_id = wc_create_new_customer( 'verify@example.com', 'verifytestuser', 'password' );
		$this->assertIsInt( $user_id );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->trigger( $user_id, '654321' );

		$after = count( $mailer->mock_sent );

		$this->assertSame( $before + 1, $after, 'trigger() must dispatch exactly one email.' );

		$sent = $mailer->mock_sent[ $before ];
		$this->assertSame( 'verify@example.com', $sent['to'][0][0], 'Email must be addressed to the customer.' );
		$this->assertStringContainsString( '654321', $sent['body'], 'Email body must contain the verification code.' );
	}

	/**
	 * @testdox trigger() is a no-op when user_id or verify_code is missing.
	 */
	public function test_trigger_noop_without_args(): void {
		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->trigger( 0, '' );

		$after = count( $mailer->mock_sent );
		$this->assertSame( $before, $after, 'trigger() with no args must not send any email.' );
	}
}
