<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * WC_Email_Customer_Ready_For_Pickup_Order test.
 *
 * @covers WC_Email_Customer_Ready_For_Pickup_Order
 */
class WC_Email_Customer_Ready_For_Pickup_Order_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Email_Customer_Ready_For_Pickup_Order
	 */
	private $sut;

	/**
	 * Enable local pickup and load the email, which is only registered on stores that offer it.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_pickup_location_settings', array( 'enabled' => 'yes' ) );

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		$this->sut = require $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-ready-for-pickup-order.php';
	}

	/**
	 * @testdox Should email the customer the pickup location when the order is marked ready for pickup.
	 */
	public function test_status_change_emails_customer_the_pickup_location(): void {
		$order = $this->create_order_with_shipping_method(
			'pickup_location',
			'Pickup',
			array(
				'pickup_location' => 'Downtown store',
				'pickup_address'  => '123 Main Street, Austin, TX 78701',
				'pickup_details'  => 'Ask at the <strong>front counter</strong>.',
			)
		);
		$order->set_billing_email( 'buyer@example.com' );
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		// The mailer registers its own copy of the email, so leave only the one under test listening.
		WC()->mailer();
		remove_all_actions( 'woocommerce_order_status_ready-for-pickup_notification' );
		add_action( 'woocommerce_order_status_ready-for-pickup_notification', array( $this->sut, 'trigger' ), 10, 2 );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$order->update_status( OrderStatus::READY_FOR_PICKUP );
		$sent = $this->get_emails_sent_since( $before, 'ready for pickup' );

		$this->assertCount( 1, $sent, 'Exactly one ready for pickup email should be sent' );
		$this->assertSame( 'buyer@example.com', $sent[0]['to'][0][0] );
		$this->assertStringContainsString( 'Pickup location', $sent[0]['body'] );
		$this->assertStringContainsString( 'Downtown store', $sent[0]['body'] );
		$this->assertStringContainsString( '123 Main Street, Austin, TX 78701', $sent[0]['body'] );
		$this->assertStringContainsString( 'Ask at the <strong>front counter</strong>.', $sent[0]['body'] );
	}

	/**
	 * @testdox Should not send the email when it is disabled in the email settings.
	 */
	public function test_disabled_email_is_not_sent(): void {
		update_option( 'woocommerce_customer_ready_for_pickup_order_settings', array( 'enabled' => 'no' ) );
		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		$email     = require $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-ready-for-pickup-order.php';
		$order     = $this->create_order_with_shipping_method( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$email->trigger( $order->get_id(), $order );

		$this->assertCount( $before, $mailer->mock_sent, 'A disabled email should not be sent' );
	}

	/**
	 * @testdox Should show the pickup location in the plain text email, without HTML.
	 */
	public function test_plain_text_content_shows_pickup_location(): void {
		$order             = $this->create_order_with_shipping_method(
			'pickup_location',
			'Pickup',
			array(
				'pickup_location' => 'Downtown store',
				'pickup_address'  => '123 Main Street, Austin, TX 78701',
				'pickup_details'  => 'Ask at the <strong>front counter</strong>.',
			)
		);
		$this->sut->object = $order;

		$content = $this->sut->get_content_plain();

		$this->assertStringContainsString( "PICKUP LOCATION\n\nDowntown store\n123 Main Street, Austin, TX 78701\nAsk at the front counter.\n", $content );
	}

	/**
	 * @testdox Should show the method title for a zone Local pickup order, which stores no location.
	 */
	public function test_content_shows_method_title_for_zone_local_pickup(): void {
		$this->sut->object = $this->create_order_with_shipping_method( 'local_pickup', 'Collect in store' );

		$content = $this->sut->get_content_html();

		$this->assertStringContainsString( '<strong>Collect in store</strong>', $content );
	}

	/**
	 * @testdox Should list every pickup location when the order has more than one.
	 */
	public function test_content_lists_every_pickup_location(): void {
		$order = $this->create_order_with_shipping_method( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );
		$this->add_shipping_method( $order, 'pickup_location', 'Pickup', array( 'pickup_location' => 'Airport kiosk' ) );
		$order->save();
		$this->sut->object = $order;

		$content = $this->sut->get_content_html();

		$this->assertStringContainsString( 'Pickup locations', $content );
		$this->assertStringContainsString( 'Downtown store', $content );
		$this->assertStringContainsString( 'Airport kiosk', $content );
	}

	/**
	 * @testdox Should leave out the pickup location section for an order that is shipped.
	 */
	public function test_content_has_no_pickup_section_for_shipped_order(): void {
		$this->sut->object = $this->create_order_with_shipping_method( 'flat_rate', 'Flat rate' );

		$this->assertStringNotContainsString( 'email-pickup-locations', $this->sut->get_content_html() );
		$this->assertStringNotContainsString( 'PICKUP LOCATION', $this->sut->get_content_plain() );
	}

	/**
	 * @testdox Should escape the pickup location name and address.
	 */
	public function test_pickup_location_name_and_address_are_escaped(): void {
		$this->sut->object = $this->create_order_with_shipping_method(
			'pickup_location',
			'Pickup',
			array(
				'pickup_location' => 'Store <script>alert(1)</script>',
				'pickup_address'  => '1 <b>Main</b> Street',
			)
		);

		$content = $this->sut->get_content_html();

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $content );
		$this->assertStringContainsString( '1 &lt;b&gt;Main&lt;/b&gt; Street', $content );
	}

	/**
	 * @testdox Should add the pickup location to the block email content once, for the email being rendered.
	 */
	public function test_block_content_shows_pickup_location_once_for_the_rendered_email(): void {
		$this->sut->object = $this->create_order_with_shipping_method( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );

		$bootstrap     = \WC_Unit_Tests_Bootstrap::instance();
		$second_copy   = require $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-ready-for-pickup-order.php';
		$another_email = new WC_Email();

		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_email_general_block_content', false, false, $this->sut );
		$rendered_email = ob_get_clean();

		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_email_general_block_content', false, false, $another_email );
		$other_email = ob_get_clean();

		$this->assertInstanceOf( WC_Email_Customer_Ready_For_Pickup_Order::class, $second_copy );
		$this->assertSame( 1, substr_count( $rendered_email, 'Downtown store' ), 'A second copy of the email class should not print the location again' );
		$this->assertSame( '', $other_email, 'Other emails should not get the pickup location' );
	}

	/**
	 * @testdox Should not reuse the previous recipient when triggered with an invalid order.
	 */
	public function test_trigger_clears_state_on_invalid_order(): void {
		$order = $this->create_order_with_shipping_method( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );
		$this->sut->trigger( $order->get_id(), $order );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( 0 );

		$this->assertCount( $before, $mailer->mock_sent, 'No email should be sent for an invalid order' );
		$this->assertSame( '', $this->sut->recipient );
		$this->assertFalse( $this->sut->object );
	}

	/**
	 * @testdox Should register the email only on stores where the status is available.
	 */
	public function test_email_is_registered_only_when_status_is_available(): void {
		$with_local_pickup = new WC_Emails();
		$this->assertArrayHasKey( 'WC_Email_Customer_Ready_For_Pickup_Order', $with_local_pickup->get_emails(), 'The email should be registered while local pickup is offered' );

		update_option( 'woocommerce_pickup_location_settings', array( 'enabled' => 'no' ) );

		$without_local_pickup = new WC_Emails();
		$this->assertArrayNotHasKey( 'WC_Email_Customer_Ready_For_Pickup_Order', $without_local_pickup->get_emails(), 'The email should not be registered without local pickup' );
	}

	/**
	 * Get the emails sent since a point in the mock mailer's log whose subject contains the given text.
	 *
	 * @param int    $offset  Number of emails already sent before the action under test.
	 * @param string $subject Text the subject must contain.
	 * @return array[]
	 */
	private function get_emails_sent_since( int $offset, string $subject ): array {
		$sent = array_slice( tests_retrieve_phpmailer_instance()->mock_sent, $offset );

		return array_values(
			array_filter(
				$sent,
				function ( $email ) use ( $subject ) {
					return false !== strpos( $email['subject'], $subject );
				}
			)
		);
	}

	/**
	 * Create an order with a single shipping method.
	 *
	 * @param string $method_id The shipping method ID.
	 * @param string $title     The shipping method title.
	 * @param array  $meta      Meta to store on the shipping line.
	 * @return WC_Order
	 */
	private function create_order_with_shipping_method( string $method_id, string $title, array $meta = array() ): WC_Order {
		$order = WC_Helper_Order::create_order();

		foreach ( array_keys( $order->get_items( 'shipping' ) ) as $item_id ) {
			$order->remove_item( $item_id );
		}

		$this->add_shipping_method( $order, $method_id, $title, $meta );
		$order->save();

		return $order;
	}

	/**
	 * Add a shipping method to an order.
	 *
	 * @param WC_Order $order     The order.
	 * @param string   $method_id The shipping method ID.
	 * @param string   $title     The shipping method title.
	 * @param array    $meta      Meta to store on the shipping line.
	 */
	private function add_shipping_method( WC_Order $order, string $method_id, string $title, array $meta = array() ): void {
		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_props(
			array(
				'method_title' => $title,
				'method_id'    => $method_id,
				'total'        => 0,
			)
		);
		foreach ( $meta as $key => $value ) {
			$shipping_item->add_meta_data( $key, $value, true );
		}

		$order->add_item( $shipping_item );
	}
}
