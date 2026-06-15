<?php
declare( strict_types = 1 );

/**
 * WC_Email_Customer_Verify_Email test.
 *
 * @covers WC_Email_Customer_Verify_Email
 */
class WC_Email_Customer_Verify_Email_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Email_Customer_Verify_Email
	 */
	private $sut;

	/**
	 * Load email classes and initialise the mailer before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-verify-email.php';

		WC()->mailer()->init();

		$this->sut = new WC_Email_Customer_Verify_Email();
	}

	/**
	 * @testdox Email id is customer_verify_email.
	 */
	public function test_email_id(): void {
		$this->assertSame( 'customer_verify_email', $this->sut->id );
	}

	/**
	 * @testdox Email is marked as a customer email.
	 */
	public function test_customer_email_flag(): void {
		$this->assertTrue( $this->sut->is_customer_email() );
	}

	/**
	 * @testdox Email belongs to the accounts group.
	 */
	public function test_email_group(): void {
		$this->assertSame( 'accounts', $this->sut->email_group );
	}

	/**
	 * @testdox Email is enabled by default.
	 */
	public function test_enabled_by_default(): void {
		$this->assertTrue( $this->sut->is_enabled() );
	}

	/**
	 * @testdox Class is registered with the WC mailer so the Settings > Emails page renders it.
	 */
	public function test_is_registered_with_wc_emails(): void {
		$emails = WC()->mailer()->get_emails();

		$this->assertArrayHasKey( 'WC_Email_Customer_Verify_Email', $emails );
	}

	/**
	 * @testdox trigger() sends an email to the customer containing the verification link.
	 */
	public function test_trigger_sends_email_with_verify_url(): void {
		$user_id = wc_create_new_customer( 'verify@example.com', 'verifytestuser', 'password' );
		$this->assertIsInt( $user_id );

		$verify_url = add_query_arg(
			array(
				'wc_verify_email_key'  => 'TESTKEY',
				'wc_verify_email_user' => $user_id,
			),
			wc_get_page_permalink( 'myaccount' )
		);

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->trigger( $user_id, $verify_url );

		$after = count( $mailer->mock_sent );

		$this->assertSame( $before + 1, $after, 'trigger() must dispatch exactly one email.' );

		$sent = $mailer->mock_sent[ $before ];
		$this->assertSame( 'verify@example.com', $sent['to'][0][0], 'Email must be addressed to the customer.' );
		$this->assertStringContainsString( 'wc_verify_email_key=TESTKEY', $sent['body'], 'Email body must contain the verification key.' );
	}

	/**
	 * @testdox trigger() is a no-op when user_id or verify_url is missing.
	 */
	public function test_trigger_noop_without_args(): void {
		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->trigger( 0, '' );

		$after = count( $mailer->mock_sent );
		$this->assertSame( $before, $after, 'trigger() with no args must not send any email.' );
	}
}
