<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormProcessor;

/**
 * WC_Email_Order_Withdrawal_Requested::get_headers() Reply-to tests.
 *
 * @covers WC_Email_Order_Withdrawal_Requested::get_headers
 */
class WC_Email_Order_Withdrawal_Requested_Test extends \WC_Unit_Test_Case {

	/**
	 * Load up the email class since it isn't loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-order-withdrawal-requested.php';
	}

	/**
	 * Build a withdrawal email instance with the given submitter name and email.
	 *
	 * @param string $first_name Submitter first name.
	 * @param string $last_name  Submitter last name.
	 * @param string $email      Submitter email.
	 * @return WC_Email_Order_Withdrawal_Requested
	 */
	private function make_email( string $first_name, string $last_name, string $email ): WC_Email_Order_Withdrawal_Requested {
		$wc_email                  = new WC_Email_Order_Withdrawal_Requested();
		$wc_email->withdrawal_data = array(
			OrderWithdrawalFormProcessor::FIELD_FIRST_NAME => $first_name,
			OrderWithdrawalFormProcessor::FIELD_LAST_NAME  => $last_name,
			OrderWithdrawalFormProcessor::FIELD_EMAIL      => $email,
		);

		return $wc_email;
	}

	/**
	 * @testWith ["Smith,", "Jr.", "Reply-to: Smith Jr. <guest@example.com>\r\n"]
	 *           ["x@evil.test,", "Bob", "Reply-to: x@evil.test Bob <guest@example.com>\r\n"]
	 *           ["Jane", "Doe", "Reply-to: Jane Doe <guest@example.com>\r\n"]
	 *
	 * @param string $first_name     Submitter first name.
	 * @param string $last_name      Submitter last name.
	 * @param string $reply_to_line  Expected Reply-to header line.
	 */
	public function test_submitter_name_reply_to_line( string $first_name, string $last_name, string $reply_to_line ): void {
		$email = $this->make_email( $first_name, $last_name, 'guest@example.com' );

		$this->assertStringContainsString( $reply_to_line, $email->get_headers() );
	}

	/**
	 * @testdox A name made only of a comma produces no Reply-to line.
	 */
	public function test_comma_only_submitter_name_produces_no_reply_to(): void {
		$email = $this->make_email( ',', '', 'guest@example.com' );

		$this->assertStringNotContainsString( 'Reply-to:', $email->get_headers() );
	}
}
