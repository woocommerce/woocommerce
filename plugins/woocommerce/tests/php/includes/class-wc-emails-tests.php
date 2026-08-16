<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;

/**
 * Class WC_Emails_Tests.
 */
class WC_Emails_Tests extends \WC_Unit_Test_Case {

	/**
	 * Test that email_header hooks are compatible with do_action calls with only param.
	 * This test should be dropped after all extensions are using compatible do_action calls.
	 */
	public function test_email_header_is_compatible_with_legacy_do_action() {
		$email_object = new WC_Emails();
		// 10 is expected priority of the hook.
		$this->assertEquals( 10, has_action( 'woocommerce_email_header', array( $email_object, 'email_header' ) ) );
		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_email_header', 'header' );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertFalse( empty( $content ) );
	}

	/**
	 * Test that email_footer hooks are compatible with do_action calls with only param.
	 * This test should be dropped after all extensions are using compatible do_action calls.
	 */
	public function test_email_footer_is_compatible_with_legacy_do_action() {
		$email_object = new WC_Emails();
		// 10 is expected priority of the hook.
		$this->assertEquals( 10, has_action( 'woocommerce_email_footer', array( $email_object, 'email_footer' ) ) );
		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_email_footer' );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertFalse( empty( $content ) );
	}

	/**
	 * Test that replace_placeholders safely handles null values.
	 */
	public function test_replace_placeholders_handles_null_value() {
		$email_object = new WC_Emails();
		$this->assertSame( '', $email_object->replace_placeholders( null ) );
	}

	/**
	 * Test that replace_placeholders replaces known placeholders.
	 */
	public function test_replace_placeholders_replaces_site_title() {
		$email_object = new WC_Emails();
		$placeholder  = '{site_title}';
		$actual       = $email_object->replace_placeholders( $placeholder );
		$expected     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @testdox Pending to cancelled order status changes trigger transactional email notifications.
	 */
	public function test_pending_to_cancelled_status_change_triggers_transactional_email_notifications(): void {
		$hook             = 'woocommerce_order_status_pending_to_cancelled';
		$captured_actions = array();
		$email_actions    = function ( $actions ) use ( &$captured_actions, $hook ) {
			$captured_actions = $actions;
			return in_array( $hook, $actions, true ) ? array( $hook ) : array();
		};

		remove_action( $hook, array( 'WC_Emails', 'send_transactional_email' ), 10 );
		remove_action( $hook, array( 'WC_Emails', 'queue_transactional_email' ), 10 );
		add_filter( 'woocommerce_email_actions', $email_actions, 999 );
		add_filter( 'woocommerce_defer_transactional_emails', '__return_false', 999 );

		WC_Emails::init_transactional_emails();

		remove_filter( 'woocommerce_email_actions', $email_actions, 999 );
		remove_filter( 'woocommerce_defer_transactional_emails', '__return_false', 999 );

		$this->assertContains( $hook, $captured_actions, 'Pending to cancelled status changes should be part of the default transactional email action list.' );
		$this->assertSame( 10, has_action( $hook, array( 'WC_Emails', 'send_transactional_email' ) ), 'Pending to cancelled status changes should dispatch transactional emails.' );

		remove_action( $hook, array( 'WC_Emails', 'send_transactional_email' ), 10 );
	}

	/**
	 * @testdox Admin cancelled order email listens for pending to cancelled notifications.
	 */
	public function test_cancelled_order_email_listens_for_pending_to_cancelled_notifications(): void {
		$email_object    = new WC_Emails();
		$emails          = $email_object->get_emails();
		$cancelled_email = $emails['WC_Email_Cancelled_Order'];

		$this->assertSame(
			10,
			has_action( 'woocommerce_order_status_pending_to_cancelled_notification', array( $cancelled_email, 'trigger' ) ),
			'Cancelled order emails should notify admins when a pending order is cancelled.'
		);
	}

	/**
	 * Test that order meta function outputs linked meta.
	 */
	public function test_order_meta() {
		add_filter(
			'woocommerce_email_order_meta_keys',
			function () {
				return array( 'dummy_key' );
			}
		);
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->add_meta_data( 'dummy_key', 'dummy_meta_value' );
		$order->save();

		$email_object = new WC_Emails();
		ob_start();
		$email_object->order_meta( $order, true, true );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertStringContainsString( 'dummy_key', $content );
		$this->assertStringContainsString( 'dummy_meta_value', $content );
	}

	/**
	 * Test that fulfillment meta function outputs linked meta.
	 */
	public function test_fulfillment_meta() {
		// Ensure the FulfillmentsController is registered, which is necessary for the translation of meta keys.
		// Delete the DB tables flag to force recreation in case another test class left stale state.
		delete_option( 'woocommerce_fulfillments_db_tables_created' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$container  = wc_get_container();
		$controller = $container->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();

		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id'   => $order->get_id(),
				'entity_type' => 'WC_Order',
			)
		);

		add_filter(
			'woocommerce_fulfillment_meta_key_translations',
			function ( $translations ) {
				$translations['test_meta_key'] = __( 'Test meta key', 'woocommerce' );
				return $translations;
			}
		);

		$email_object = new WC_Emails();
		ob_start();
		$email_object->fulfillment_meta( $order, $fulfillment, true, true );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertStringContainsString( 'Test meta key', $content );
		$this->assertStringContainsString( 'test_meta_value', $content );
	}

	/**
	 * Build a valid set of arguments for WC_Emails::backorder().
	 *
	 * @return array
	 */
	private function get_backorder_args() {
		$product = WC_Helper_Product::create_simple_product();
		$order   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		return array(
			'product'  => $product,
			'quantity' => 2,
			'order_id' => $order->get_id(),
		);
	}

	/**
	 * @testdox backorder() sends a notification when the setting is enabled (default).
	 */
	public function test_backorder_sends_when_setting_enabled() {
		update_option( 'woocommerce_notify_backorder', 'yes' );
		$args = $this->get_backorder_args();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		( new WC_Emails() )->backorder( $args );
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before + 1, $after, 'Backorder notification should be sent when the setting is enabled.' );
	}

	/**
	 * @testdox backorder() does not send a notification when the setting is disabled.
	 */
	public function test_backorder_does_not_send_when_setting_disabled() {
		update_option( 'woocommerce_notify_backorder', 'no' );
		$args = $this->get_backorder_args();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		( new WC_Emails() )->backorder( $args );
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'Backorder notification must not be sent when the setting is disabled.' );

		update_option( 'woocommerce_notify_backorder', 'yes' );
	}

	/**
	 * @testdox backorder() does not send a notification when the woocommerce_should_send_backorder_notification filter returns false.
	 */
	public function test_backorder_does_not_send_when_filter_returns_false() {
		update_option( 'woocommerce_notify_backorder', 'yes' );
		$args = $this->get_backorder_args();

		add_filter( 'woocommerce_should_send_backorder_notification', '__return_false' );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		( new WC_Emails() )->backorder( $args );
		$after = count( $mailer->mock_sent );

		remove_filter( 'woocommerce_should_send_backorder_notification', '__return_false' );

		$this->assertSame( $before, $after, 'Backorder notification must not be sent when the filter returns false.' );
	}
}
