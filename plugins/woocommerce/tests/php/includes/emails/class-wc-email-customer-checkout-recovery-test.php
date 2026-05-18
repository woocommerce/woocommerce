<?php
declare( strict_types = 1 );

/**
 * WC_Email_Customer_Checkout_Recovery test.
 *
 * @covers WC_Email_Customer_Checkout_Recovery
 */
class WC_Email_Customer_Checkout_Recovery_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Email_Customer_Checkout_Recovery
	 */
	private $sut;

	/**
	 * `WC_Emails::init()` only registers the checkout recovery email class
	 * when the `checkout_recovery` feature flag is on, so the suite has to
	 * enable the option (and re-init the mailer to pick up the flag change)
	 * before exercising the mailer-level registration. Doing it here makes
	 * every test self-contained rather than relying on the incidental order
	 * of other suites that flip the flag.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_checkout_recovery_enabled', 'yes' );

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-checkout-recovery.php';

		WC()->mailer()->init();

		$this->sut = new WC_Email_Customer_Checkout_Recovery();
	}

	/**
	 * Reset the feature flag + saved settings between tests so the suite
	 * doesn't leak state into unrelated test classes.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_checkout_recovery_enabled' );
		delete_option( 'woocommerce_customer_checkout_recovery_settings' );

		parent::tearDown();
	}

	/**
	 * @testdox Constructor wires the email id, customer flag, group, and template paths.
	 */
	public function test_constructor_sets_email_identity(): void {
		$this->assertSame( 'customer_checkout_recovery', $this->sut->id );
		$this->assertTrue( $this->sut->is_customer_email() );
		$this->assertSame( 'order-updates', $this->sut->email_group );
		$this->assertSame( 'emails/customer-checkout-recovery.php', $this->sut->template_html );
		$this->assertSame( 'emails/plain/customer-checkout-recovery.php', $this->sut->template_plain );
	}

	/**
	 * @testdox Defaults wire the expected JTBD-framed subject, heading, and additional content.
	 */
	public function test_default_copy(): void {
		$this->assertSame( 'Your items at {site_title} are waiting', $this->sut->get_default_subject() );
		$this->assertSame( 'Pick up where you left off', $this->sut->get_default_heading() );
		$this->assertStringContainsString( 'reply to this email', $this->sut->get_default_additional_content() );
	}

	/**
	 * @testdox Settings form exposes both the standard enabled toggle and the automated toggle, with the chosen defaults (enabled=yes, automated=no).
	 */
	public function test_form_fields_expose_enabled_and_automated(): void {
		$this->sut->init_form_fields();

		$this->assertArrayHasKey( 'enabled', $this->sut->form_fields );
		$this->assertArrayHasKey( 'automated', $this->sut->form_fields );
		$this->assertArrayHasKey( 'subject', $this->sut->form_fields );
		$this->assertArrayHasKey( 'heading', $this->sut->form_fields );
		$this->assertArrayHasKey( 'additional_content', $this->sut->form_fields );

		$this->assertSame( 'yes', $this->sut->form_fields['enabled']['default'] );
		$this->assertSame( 'checkbox', $this->sut->form_fields['enabled']['type'] );

		$this->assertSame( 'no', $this->sut->form_fields['automated']['default'] );
		$this->assertSame( 'checkbox', $this->sut->form_fields['automated']['type'] );
	}

	/**
	 * @testdox is_automated() reflects the saved option and defaults to off when unset.
	 */
	public function test_is_automated_reads_option(): void {
		$this->assertFalse( $this->sut->is_automated() );

		$this->sut->update_option( 'automated', 'yes' );
		$this->assertTrue( $this->sut->is_automated() );

		$this->sut->update_option( 'automated', 'no' );
		$this->assertFalse( $this->sut->is_automated() );
	}

	/**
	 * @testdox get_recovery_url() returns the order's pay endpoint once a valid order is bound to the email.
	 */
	public function test_recovery_url_uses_order_pay_endpoint(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$this->sut->trigger( $order->get_id() );

		$url = $this->sut->get_recovery_url();

		$this->assertSame( $order->get_checkout_payment_url(), $url );
	}

	/**
	 * @testdox get_recovery_url() returns empty string when no order is bound.
	 */
	public function test_recovery_url_empty_without_order(): void {
		$this->assertSame( '', $this->sut->get_recovery_url() );
	}

	/**
	 * @testdox The woocommerce_checkout_recovery_url filter can replace the generated URL so a follow-up can swap in a tokenized URL without touching templates.
	 */
	public function test_recovery_url_is_filterable(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$this->sut->trigger( $order->get_id() );

		$override = static function () {
			return 'https://example.test/custom-recovery';
		};
		add_filter( 'woocommerce_checkout_recovery_url', $override );

		try {
			$this->assertSame( 'https://example.test/custom-recovery', $this->sut->get_recovery_url() );
		} finally {
			remove_filter( 'woocommerce_checkout_recovery_url', $override );
		}
	}

	/**
	 * @testdox Email is registered with WC_Emails when the feature flag is on so the WC Settings → Emails page renders it.
	 */
	public function test_is_registered_with_wc_emails(): void {
		$emails = WC()->mailer()->get_emails();

		$this->assertArrayHasKey( 'WC_Email_Customer_Checkout_Recovery', $emails );
	}

	/**
	 * @testdox Calling trigger() with an invalid order id after a valid call does not dispatch to the previous recipient.
	 */
	public function test_trigger_clears_state_on_invalid_order(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$this->sut->trigger( $order->get_id() );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( 0 );
		$after  = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'trigger() must not send to the previous order\'s recipient when called with an invalid id.' );
		$this->assertSame( '', $this->sut->recipient );
		$this->assertFalse( $this->sut->object );
	}

	/**
	 * @testdox trigger() is a no-op when the email is disabled.
	 */
	public function test_trigger_is_noop_when_disabled(): void {
		$this->sut->update_option( 'enabled', 'no' );
		$this->sut->enabled = 'no';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( $order->get_id() );
		$after  = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'Disabled checkout recovery email must not dispatch any mail.' );
	}

	/**
	 * @testdox trigger() dispatches the email when enabled and the order has a billing email.
	 */
	public function test_trigger_sends_when_enabled(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( $order->get_id() );
		$after  = count( $mailer->mock_sent );

		$this->assertSame( $before + 1, $after, 'Enabled checkout recovery email must dispatch one message.' );
		$this->assertSame( $order->get_billing_email(), $this->sut->recipient );
	}
}
