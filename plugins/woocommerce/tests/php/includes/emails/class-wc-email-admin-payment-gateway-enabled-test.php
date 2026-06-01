<?php
declare( strict_types = 1 );

/**
 * WC_Email_Admin_Payment_Gateway_Enabled test.
 *
 * @covers WC_Email_Admin_Payment_Gateway_Enabled
 */
class WC_Email_Admin_Payment_Gateway_Enabled_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Email_Admin_Payment_Gateway_Enabled
	 */
	private $sut;

	/**
	 * Load up the email classes and create the SUT.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-admin-payment-gateway-enabled.php';

		$this->sut = new WC_Email_Admin_Payment_Gateway_Enabled();
	}

	/**
	 * @testdox Email has correct ID set to 'admin_payment_gateway_enabled'.
	 */
	public function test_email_has_correct_id(): void {
		$this->assertEquals( 'admin_payment_gateway_enabled', $this->sut->id );
	}

	/**
	 * @testdox Email has correct title.
	 */
	public function test_email_has_correct_title(): void {
		$this->assertEquals( 'Payment gateway enabled', $this->sut->title );
	}

	/**
	 * @testdox Email belongs to the payments group.
	 */
	public function test_email_belongs_to_payments_group(): void {
		$this->assertEquals( 'payments', $this->sut->email_group );
	}

	/**
	 * @testdox Email is not a customer email.
	 */
	public function test_email_is_not_customer_email(): void {
		$this->assertFalse( $this->sut->is_customer_email() );
	}

	/**
	 * @testdox Email has the correct HTML template path.
	 */
	public function test_email_has_correct_html_template(): void {
		$this->assertEquals( 'emails/admin-payment-gateway-enabled.php', $this->sut->template_html );
	}

	/**
	 * @testdox Email has the correct plain text template path.
	 */
	public function test_email_has_correct_plain_template(): void {
		$this->assertEquals( 'emails/plain/admin-payment-gateway-enabled.php', $this->sut->template_plain );
	}

	/**
	 * @testdox Email has gateway_title and site_title placeholders.
	 */
	public function test_email_has_correct_placeholders(): void {
		$this->assertArrayHasKey( '{gateway_title}', $this->sut->placeholders );
		$this->assertArrayHasKey( '{site_title}', $this->sut->placeholders );
	}

	/**
	 * @testdox Default subject contains gateway_title placeholder.
	 */
	public function test_default_subject_contains_gateway_title_placeholder(): void {
		$subject = $this->sut->get_default_subject();

		$this->assertStringContainsString( '{gateway_title}', $subject );
		$this->assertStringContainsString( '{site_title}', $subject );
	}

	/**
	 * @testdox Default heading contains gateway_title placeholder.
	 */
	public function test_default_heading_contains_gateway_title_placeholder(): void {
		$heading = $this->sut->get_default_heading();

		$this->assertStringContainsString( '{gateway_title}', $heading );
	}

	/**
	 * @testdox Default recipient is the admin email.
	 */
	public function test_default_recipient_is_admin_email(): void {
		$recipient = $this->sut->get_recipient();

		$this->assertEquals( get_option( 'admin_email' ), $recipient );
	}

	/**
	 * @testdox Trigger sets gateway data and sends email when enabled.
	 */
	public function test_trigger_sends_email_when_enabled(): void {
		$email_sent = false;
		$watcher    = function ( $args ) use ( &$email_sent ) {
			$email_sent = true;
			return $args;
		};
		add_filter( 'wp_mail', $watcher );

		$gateway = new WC_Gateway_BACS();
		$this->sut->trigger( $gateway );

		$this->assertTrue( $email_sent, 'Email should be sent when trigger is called with a valid gateway' );

		remove_filter( 'wp_mail', $watcher );
	}

	/**
	 * @testdox Trigger populates gateway_title placeholder correctly.
	 */
	public function test_trigger_populates_gateway_title(): void {
		add_filter( 'wp_mail', '__return_empty_array' );

		$gateway = new WC_Gateway_BACS();
		$this->sut->trigger( $gateway );

		$this->assertEquals( $gateway->get_method_title(), $this->sut->gateway_title );
		$this->assertEquals( $gateway->get_method_title(), $this->sut->placeholders['{gateway_title}'] );

		remove_filter( 'wp_mail', '__return_empty_array' );
	}

	/**
	 * @testdox Trigger computes the gateway settings URL.
	 */
	public function test_trigger_computes_gateway_settings_url(): void {
		add_filter( 'wp_mail', '__return_empty_array' );

		$gateway = new WC_Gateway_BACS();
		$this->sut->trigger( $gateway );

		$this->assertStringContainsString( 'page=wc-settings&tab=checkout&section=bacs', $this->sut->gateway_settings_url );

		remove_filter( 'wp_mail', '__return_empty_array' );
	}

	/**
	 * @testdox Trigger does not send email when email is disabled.
	 */
	public function test_trigger_does_not_send_when_disabled(): void {
		$this->sut->update_option( 'enabled', 'no' );
		$this->sut->enabled = 'no';

		$email_sent = false;
		$watcher    = function ( $args ) use ( &$email_sent ) {
			$email_sent = true;
			return $args;
		};
		add_filter( 'wp_mail', $watcher );

		$gateway = new WC_Gateway_BACS();
		$this->sut->trigger( $gateway );

		$this->assertFalse( $email_sent, 'Email should not be sent when email notification is disabled' );

		remove_filter( 'wp_mail', $watcher );

		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';
	}

	/**
	 * @testdox Email subject resolves gateway_title placeholder after trigger.
	 */
	public function test_subject_resolves_placeholder_after_trigger(): void {
		add_filter( 'wp_mail', '__return_empty_array' );

		$gateway = new WC_Gateway_BACS();
		$this->sut->trigger( $gateway );

		$subject = $this->sut->get_subject();

		$this->assertStringContainsString( $gateway->get_method_title(), $subject );
		$this->assertStringNotContainsString( '{gateway_title}', $subject );

		remove_filter( 'wp_mail', '__return_empty_array' );
	}

	/**
	 * @testdox get_recipient merges addresses from backward-compat filter.
	 */
	public function test_get_recipient_merges_backward_compat_filter_addresses(): void {
		$gateway           = new WC_Gateway_BACS();
		$this->sut->object = $gateway;

		$extra_watcher = function () {
			return array( 'extra@example.com' );
		};
		add_filter( 'wc_payment_gateway_enabled_notification_email_addresses', $extra_watcher );

		$recipient = $this->sut->get_recipient();

		$this->assertStringContainsString( 'extra@example.com', $recipient );
		$this->assertStringContainsString( get_option( 'admin_email' ), $recipient );

		remove_filter( 'wc_payment_gateway_enabled_notification_email_addresses', $extra_watcher );
	}

	/**
	 * @testdox HTML content includes gateway title and settings URL.
	 */
	public function test_html_content_includes_gateway_details(): void {
		$gateway                         = new WC_Gateway_BACS();
		$this->sut->object               = $gateway;
		$this->sut->gateway_title        = $gateway->get_method_title();
		$this->sut->gateway_settings_url = 'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bacs';
		$this->sut->username             = 'admin';
		$this->sut->admin_email          = 'admin@example.com';

		$content = $this->sut->get_content_html();

		$this->assertStringContainsString( $gateway->get_method_title(), $content );
		$this->assertStringContainsString( 'wc-settings', $content );
		$this->assertStringContainsString( 'If you did not enable this payment gateway', $content );
	}

	/**
	 * @testdox Plain text content includes gateway title and settings URL.
	 */
	public function test_plain_text_content_includes_gateway_details(): void {
		$gateway                         = new WC_Gateway_BACS();
		$this->sut->object               = $gateway;
		$this->sut->gateway_title        = $gateway->get_method_title();
		$this->sut->gateway_settings_url = 'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bacs';
		$this->sut->username             = 'admin';
		$this->sut->admin_email          = 'admin@example.com';

		$this->sut->update_option( 'email_type', 'plain' );

		$content = $this->sut->get_content_plain();

		$this->assertStringContainsString( $gateway->get_method_title(), $content );
		$this->assertStringContainsString( 'wc-settings', $content );
		$this->assertStringContainsString( 'If you did not enable this payment gateway', $content );

		$this->sut->update_option( 'email_type', 'html' );
	}

	/**
	 * @testdox Form fields are initialized correctly.
	 */
	public function test_form_fields_are_initialized(): void {
		$this->sut->init_form_fields();

		$this->assertArrayHasKey( 'enabled', $this->sut->form_fields );
		$this->assertArrayHasKey( 'recipient', $this->sut->form_fields );
		$this->assertArrayHasKey( 'subject', $this->sut->form_fields );
		$this->assertArrayHasKey( 'heading', $this->sut->form_fields );
		$this->assertArrayHasKey( 'additional_content', $this->sut->form_fields );
		$this->assertArrayHasKey( 'email_type', $this->sut->form_fields );
	}

	/**
	 * @testdox Email is registered in WC_Emails.
	 */
	public function test_email_is_registered_in_wc_emails(): void {
		$emails = new WC_Emails();

		$this->assertArrayHasKey( 'WC_Email_Admin_Payment_Gateway_Enabled', $emails->emails );
		$this->assertInstanceOf( WC_Email_Admin_Payment_Gateway_Enabled::class, $emails->emails['WC_Email_Admin_Payment_Gateway_Enabled'] );
	}

	/**
	 * @testdox Default additional content is not empty.
	 */
	public function test_default_additional_content_is_not_empty(): void {
		$content = $this->sut->get_default_additional_content();

		$this->assertNotEmpty( $content );
	}

	/**
	 * @testdox block_content outputs gateway title and settings URL for this email.
	 */
	public function test_block_content_outputs_gateway_details(): void {
		$gateway                         = new WC_Gateway_BACS();
		$this->sut->object               = $gateway;
		$this->sut->gateway_title        = $gateway->get_method_title();
		$this->sut->gateway_settings_url = 'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bacs';

		ob_start();
		$this->sut->block_content( true, false, $this->sut );
		$output = ob_get_clean();

		$this->assertStringContainsString( $gateway->get_method_title(), $output, 'Block content should include gateway title' );
		$this->assertStringContainsString( 'wc-settings', $output, 'Block content should include gateway settings URL' );
		$this->assertStringContainsString( 'If you did not enable this payment gateway', $output, 'Block content should include security notice' );
	}

	/**
	 * @testdox block_content does not output for other email types.
	 */
	public function test_block_content_skips_other_email_ids(): void {
		$other_email     = new WC_Email();
		$other_email->id = 'customer_new_order';

		ob_start();
		$this->sut->block_content( true, false, $other_email );
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Block content should not output anything for other email types' );
	}
}
