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
	 * Extract the raw "Reply-to: ...\r\n" line, if any, from a header string.
	 *
	 * @param string $headers Raw header string.
	 * @return string
	 */
	private function extract_reply_to_line( string $headers ): string {
		$start = strpos( $headers, 'Reply-to:' );
		if ( false === $start ) {
			return '';
		}

		$end = strpos( $headers, "\r\n", $start );
		if ( false === $end ) {
			return '';
		}

		return substr( $headers, $start, $end - $start + strlen( "\r\n" ) );
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
	 * @testdox A comma in the submitted name is removed since wp_mail splits Reply-to on commas.
	 */
	public function test_comma_in_submitter_name_is_removed(): void {
		$email = $this->make_email( 'Smith,', 'Jr.', 'guest@example.com' );

		$this->assertSame( "Reply-to: Smith Jr. <guest@example.com>\r\n", $this->extract_reply_to_line( $email->get_headers() ) );
	}

	/**
	 * @testdox A comma used to try to smuggle an extra Reply-to address is removed.
	 */
	public function test_comma_separated_address_in_submitter_name_is_removed(): void {
		$email = $this->make_email( 'x@evil.test,', 'Bob', 'guest@example.com' );

		$headers       = $email->get_headers();
		$reply_to_line = $this->extract_reply_to_line( $headers );

		$this->assertStringNotContainsString( ',', $reply_to_line );
		$this->assertSame( "Reply-to: x@evil.test Bob <guest@example.com>\r\n", $reply_to_line );
	}

	/**
	 * @testdox A name made only of a comma produces no Reply-to line.
	 */
	public function test_comma_only_submitter_name_produces_no_reply_to(): void {
		$email = $this->make_email( ',', '', 'guest@example.com' );

		$this->assertSame( '', $this->extract_reply_to_line( $email->get_headers() ) );
	}

	/**
	 * @testdox A normal name is left unchanged.
	 */
	public function test_normal_submitter_name_is_unchanged(): void {
		$email = $this->make_email( 'Jane', 'Doe', 'guest@example.com' );

		$this->assertSame( "Reply-to: Jane Doe <guest@example.com>\r\n", $this->extract_reply_to_line( $email->get_headers() ) );
	}
}
