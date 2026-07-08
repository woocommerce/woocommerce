<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * WC_Email_Customer_Abandoned_Cart_Recovery test.
 *
 * @covers WC_Email_Customer_Abandoned_Cart_Recovery
 */
class WC_Email_Customer_Abandoned_Cart_Recovery_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Email_Customer_Abandoned_Cart_Recovery
	 */
	private $sut;

	/**
	 * Snapshot of the `active_plugins` option taken in setUp so tests that
	 * mock a known recovery handler can restore the original list in tearDown.
	 *
	 * @var array
	 */
	private $original_active_plugins = array();

	/**
	 * Admin user id created for cap-gated assertions.
	 *
	 * @var int
	 */
	private $admin_user_id = 0;

	/**
	 * `WC_Emails::init()` only registers the abandoned cart recovery email class
	 * when the `abandoned_cart_recovery` feature flag is on, so the suite has to
	 * enable the option (and re-init the mailer to pick up the flag change)
	 * before exercising the mailer-level registration. Doing it here makes
	 * every test self-contained rather than relying on the incidental order
	 * of other suites that flip the flag.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_abandoned_cart_recovery_enabled', 'yes' );

		$this->original_active_plugins = (array) get_option( 'active_plugins', array() );

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-abandoned-cart-recovery.php';

		WC()->mailer()->init();

		$this->sut = new WC_Email_Customer_Abandoned_Cart_Recovery();
	}

	/**
	 * Reset the feature flag + saved settings between tests so the suite
	 * doesn't leak state into unrelated test classes.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_abandoned_cart_recovery_enabled' );
		delete_option( 'woocommerce_customer_abandoned_cart_recovery_settings' );
		update_option( 'active_plugins', $this->original_active_plugins );

		if ( $this->admin_user_id ) {
			wp_set_current_user( 0 );
			wp_delete_user( $this->admin_user_id );
			$this->admin_user_id = 0;
		}

		remove_all_actions( 'woocommerce_send_abandoned_cart_recovery_notification' );

		parent::tearDown();
	}

	/**
	 * Switch the current user to an administrator so capability-gated paths run.
	 */
	private function become_admin(): void {
		$this->admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user_id );
	}

	/**
	 * Backdate the order's `date_created` so it clears the abandonment age threshold.
	 *
	 * `OrderHelper::create_order()` returns a freshly-created order, which by
	 * definition has not yet been abandoned for the required duration. Tests that
	 * exercise the post-threshold path call this helper to age the order past it.
	 *
	 * @param WC_Order $order Order to age.
	 * @return WC_Order Reloaded order reflecting the persisted date.
	 */
	private function age_order_past_threshold( WC_Order $order ): WC_Order {
		$order->set_date_created( time() - WC_Email_Customer_Abandoned_Cart_Recovery::ABANDONMENT_THRESHOLD_SECONDS - MINUTE_IN_SECONDS );
		$order->save();
		return wc_get_order( $order->get_id() );
	}

	/**
	 * @testdox Constructor wires the email id, customer flag, group, and template paths (HTML, plain, block).
	 */
	public function test_constructor_sets_email_identity(): void {
		$this->assertSame( 'customer_abandoned_cart_recovery', $this->sut->id );
		$this->assertTrue( $this->sut->is_customer_email() );
		$this->assertSame( 'order-updates', $this->sut->email_group );
		$this->assertSame( 'emails/customer-abandoned-cart-recovery.php', $this->sut->template_html );
		$this->assertSame( 'emails/plain/customer-abandoned-cart-recovery.php', $this->sut->template_plain );
		$this->assertSame( 'emails/block/customer-abandoned-cart-recovery.php', $this->sut->template_block );
	}

	/**
	 * @testdox Constructor declares the placeholders the default copy and the Available placeholders hint advertise.
	 */
	public function test_constructor_declares_expected_placeholders(): void {
		$this->assertArrayHasKey( '{site_title}', $this->sut->placeholders );
		$this->assertArrayHasKey( '{site_address}', $this->sut->placeholders );
		$this->assertArrayHasKey( '{order_date}', $this->sut->placeholders );
		$this->assertArrayHasKey( '{order_number}', $this->sut->placeholders );
	}

	/**
	 * @testdox Defaults wire the expected JTBD-framed subject, heading, and additional content.
	 */
	public function test_default_copy(): void {
		$this->assertSame( 'Still want it?', $this->sut->get_default_subject() );
		$this->assertSame( 'Pick up where you left off', $this->sut->get_default_heading() );
		$this->assertStringContainsString( 'reply to this email', $this->sut->get_default_additional_content() );
	}

	/**
	 * @testdox Settings form exposes enabled + automated as checkboxes with the chosen defaults (enabled=yes, automated=no). No separate suppress toggle — handler detection drives the enabled default instead.
	 */
	public function test_form_fields_expose_enabled_and_automated(): void {
		$this->sut->init_form_fields();

		$this->assertArrayHasKey( 'enabled', $this->sut->form_fields );
		$this->assertArrayHasKey( 'automated', $this->sut->form_fields );
		$this->assertArrayHasKey( 'subject', $this->sut->form_fields );
		$this->assertArrayHasKey( 'heading', $this->sut->form_fields );
		$this->assertArrayHasKey( 'additional_content', $this->sut->form_fields );

		$this->assertArrayNotHasKey( 'suppressed', $this->sut->form_fields, 'Suppress toggle should be consolidated into the enabled default.' );

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
	 * @testdox The woocommerce_abandoned_cart_recovery_url filter can replace the generated URL so a follow-up can swap in a tokenized URL without touching templates.
	 */
	public function test_recovery_url_is_filterable(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$this->sut->trigger( $order->get_id() );

		$override = static function () {
			return 'https://example.test/custom-recovery';
		};
		add_filter( 'woocommerce_abandoned_cart_recovery_url', $override );

		try {
			$this->assertSame( 'https://example.test/custom-recovery', $this->sut->get_recovery_url() );
		} finally {
			remove_filter( 'woocommerce_abandoned_cart_recovery_url', $override );
		}
	}

	/**
	 * @testdox Email is registered with WC_Emails when the feature flag is on so the WC Settings → Emails page renders it.
	 */
	public function test_is_registered_with_wc_emails(): void {
		$emails = WC()->mailer()->get_emails();

		$this->assertArrayHasKey( 'WC_Email_Customer_Abandoned_Cart_Recovery', $emails );
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
		$after = count( $mailer->mock_sent );

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
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'Disabled abandoned cart recovery email must not dispatch any mail.' );
	}

	/**
	 * @testdox trigger() dispatches the email when enabled and the order has a billing email and is past the abandonment threshold.
	 */
	public function test_trigger_sends_when_enabled(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( $order->get_id() );
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before + 1, $after, 'Enabled abandoned cart recovery email must dispatch one message.' );
		$this->assertSame( $order->get_billing_email(), $this->sut->recipient );
	}

	/**
	 * @testdox trigger() records the send timestamp on order meta so the future auto-send dedup can skip already-emailed orders.
	 */
	public function test_trigger_records_sent_at_meta(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$before_ts = time();
		$this->sut->trigger( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$saved = $fresh->get_meta( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT );

		$this->assertNotEmpty( $saved, 'Successful trigger() must persist the sent_at meta.' );
		$this->assertGreaterThanOrEqual( $before_ts, (int) $saved );
		$this->assertLessThanOrEqual( time() + 1, (int) $saved );
	}

	/**
	 * @testdox trigger() does not dispatch when META_KEY_SENT_AT is already populated, so a duplicate AS firing or a re-trigger after a successful send cannot double-email the customer.
	 */
	public function test_trigger_skips_when_already_sent(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );
		$order->update_meta_data( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT, (string) time() );
		$order->save();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( $order->get_id() );
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'Order already marked as sent must not receive a second dispatch.' );
	}

	/**
	 * @testdox trigger() does not write the sent_at meta when the email is disabled (no send happened).
	 */
	public function test_trigger_does_not_record_meta_when_disabled(): void {
		$this->sut->update_option( 'enabled', 'no' );
		$this->sut->enabled = 'no';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$this->sut->trigger( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT ) );
	}

	/**
	 * @testdox trigger() is a no-op when the woocommerce_abandoned_cart_recovery_suppress filter returns true.
	 */
	public function test_trigger_is_suppressed_when_filter_returns_true(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$override = static fn() => true;
		add_filter( 'woocommerce_abandoned_cart_recovery_suppress', $override );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		try {
			$this->sut->trigger( $order->get_id() );
			$after = count( $mailer->mock_sent );
		} finally {
			remove_filter( 'woocommerce_abandoned_cart_recovery_suppress', $override );
		}

		$this->assertSame( $before, $after, 'Filter-suppressed abandoned cart recovery email must not dispatch.' );
	}

	/**
	 * @testdox trigger() refuses to send when the order has moved past the abandoned-checkout statuses, even if the action is invoked directly with the order id.
	 */
	public function test_trigger_skips_when_order_not_pending(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( $order->get_id() );
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'Recovery email must not dispatch for non-abandoned orders.' );
	}

	/**
	 * @testdox The woocommerce_abandoned_cart_recovery_eligible_statuses filter widens the eligible set for trigger().
	 */
	public function test_trigger_eligible_statuses_filter_can_widen(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_status( 'failed' );
		$order->save();
		$order = $this->age_order_past_threshold( $order );

		$widen = static function () {
			return array( 'pending', 'failed' );
		};
		add_filter( 'woocommerce_abandoned_cart_recovery_eligible_statuses', $widen );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		try {
			$this->sut->trigger( $order->get_id() );
			$after = count( $mailer->mock_sent );
		} finally {
			remove_filter( 'woocommerce_abandoned_cart_recovery_eligible_statuses', $widen );
		}

		$this->assertSame( $before + 1, $after, 'Widened filter must allow non-default statuses to receive the email.' );
	}

	/**
	 * @testdox is_suppressed() returns false by default so the email isn't blocked when no partner filter is registered.
	 */
	public function test_is_suppressed_defaults_to_false(): void {
		$this->assertFalse( WC_Email_Customer_Abandoned_Cart_Recovery::is_suppressed() );
	}

	/**
	 * @testdox get_active_recovery_handlers() returns empty when no known recovery-handling plugin is active.
	 */
	public function test_active_recovery_handlers_empty_when_none_active(): void {
		update_option( 'active_plugins', array() );

		$this->assertSame( array(), WC_Email_Customer_Abandoned_Cart_Recovery::get_active_recovery_handlers() );
	}

	/**
	 * @testdox get_active_recovery_handlers() returns the AutomateWoo entry when only AutomateWoo is active.
	 */
	public function test_active_recovery_handlers_detects_automatewoo(): void {
		update_option( 'active_plugins', array( 'automatewoo/automatewoo.php' ) );

		$active = WC_Email_Customer_Abandoned_Cart_Recovery::get_active_recovery_handlers();

		$this->assertArrayHasKey( 'automatewoo/automatewoo.php', $active );
		$this->assertSame( 'AutomateWoo', $active['automatewoo/automatewoo.php'] );
		$this->assertArrayNotHasKey( 'mailpoet/mailpoet.php', $active );
	}

	/**
	 * @testdox get_active_recovery_handlers() detects both AutomateWoo and MailPoet when both are active.
	 */
	public function test_active_recovery_handlers_detects_both(): void {
		update_option( 'active_plugins', array( 'automatewoo/automatewoo.php', 'mailpoet/mailpoet.php' ) );

		$active = WC_Email_Customer_Abandoned_Cart_Recovery::get_active_recovery_handlers();

		$this->assertCount( 2, $active );
		$this->assertArrayHasKey( 'automatewoo/automatewoo.php', $active );
		$this->assertArrayHasKey( 'mailpoet/mailpoet.php', $active );
	}

	/**
	 * @testdox Enabled field defaults to 'no' when a known recovery handler is active so the merchant is pre-protected from duplicate sends, and the description names the detected plugin.
	 */
	public function test_enabled_field_default_is_no_when_handler_active(): void {
		update_option( 'active_plugins', array( 'automatewoo/automatewoo.php' ) );

		$this->sut->init_form_fields();

		$this->assertSame( 'no', $this->sut->form_fields['enabled']['default'] );
		$this->assertStringContainsString( 'AutomateWoo', $this->sut->form_fields['enabled']['description'] );
	}

	/**
	 * @testdox Enabled field defaults to 'yes' when no known recovery handler is active so core's recovery email runs by default, and the description is empty.
	 */
	public function test_enabled_field_default_is_yes_when_no_handler_active(): void {
		update_option( 'active_plugins', array() );

		$this->sut->init_form_fields();

		$this->assertSame( 'yes', $this->sut->form_fields['enabled']['default'] );
		$this->assertSame( '', $this->sut->form_fields['enabled']['description'] );
	}

	/**
	 * @testdox register_order_action() adds the manual-send entry for a pending order older than the abandonment threshold when the current user has edit_shop_orders.
	 */
	public function test_register_order_action_adds_entry_for_pending_order(): void {
		$this->become_admin();

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$actions = $this->sut->register_order_action( array(), $order );

		$this->assertArrayHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
		$this->assertSame( 'Send abandoned cart recovery email', $actions[ WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION ] );
	}

	/**
	 * @testdox register_order_action() also surfaces the action for checkout-draft orders past the abandonment threshold (Blocks Store API parks abandoned-mid-flow orders there).
	 */
	public function test_register_order_action_adds_entry_for_checkout_draft_order(): void {
		$this->become_admin();

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();
		$order = $this->age_order_past_threshold( $order );

		$actions = $this->sut->register_order_action( array(), $order );

		$this->assertArrayHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
	}

	/**
	 * @testdox register_order_action() hides the entry for an otherwise-eligible order that was created less than the abandonment threshold ago, so we don't nudge customers still on the page.
	 */
	public function test_register_order_action_skips_recent_orders(): void {
		$this->become_admin();

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$actions = $this->sut->register_order_action( array(), $order );

		$this->assertArrayNotHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
	}

	/**
	 * @testdox register_order_action() leaves the dropdown alone once the order has moved past the abandoned-checkout statuses.
	 */
	public function test_register_order_action_skips_non_abandoned_orders(): void {
		$this->become_admin();

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$actions = $this->sut->register_order_action( array(), $order );

		$this->assertArrayNotHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
	}

	/**
	 * @testdox register_order_action() does not surface the action for users without edit_shop_orders, even on a pending order.
	 */
	public function test_register_order_action_requires_capability(): void {
		// Logged-out / no caps.
		wp_set_current_user( 0 );

		// Age the order past the threshold so the capability check — not the
		// recent-order gate — is what removes the action from the list.
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$actions = $this->sut->register_order_action( array(), $order );

		$this->assertArrayNotHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
	}

	/**
	 * @testdox register_order_action() returns the unchanged action list when called without an order (e.g. order-list bulk context).
	 */
	public function test_register_order_action_passthrough_without_order(): void {
		$this->become_admin();

		$existing = array( 'foo' => 'Foo' );
		$actions  = $this->sut->register_order_action( $existing, null );

		$this->assertSame( $existing, $actions );
	}

	/**
	 * @testdox handle_recovery_email_send() dispatches the email, persists the sent_at meta, and records an email-notification order note.
	 */
	public function test_handle_recovery_email_send_dispatches_and_records_note(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_recovery_email_send( $order );

		$this->assertSame( $before + 1, count( $mailer->mock_sent ), 'Manual send must dispatch one message.' );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty( $fresh->get_meta( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT ) );

		$notes        = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$note_strings = wp_list_pluck( $notes, 'content' );
		$this->assertNotEmpty(
			array_filter(
				$note_strings,
				static fn ( $note ) => false !== strpos( $note, 'sent from the order actions menu' )
			),
			'Manual send must record an order note announcing the email.'
		);
	}

	/**
	 * @testdox handle_recovery_email_send() also dispatches for checkout-draft orders that have a billing email and are past the abandonment threshold, mirroring the dropdown gating.
	 */
	public function test_handle_recovery_email_send_dispatches_on_checkout_draft(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();
		$order = $this->age_order_past_threshold( $order );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_recovery_email_send( $order );

		$this->assertSame( $before + 1, count( $mailer->mock_sent ), 'Checkout-draft order with a billing email must dispatch.' );
	}

	/**
	 * @testdox handle_recovery_email_send() is a no-op for orders that are still inside the abandonment window so a stale dropdown submission cannot fire prematurely.
	 */
	public function test_handle_recovery_email_send_bails_on_recent_order(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_recovery_email_send( $order );

		$this->assertSame( $before, count( $mailer->mock_sent ), 'Recent pending order must not dispatch.' );
		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT ) );
	}

	/**
	 * @testdox handle_recovery_email_send() is a no-op when the order has moved past the abandoned-checkout statuses so a stale dropdown submission cannot resend.
	 */
	public function test_handle_recovery_email_send_bails_on_non_abandoned_status(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_recovery_email_send( $order );

		$this->assertSame( $before, count( $mailer->mock_sent ), 'Non-abandoned order must not dispatch.' );
		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT ) );
	}

	/**
	 * @testdox handle_recovery_email_send() is a no-op for users without edit_shop_orders so an unauthorized hook caller cannot fire the email.
	 */
	public function test_handle_recovery_email_send_requires_capability(): void {
		wp_set_current_user( 0 );
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		// Age the order past the threshold so the capability check — not the
		// recent-order gate — is what blocks the dispatch.
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_recovery_email_send( $order );

		$this->assertSame( $before, count( $mailer->mock_sent ), 'Unauthorized user must not dispatch the email.' );
	}

	/**
	 * @testdox register_order_action() hides the action when the email is disabled, so the dropdown stays in sync with what trigger() would do.
	 */
	public function test_register_order_action_skips_when_email_disabled(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'no' );
		$this->sut->enabled = 'no';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$actions = $this->sut->register_order_action( array(), $order );

		$this->assertArrayNotHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
	}

	/**
	 * @testdox register_order_action() hides the action when the woocommerce_abandoned_cart_recovery_suppress filter returns true, so merchants don't click a no-op item.
	 */
	public function test_register_order_action_skips_when_suppressed(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		add_filter( 'woocommerce_abandoned_cart_recovery_suppress', '__return_true' );
		try {
			$actions = $this->sut->register_order_action( array(), $order );
		} finally {
			remove_filter( 'woocommerce_abandoned_cart_recovery_suppress', '__return_true' );
		}

		$this->assertArrayNotHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
	}

	/**
	 * @testdox register_order_action() hides the action once the order is marked as sent so merchants don't click a dropdown item that would silently no-op on the trigger-side dedup gate.
	 */
	public function test_register_order_action_skips_when_already_sent(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );
		$order->update_meta_data( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT, (string) time() );
		$order->save();

		$actions = $this->sut->register_order_action( array(), $order );

		$this->assertArrayNotHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
	}

	/**
	 * @testdox handle_recovery_email_send() does not record a "sent from the order actions menu" order note when trigger() bails on the dedup gate — keeps the audit trail honest about whether an email actually went out.
	 */
	public function test_handle_recovery_email_send_skips_note_when_already_sent(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );
		$order->update_meta_data( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT, (string) ( time() - HOUR_IN_SECONDS ) );
		$order->save();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_recovery_email_send( $order );

		$this->assertSame( $before, count( $mailer->mock_sent ), 'Already-sent order must not dispatch a second message from the manual handler.' );

		$notes        = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$note_strings = wp_list_pluck( $notes, 'content' );
		$this->assertEmpty(
			array_filter(
				$note_strings,
				static fn ( $note ) => false !== strpos( $note, 'sent from the order actions menu' )
			),
			'Dedup-gated trigger() must not leave a "sent from the order actions menu" order note behind.'
		);
	}

	/**
	 * @testdox handle_recovery_email_send() is a no-op when the email is disabled — avoids writing an order note for a send that never happened.
	 */
	public function test_handle_recovery_email_send_bails_when_email_disabled(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'no' );
		$this->sut->enabled = 'no';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_recovery_email_send( $order );

		$this->assertSame( $before, count( $mailer->mock_sent ), 'Disabled email must not dispatch from manual handler.' );

		$notes        = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$note_strings = wp_list_pluck( $notes, 'content' );
		$this->assertEmpty(
			array_filter(
				$note_strings,
				static fn ( $note ) => false !== strpos( $note, 'sent from the order actions menu' )
			),
			'Disabled email must not record a "sent from the order actions menu" order note.'
		);
	}

	/**
	 * Create an order without a billing email or associated customer.
	 *
	 * `OrderHelper::create_order()` always seeds a billing email, and
	 * `WC_Order::maybe_set_user_billing_email()` re-populates the field from
	 * the associated user on save. To exercise the no-recipient path we have
	 * to drop both the customer link and the address email together.
	 *
	 * @return WC_Order Reloaded order with empty billing email.
	 */
	private function create_order_without_recipient(): WC_Order {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_customer_id( 0 );
		$order->set_billing_email( '' );
		$order->save();

		return $this->age_order_past_threshold( $order );
	}

	/**
	 * @testdox register_order_action() hides the action when the order has no billing email — checkout-draft orders can land here mid-flow and a recipient-less send would silently no-op.
	 */
	public function test_register_order_action_skips_without_billing_email(): void {
		$this->become_admin();

		$order = $this->create_order_without_recipient();

		$actions = $this->sut->register_order_action( array(), $order );

		$this->assertArrayNotHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
	}

	/**
	 * @testdox handle_recovery_email_send() is a no-op when the order has no billing email so we don't record an order note for a send that never went out.
	 */
	public function test_handle_recovery_email_send_bails_without_billing_email(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = $this->create_order_without_recipient();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_recovery_email_send( $order );

		$this->assertSame( $before, count( $mailer->mock_sent ), 'Order without a recipient must not dispatch.' );

		$notes        = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$note_strings = wp_list_pluck( $notes, 'content' );
		$this->assertEmpty(
			array_filter(
				$note_strings,
				static fn ( $note ) => false !== strpos( $note, 'sent from the order actions menu' )
			),
			'Recipient-less order must not record a "sent from the order actions menu" order note.'
		);
	}

	/**
	 * @testdox handle_recovery_email_send() is a no-op when the woocommerce_abandoned_cart_recovery_suppress filter returns true — avoids writing a misleading order note when trigger() would also bail.
	 */
	public function test_handle_recovery_email_send_bails_when_suppressed(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		add_filter( 'woocommerce_abandoned_cart_recovery_suppress', '__return_true' );
		try {
			$this->sut->handle_recovery_email_send( $order );
		} finally {
			remove_filter( 'woocommerce_abandoned_cart_recovery_suppress', '__return_true' );
		}

		$this->assertSame( $before, count( $mailer->mock_sent ), 'Suppressed email must not dispatch from manual handler.' );

		$notes        = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$note_strings = wp_list_pluck( $notes, 'content' );
		$this->assertEmpty(
			array_filter(
				$note_strings,
				static fn ( $note ) => false !== strpos( $note, 'sent from the order actions menu' )
			),
			'Suppressed email must not record a "sent from the order actions menu" order note.'
		);
	}

	/**
	 * @testdox trigger() does not dispatch when the recipient has previously unsubscribed — customer preference wins over the merchant's enabled setting.
	 */
	public function test_trigger_bails_when_recipient_unsubscribed(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$repository = wc_get_container()->get( \Automattic\WooCommerce\Internal\Email\Unsubscribes\Storage::class );
		$repository->mark_unsubscribed( $order->get_billing_email(), 'customer_abandoned_cart_recovery' );

		try {
			$mailer = tests_retrieve_phpmailer_instance();
			$before = count( $mailer->mock_sent );
			$this->sut->trigger( $order->get_id() );
			$after = count( $mailer->mock_sent );

			$this->assertSame( $before, $after, 'Unsubscribed recipient must not receive a recovery email.' );
		} finally {
			// Cleanup so the row doesn't leak into later tests in this run.
			$repository->erase_for_email( $order->get_billing_email() );
		}
	}

	/**
	 * @testdox register_order_action() hides the entry when the recipient has unsubscribed, so the merchant can't accidentally override the customer's preference from the dropdown.
	 */
	public function test_register_order_action_skips_unsubscribed_recipient(): void {
		$this->become_admin();

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$repository = wc_get_container()->get( \Automattic\WooCommerce\Internal\Email\Unsubscribes\Storage::class );
		$repository->mark_unsubscribed( $order->get_billing_email(), 'customer_abandoned_cart_recovery' );

		try {
			$actions = $this->sut->register_order_action( array(), $order );

			$this->assertArrayNotHasKey( WC_Email_Customer_Abandoned_Cart_Recovery::MANUAL_RECOVERY_EMAIL_SEND_ACTION, $actions );
		} finally {
			$repository->erase_for_email( $order->get_billing_email() );
		}
	}

	/**
	 * @testdox handle_recovery_email_send() is a no-op when the recipient has unsubscribed — defense in depth in case the action hook is fired from outside the metabox.
	 */
	public function test_handle_recovery_email_send_bails_when_recipient_unsubscribed(): void {
		$this->become_admin();
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order = $this->age_order_past_threshold( $order );

		$repository = wc_get_container()->get( \Automattic\WooCommerce\Internal\Email\Unsubscribes\Storage::class );
		$repository->mark_unsubscribed( $order->get_billing_email(), 'customer_abandoned_cart_recovery' );

		try {
			$mailer = tests_retrieve_phpmailer_instance();
			$before = count( $mailer->mock_sent );
			$this->sut->handle_recovery_email_send( $order );

			$this->assertSame( $before, count( $mailer->mock_sent ), 'Unsubscribed recipient must not receive a manual send.' );

			$notes        = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
			$note_strings = wp_list_pluck( $notes, 'content' );
			$this->assertEmpty(
				array_filter(
					$note_strings,
					static fn ( $note ) => false !== strpos( $note, 'sent from the order actions menu' )
				),
				'Unsubscribed recipient must not have a "sent from the order actions menu" order note written.'
			);
		} finally {
			$repository->erase_for_email( $order->get_billing_email() );
		}
	}

	/**
	 * @testdox get_unsubscribe_url() returns a signed URL pointing at the public endpoint once a valid order is bound; empty when there's no order to derive it from.
	 */
	public function test_get_unsubscribe_url(): void {
		$this->assertSame( '', $this->sut->get_unsubscribe_url(), 'No order bound — no URL.' );

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$this->sut->trigger( $order->get_id() );

		$url = $this->sut->get_unsubscribe_url();

		$this->assertNotEmpty( $url );
		$this->assertStringContainsString( \Automattic\WooCommerce\Internal\Email\Unsubscribes\Endpoint::QUERY_VAR . '=' . $order->get_id(), $url );
		$this->assertStringContainsString( 'sig=', $url );
	}
}
