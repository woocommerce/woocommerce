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
	 * Split header text into individual lines, so newline variants used to
	 * smuggle extra headers are all normalized the same way.
	 *
	 * @param string $headers Raw header string.
	 * @return array
	 */
	private function split_headers( string $headers ): array {
		$normalized = str_replace( array( "\r\n", "\r" ), array( "\n", "\n" ), $headers );
		return explode( "\n", $normalized );
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

		$this->assertStringContainsString(
			"Reply-to: Foo Bcc: attacker@example.test X-Injected: Bar <guest@example.com>\r\n",
			$headers
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

		$this->assertStringContainsString( "Reply-to: Smith Jr. <guest@example.com>\r\n", $email->get_headers() );
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

		$this->assertStringContainsString( "Reply-to: María O'Brien <guest@example.com>\r\n", $email->get_headers() );
	}

	/**
	 * @testdox No Reply-to line is added when the billing email is invalid.
	 */
	public function test_admin_order_email_invalid_billing_email_produces_no_reply_to(): void {
		// WC_Order::set_billing_email() throws on an invalid address, so the
		// order getters are stubbed on a mock instead of going through a real order.
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_billing_first_name' )->willReturn( 'Foo' );
		$order->method( 'get_billing_last_name' )->willReturn( 'Bar' );
		$order->method( 'get_billing_email' )->willReturn( 'not-an-email' );

		$email         = new WC_Email_New_Order();
		$email->object = $order;

		$this->assertStringNotContainsString( 'Reply-to:', $email->get_headers() );
	}

	/**
	 * @testdox No Reply-to line is added when a "sanitize_email" filter hands back a value that fails is_email().
	 */
	public function test_admin_order_email_sanitize_email_filter_returning_invalid_address_produces_no_reply_to(): void {
		// sanitize_email() ends with an apply_filters( 'sanitize_email', ... ) call, so a callback can
		// hand back a non-empty value that does not pass is_email().
		$filter = static fn() => 'not-an-email';
		add_filter( 'sanitize_email', $filter );

		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_billing_first_name' )->willReturn( 'Foo' );
		$order->method( 'get_billing_last_name' )->willReturn( 'Bar' );
		$order->method( 'get_billing_email' )->willReturn( 'guest@example.com' );

		$email         = new WC_Email_New_Order();
		$email->object = $order;

		try {
			$headers = $email->get_headers();
		} finally {
			remove_filter( 'sanitize_email', $filter );
		}

		$this->assertStringNotContainsString( 'Reply-to:', $headers );
	}

	/**
	 * @testdox No Reply-to line is added when the billing name has no visible characters.
	 * @testWith ["\r\n"]
	 *           [" "]
	 *
	 * @param string $name Billing first name made up only of whitespace/line breaks.
	 */
	public function test_admin_order_email_blank_billing_name_produces_no_reply_to( string $name ): void {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_billing_first_name' )->willReturn( $name );
		$order->method( 'get_billing_last_name' )->willReturn( '' );
		$order->method( 'get_billing_email' )->willReturn( 'guest@example.com' );

		$email         = new WC_Email_New_Order();
		$email->object = $order;

		$this->assertStringNotContainsString( 'Reply-to:', $email->get_headers() );
	}

	/**
	 * @testdox A billing name of "0" is not treated as empty.
	 */
	public function test_admin_order_email_billing_name_of_zero_is_kept(): void {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_billing_first_name' )->willReturn( '0' );
		$order->method( 'get_billing_last_name' )->willReturn( '' );
		$order->method( 'get_billing_email' )->willReturn( 'guest@example.com' );

		$email         = new WC_Email_New_Order();
		$email->object = $order;

		$this->assertStringContainsString( "Reply-to: 0 <guest@example.com>\r\n", $email->get_headers() );
	}

	/**
	 * @testdox No Reply-to line is added when the object is not a WC_Order.
	 */
	public function test_admin_order_email_non_order_object_produces_no_reply_to(): void {
		$order = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'get_billing_first_name', 'get_billing_last_name', 'get_billing_email' ) )
			->getMock();
		$order->method( 'get_billing_first_name' )->willReturn( 'Foo' );
		$order->method( 'get_billing_last_name' )->willReturn( 'Bar' );
		$order->method( 'get_billing_email' )->willReturn( 'guest@example.com' );

		$email         = new WC_Email_New_Order();
		$email->object = $order;

		$this->assertStringNotContainsString( 'Reply-to:', $email->get_headers() );
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

		$reply_to_line = "Reply-to: Shop Bcc: x@evil.test <reply@example.com>\r\n";
		$this->assertStringContainsString( $reply_to_line, $headers );
		$this->assertStringNotContainsString( 'x@evil.test', str_replace( $reply_to_line, '', $headers ) );
	}

	/**
	 * @testdox A configured reply-to name left empty by the cleanup falls back to the from-name.
	 */
	public function test_custom_reply_to_name_that_cleans_to_nothing_falls_back_to_from_name(): void {
		update_option( 'woocommerce_email_reply_to_enabled', 'yes' );
		update_option( 'woocommerce_email_reply_to_address', 'reply@example.com' );
		update_option( 'woocommerce_email_reply_to_name', ',' );

		$filter = static fn() => 'Shop';
		add_filter( 'woocommerce_email_from_name', $filter );

		$email   = new WC_Email_Customer_Processing_Order();
		$headers = $email->get_headers();

		remove_filter( 'woocommerce_email_from_name', $filter );

		$this->assertStringContainsString( "Reply-to: Shop <reply@example.com>\r\n", $headers );
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

		$reply_to_line = "Reply-to: Shop Bcc: x@evil.test <from@address.com>\r\n";
		$this->assertStringContainsString( $reply_to_line, $headers );
		$this->assertStringNotContainsString( 'x@evil.test', str_replace( $reply_to_line, '', $headers ) );
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

		$this->assertStringContainsString( "Reply-to: Shop Inc. <from@address.com>\r\n", $headers );
	}

	/**
	 * @testdox An address in angle brackets inside a filtered from-name does not reach the Reply-to header.
	 */
	public function test_from_name_fallback_reply_to_strips_embedded_address(): void {
		update_option( 'woocommerce_email_reply_to_enabled', 'no' );
		update_option( 'woocommerce_email_from_address', 'from@address.com' );

		$filter = static fn() => 'Support <help@attacker.test>';
		add_filter( 'woocommerce_email_from_name', $filter );

		$email   = new WC_Email_Customer_Processing_Order();
		$headers = $email->get_headers();

		remove_filter( 'woocommerce_email_from_name', $filter );

		$this->assertStringContainsString( 'Reply-to: Support <from@address.com>' . "\r\n", $headers );
		$this->assertStringNotContainsString( 'help@attacker.test', $headers );
	}
}
