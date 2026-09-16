<?php
declare( strict_types = 1 );

/**
 * WC_Email::get_headers() Reply-to tests.
 *
 * @covers WC_Email::get_headers
 */
class WC_Email_Reply_To_Header_Test extends \WC_Unit_Test_Case {

	/**
	 * Load up the email classes since they aren't loaded by default, and
	 * make sure the legacy header format is used regardless of the
	 * environment's feature flag defaults.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-new-order.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-cancelled-order.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-failed-order.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-processing-order.php';
	}

	/**
	 * Restore options changed by the tests.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_email_improvements_enabled' );
		delete_option( 'woocommerce_email_reply_to_enabled' );
		delete_option( 'woocommerce_email_reply_to_name' );
		delete_option( 'woocommerce_email_reply_to_address' );
		delete_option( 'woocommerce_email_from_address' );
		delete_option( 'woocommerce_email_from_name' );

		parent::tearDown();
	}

	/**
	 * Split header text into individual lines, so newline variants used to
	 * smuggle extra headers are all normalized the same way.
	 *
	 * @param string $headers Raw header string.
	 * @return array
	 */
	private function split_headers( string $headers ): array {
		return preg_split( '/\r\n|\r|\n/', $headers );
	}

	/**
	 * Data provider of admin order emails and line-break separators used to
	 * try to inject an extra header via the billing name.
	 *
	 * @return array
	 */
	public function admin_order_email_and_separator_provider(): array {
		$emails = array(
			'new_order'       => WC_Email_New_Order::class,
			'cancelled_order' => WC_Email_Cancelled_Order::class,
			'failed_order'    => WC_Email_Failed_Order::class,
		);

		$cases = array();
		foreach ( $emails as $email_id => $email_class ) {
			// "\r" alone is a regression-only case: wp_mail splits headers on "\n", so this could pass even without the fix.
			foreach ( array( "\r\n", "\n", "\r" ) as $separator_label => $separator ) {
				$cases[ "$email_id, separator index $separator_label" ] = array( $email_class, $separator );
			}
		}

		return $cases;
	}

	/**
	 * @testdox A line break in the billing name does not add an extra header line.
	 * @dataProvider admin_order_email_and_separator_provider
	 *
	 * @param string $email_class Admin order email class to instantiate.
	 * @param string $separator   Line-break variant used inside the billing name.
	 */
	public function test_admin_order_email_billing_name_line_break_does_not_inject_header( string $email_class, string $separator ): void {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_first_name( "Foo{$separator}Bcc: attacker@example.test{$separator}X-Injected:" );
		$order->set_billing_last_name( 'Bar' );
		$order->set_billing_email( 'guest@example.com' );
		$order->save();

		$email         = new $email_class();
		$email->object = $order;

		$headers = $email->get_headers();

		$this->assertSame(
			"Reply-to: Foo Bcc: attacker@example.test X-Injected: Bar <guest@example.com>\r\n",
			$this->extract_reply_to_line( $headers )
		);

		$lines              = $this->split_headers( $headers );
		$reply_to_lines     = array_filter( $lines, static fn( $line ) => 0 === strpos( $line, 'Reply-to:' ) );
		$non_reply_to_lines = array_filter( $lines, static fn( $line ) => 0 !== strpos( $line, 'Reply-to:' ) );

		$this->assertCount( 1, $reply_to_lines, 'Exactly one Reply-to line should be present' );
		foreach ( $non_reply_to_lines as $line ) {
			$this->assertStringNotContainsString( 'attacker@example.test', $line, 'No header other than Reply-to should reference the injected address' );
		}
	}

	/**
	 * Extract the raw "Reply-to: ...\r\n" line, if any, from a header string.
	 *
	 * @param string $headers Raw header string.
	 * @return string
	 */
	private function extract_reply_to_line( string $headers ): string {
		if ( preg_match( '/Reply-to:[^\r\n]*\r\n/', $headers, $matches ) ) {
			return $matches[0];
		}
		return '';
	}

	/**
	 * @testdox A comma in the billing name is removed since wp_mail splits Reply-to on commas.
	 */
	public function test_admin_order_email_billing_name_comma_is_removed(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_first_name( 'Smith,' );
		$order->set_billing_last_name( 'Jr.' );
		$order->set_billing_email( 'guest@example.com' );
		$order->save();

		$email         = new WC_Email_New_Order();
		$email->object = $order;

		$this->assertSame( "Reply-to: Smith Jr. <guest@example.com>\r\n", $this->extract_reply_to_line( $email->get_headers() ) );
	}

	/**
	 * @testdox Legitimate names with accents and apostrophes are unchanged.
	 */
	public function test_admin_order_email_billing_name_with_accents_and_apostrophe_is_unchanged(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_first_name( 'María' );
		$order->set_billing_last_name( "O'Brien" );
		$order->set_billing_email( 'guest@example.com' );
		$order->save();

		$email         = new WC_Email_New_Order();
		$email->object = $order;

		$this->assertSame( "Reply-to: María O'Brien <guest@example.com>\r\n", $this->extract_reply_to_line( $email->get_headers() ) );
	}

	/**
	 * @testdox No Reply-to line is added when the billing email is invalid.
	 */
	public function test_admin_order_email_invalid_billing_email_produces_no_reply_to(): void {
		// WC_Order::set_billing_email() throws on an invalid address, so the
		// order getters are stubbed out instead of going through a real order.
		$order = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'get_billing_first_name', 'get_billing_last_name', 'get_billing_email' ) )
			->getMock();
		$order->method( 'get_billing_first_name' )->willReturn( 'Foo' );
		$order->method( 'get_billing_last_name' )->willReturn( 'Bar' );
		$order->method( 'get_billing_email' )->willReturn( 'not-an-email' );

		$email         = new WC_Email_New_Order();
		$email->object = $order;

		$this->assertSame( '', $this->extract_reply_to_line( $email->get_headers() ) );
	}

	/**
	 * @testdox No Reply-to line is added when the billing name has no visible characters.
	 * @testWith ["\r\n"]
	 *           [" "]
	 *
	 * @param string $name Billing first name made up only of whitespace/line breaks.
	 */
	public function test_admin_order_email_blank_billing_name_produces_no_reply_to( string $name ): void {
		$order = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'get_billing_first_name', 'get_billing_last_name', 'get_billing_email' ) )
			->getMock();
		$order->method( 'get_billing_first_name' )->willReturn( $name );
		$order->method( 'get_billing_last_name' )->willReturn( '' );
		$order->method( 'get_billing_email' )->willReturn( 'guest@example.com' );

		$email         = new WC_Email_New_Order();
		$email->object = $order;

		$this->assertSame( '', $this->extract_reply_to_line( $email->get_headers() ) );
	}

	/**
	 * @testdox Custom reply-to name falling back to a filtered from-name stays on a single line.
	 */
	public function test_custom_reply_to_falls_back_to_from_name_without_injecting_header(): void {
		update_option( 'woocommerce_email_reply_to_enabled', 'yes' );
		update_option( 'woocommerce_email_reply_to_address', 'reply@example.com' );
		update_option( 'woocommerce_email_reply_to_name', '' );

		$filter = static fn() => "Shop\r\nBcc: x@evil.test";
		add_filter( 'woocommerce_email_from_name', $filter );

		$email = new WC_Email_Customer_Processing_Order();

		$headers = $email->get_headers();

		remove_filter( 'woocommerce_email_from_name', $filter );

		$this->assertSame( "Reply-to: Shop  Bcc: x@evil.test <reply@example.com>\r\n", $this->extract_reply_to_line( $headers ) );
		$this->assertStringNotContainsString( 'x@evil.test', str_replace( $this->extract_reply_to_line( $headers ), '', $headers ) );
	}

	/**
	 * @testdox From-name fallback for the reply-to header stays on a single line.
	 */
	public function test_from_name_fallback_reply_to_does_not_inject_header(): void {
		update_option( 'woocommerce_email_reply_to_enabled', 'no' );
		update_option( 'woocommerce_email_from_address', 'from@address.com' );

		$filter = static fn() => "Shop\r\nBcc: x@evil.test";
		add_filter( 'woocommerce_email_from_name', $filter );

		$email = new WC_Email_Customer_Processing_Order();

		$headers = $email->get_headers();

		remove_filter( 'woocommerce_email_from_name', $filter );

		$this->assertSame( "Reply-to: Shop  Bcc: x@evil.test <from@address.com>\r\n", $this->extract_reply_to_line( $headers ) );
		$this->assertStringNotContainsString( 'x@evil.test', str_replace( $this->extract_reply_to_line( $headers ), '', $headers ) );
	}

	/**
	 * @testdox A comma in a filtered from-name is removed for the reply-to header.
	 */
	public function test_from_name_fallback_reply_to_comma_is_removed(): void {
		update_option( 'woocommerce_email_reply_to_enabled', 'no' );
		update_option( 'woocommerce_email_from_address', 'from@address.com' );

		$filter = static fn() => 'Shop, Inc.';
		add_filter( 'woocommerce_email_from_name', $filter );

		$email   = new WC_Email_Customer_Processing_Order();
		$headers = $email->get_headers();

		remove_filter( 'woocommerce_email_from_name', $filter );

		$this->assertSame( "Reply-to: Shop Inc. <from@address.com>\r\n", $this->extract_reply_to_line( $headers ) );
	}
}
