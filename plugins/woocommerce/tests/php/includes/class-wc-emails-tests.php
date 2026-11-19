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
	 * @testdox Plain fulfillment details list the fulfilled items when the fulfillment has no tracking information.
	 */
	public function test_plain_fulfillment_details_list_items_without_tracking_information(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );

		$content = $this->render_fulfillment_details( $order, $fulfillment, true );

		$this->assertStringContainsString( 'No tracking information available', $content, 'The missing tracking notice should still show' );
		$this->assertStringContainsString( 'Fulfillment summary', $content, 'The summary should render without tracking information' );
		foreach ( $order->get_items() as $item ) {
			$this->assertStringContainsString( $item->get_name(), $content, 'Every fulfilled item should be listed' );
		}
	}

	/**
	 * @testdox Plain fulfillment details list the fulfilled items when the fulfillment has tracking information.
	 */
	public function test_plain_fulfillment_details_list_items_with_tracking_information(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_number', '1Z999AA10123456784', true );

		$content = $this->render_fulfillment_details( $order, $fulfillment, true );

		$this->assertStringContainsString( '1Z999AA10123456784', $content, 'The tracking number should show' );
		$this->assertStringContainsString( 'Fulfillment summary', $content, 'The summary should render with tracking information' );
		foreach ( $order->get_items() as $item ) {
			$this->assertStringContainsString( $item->get_name(), $content, 'Every fulfilled item should be listed' );
		}
	}

	/**
	 * @testdox Fulfillment details leave the tracking link out when the fulfillment has no tracking URL.
	 */
	public function test_fulfillment_details_leave_out_the_tracking_link_without_a_url(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_number', '1Z999AA10123456784', true );
		$fulfillment->add_meta_data( '_shipment_provider', 'other', true );
		$fulfillment->add_meta_data( '_provider_name', 'Acme Freight', true );

		$content = $this->render_fulfillment_details( $order, $fulfillment, false );

		$this->assertStringNotContainsString( 'Track your shipment', $content, 'A link with no destination should not be offered' );
		$this->assertStringNotContainsString( 'href=""', $content, 'No link should be rendered with an empty destination' );
		$this->assertStringContainsString( '1Z999AA10123456784', $content, 'The tracking number should still show' );
		$this->assertStringContainsString( 'Acme Freight', $content, 'The shipment provider should still show' );
	}

	/**
	 * @testdox Plain fulfillment details leave the Tracking URL line out when the fulfillment has no tracking URL.
	 */
	public function test_plain_fulfillment_details_leave_out_the_tracking_url_line_without_a_url(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_number', '1Z999AA10123456784', true );
		$fulfillment->add_meta_data( '_shipment_provider', 'other', true );
		$fulfillment->add_meta_data( '_provider_name', 'Acme Freight', true );

		$content = $this->render_fulfillment_details( $order, $fulfillment, true );

		$this->assertStringNotContainsString( 'Tracking URL', $content, 'A label with nothing after it should not be printed' );
		$this->assertStringContainsString( "Acme Freight\n\nYou can access", $content, 'The tracking block should still end with a blank line' );
		$this->assertStringContainsString( '1Z999AA10123456784', $content, 'The tracking number should still show' );
	}

	/**
	 * @testdox Fulfillment details render every tracking line the fulfillment has, in order, followed by a blank line.
	 */
	public function test_fulfillment_details_render_every_tracking_line_in_order(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_number', '1Z999AA10123456784', true );
		$fulfillment->add_meta_data( '_shipment_provider', 'other', true );
		$fulfillment->add_meta_data( '_provider_name', 'Acme Freight', true );
		$fulfillment->add_meta_data( '_tracking_url', 'https://example.com/track/1Z999AA10123456784', true );

		$html  = $this->render_fulfillment_details( $order, $fulfillment, false );
		$plain = $this->render_fulfillment_details( $order, $fulfillment, true );

		$this->assertStringContainsString( 'Track your shipment', $html, 'The link should be offered' );
		$this->assertStringContainsString( 'href="https://example.com/track/1Z999AA10123456784"', $html, 'With the stored URL as its destination' );
		$this->assertStringContainsString(
			"Tracking Number: 1Z999AA10123456784\nShipment Provider: Acme Freight\nTracking URL: https://example.com/track/1Z999AA10123456784\n\nYou can access",
			$plain,
			'A fulfillment with all three values should read exactly as it did before the lines were guarded'
		);
	}

	/**
	 * @testdox Fulfillment details leave the tracking link out when the stored URL cannot be linked.
	 * @testWith ["   "]
	 *           ["javascript:alert(1)"]
	 *
	 * @param string $tracking_url The stored tracking URL.
	 */
	public function test_fulfillment_details_leave_out_a_tracking_link_that_cannot_be_rendered( string $tracking_url ): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_number', '1Z999AA10123456784', true );
		$fulfillment->add_meta_data( '_tracking_url', $tracking_url, true );

		$html  = $this->render_fulfillment_details( $order, $fulfillment, false );
		$plain = $this->render_fulfillment_details( $order, $fulfillment, true );

		$this->assertStringNotContainsString( 'href=""', $html, 'A URL that esc_url() empties should not become a link' );
		$this->assertStringNotContainsString( 'Track your shipment', $html, 'So no link should be offered at all' );
		$this->assertStringNotContainsString( 'Tracking URL', $plain, 'The plain email should not print a label for it either' );
		$this->assertStringContainsString( '1Z999AA10123456784', $html, 'The tracking number should still show' );
		$this->assertStringContainsString( "1Z999AA10123456784\n\nYou can access", $plain, 'The plain email should still render the rest of the block' );
	}

	/**
	 * @testdox Fulfillment details encode a tracking URL for the format each email is in.
	 */
	public function test_fulfillment_details_encode_the_tracking_url_for_each_format(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_url', 'https://example.com/t?id=1&carrier=ups', true );

		$html  = $this->render_fulfillment_details( $order, $fulfillment, false );
		$plain = $this->render_fulfillment_details( $order, $fulfillment, true );

		$this->assertStringContainsString( 'href="https://example.com/t?id=1&#038;carrier=ups"', $html, 'An href encodes the ampersand as HTML requires' );
		$this->assertStringContainsString( "Tracking URL: https://example.com/t?id=1&carrier=ups\n", $plain, 'A plain email needs no entity for an ampersand' );
	}

	/**
	 * @testdox Plain fulfillment details keep a character of the tracking URL that an entity would lose.
	 */
	public function test_plain_fulfillment_details_keep_a_url_character_an_entity_would_lose(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_url', "https://example.com/t?a='b", true );

		$content = $this->render_fulfillment_details( $order, $fulfillment, true );

		// WC_Email strips entities it does not recognize from a plain email, and "&#039;" is one
		// of them, so an escaper that produced it would drop the character from the URL entirely.
		$this->assertStringNotContainsString( '&#039;', $content, 'The apostrophe must not be left as an entity the plain email would delete' );
		$this->assertStringContainsString( "Tracking URL: https://example.com/t?a='b", $content, 'It should be printed as itself' );
	}

	/**
	 * @testdox Fulfillment details show the missing tracking notice when a tracking value is not a string.
	 */
	public function test_fulfillment_details_show_the_notice_for_a_tracking_value_that_is_not_a_string(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_number', array( 'not a tracking number' ), true );

		$html  = $this->render_fulfillment_details( $order, $fulfillment, false );
		$plain = $this->render_fulfillment_details( $order, $fulfillment, true );

		$this->assertStringNotContainsString( 'Array', $html, 'A value that is not a string should not be printed' );
		$this->assertStringContainsString( 'No tracking information available', $html, 'The notice should take over' );
		$this->assertStringContainsString( 'No tracking information available', $plain, 'In both formats' );
	}

	/**
	 * @testdox Fulfillment details leave the empty labels out when the fulfillment has only a tracking URL.
	 */
	public function test_fulfillment_details_leave_out_empty_labels_with_only_a_tracking_url(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_url', 'https://example.com/track/1Z999AA10123456784', true );

		$html  = $this->render_fulfillment_details( $order, $fulfillment, false );
		$plain = $this->render_fulfillment_details( $order, $fulfillment, true );

		$this->assertStringNotContainsString( 'Tracking Number', $html, 'A tracking number label with nothing after it should not be printed' );
		$this->assertStringNotContainsString( 'Shipment Provider', $html, 'A provider label with nothing after it should not be printed' );
		$this->assertStringContainsString( 'Track your shipment', $html, 'The link it does have should still show' );
		$this->assertStringNotContainsString( 'Tracking Number', $plain, 'The plain email should leave the same labels out' );
		$this->assertStringContainsString( "Tracking URL: https://example.com/track/1Z999AA10123456784\n\nYou can access", $plain, 'A single line still ends with the blank line that closes the block' );
	}

	/**
	 * @testdox Fulfillment details show the missing tracking notice when none of the three lines can be rendered.
	 */
	public function test_fulfillment_details_show_the_notice_when_no_line_can_be_rendered(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_shipment_provider', 'ups', true );

		// A provider that resolves to nothing, as a callback returning an empty label leaves it.
		$empty_label = function () {
			return '';
		};
		add_filter( 'woocommerce_fulfillment_shipment_provider_label', $empty_label );

		$html  = $this->render_fulfillment_details( $order, $fulfillment, false );
		$plain = $this->render_fulfillment_details( $order, $fulfillment, true );

		remove_filter( 'woocommerce_fulfillment_shipment_provider_label', $empty_label );

		$this->assertStringContainsString( 'No tracking information available', $html, 'The notice should take over when every line would be empty' );
		$this->assertStringContainsString( 'No tracking information available', $plain, 'Both templates carry their own copy of that decision' );
	}

	/**
	 * @testdox Fulfillment details leave the shipment provider out when the fulfillment has none.
	 */
	public function test_fulfillment_details_leave_out_the_provider_when_there_is_none(): void {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = $this->create_fulfillment_covering_order( $order );
		$fulfillment->add_meta_data( '_tracking_number', '1Z999AA10123456784', true );

		$html  = $this->render_fulfillment_details( $order, $fulfillment, false );
		$plain = $this->render_fulfillment_details( $order, $fulfillment, true );

		$this->assertStringContainsString( '1Z999AA10123456784', $html, 'The tracking number should still show' );
		$this->assertStringNotContainsString( 'Shipment Provider', $html, 'A provider label with nothing after it should not be printed' );
		$this->assertStringNotContainsString( 'Shipment Provider', $plain, 'In both formats' );
	}

	/**
	 * Build an unsaved fulfillment covering every item of the order.
	 *
	 * @param WC_Order $order The order to fulfill.
	 * @return Fulfillment
	 */
	private function create_fulfillment_covering_order( WC_Order $order ): Fulfillment {
		$items = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$items[] = array(
				'item_id' => $item_id,
				'qty'     => $item->get_quantity(),
			);
		}

		$fulfillment = new Fulfillment();
		$fulfillment->set_items( $items );

		return $fulfillment;
	}

	/**
	 * Render the fulfillment details for an order in one of the two email formats.
	 *
	 * @param WC_Order    $order The order the details are about.
	 * @param Fulfillment $fulfillment The fulfillment the details are about.
	 * @param bool        $plain_text Whether to render the plain text format instead of the HTML one.
	 * @return string
	 */
	private function render_fulfillment_details( WC_Order $order, Fulfillment $fulfillment, bool $plain_text ): string {
		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-fulfillment-created.php';

		// Called directly rather than through its action: both that hook and the email class are
		// only loaded when the fulfillments feature is on, and WC_Emails freezes its hooks when it
		// is first constructed. The email is still passed, because the template hands it to its hooks.
		$email_object = new WC_Emails();
		ob_start();
		$email_object->fulfillment_details( $order, $fulfillment, false, $plain_text, new WC_Email_Customer_Fulfillment_Created() );

		return (string) ob_get_clean();
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
	 * @testdox get_store_address() shows the store's country, and leaves later addresses in that country without it.
	 */
	public function test_get_store_address_forces_the_country_only_for_the_store_address(): void {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_store_address', '123 Main Street' );
		update_option( 'woocommerce_store_city', 'San Francisco' );
		update_option( 'woocommerce_store_postcode', '94110' );

		$store_address = WC()->mailer()->get_store_address();

		$this->assertStringContainsString( 'United States (US)', $store_address, 'The store address should show the country.' );
		$this->assertStringNotContainsString(
			'United States (US)',
			WC()->countries->get_formatted_address(
				array(
					'address_1' => '1 Other Street',
					'city'      => 'Los Angeles',
					'state'     => 'CA',
					'postcode'  => '90001',
					'country'   => 'US',
				)
			),
			'Addresses formatted after the store address should leave out the store\'s country again.'
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
