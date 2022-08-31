<?php

/**
 * Class WC_Mobile_Messaging_Handler_Test file.
 *
 * @package WooCommerce\Tests
 */
class WC_Mobile_Messaging_Handler_Test extends WC_Unit_Test_Case {

	const BLOG_ID = 2;

	/**
	 * Tests if SUT is not throwing an exception in scenario, when user has only
	 * one mobile platform usage recorded.
	 */
	public function test_tracker_reports_only_android_usage() {
		$now = $this->prepare_timeline_with_valid_last_mobile_app_usage();

		$mobile_message = WC_Mobile_Messaging_Handler::prepare_mobile_message( new WC_Order(), self::BLOG_ID, $now );

		$this->assertEquals(
			'<a href="https://woocommerce.com/mobile/order?blog_id=' . self::BLOG_ID . '&#038;order_id=' . 0 . '">Manage the order</a> with the app.',
			$mobile_message
		);
	}

	/**
	 * Tests if SUT returns correct message when there are no mobile app usages reported
	 */
	public function test_show_get_app_message_when_no_mobile_reports_at_all() {
		$now = new DateTime( '2022-08-05T00:00:00+00:00' );
		update_option(
			'woocommerce_mobile_app_usage',
			array()
		);

		$mobile_message = WC_Mobile_Messaging_Handler::prepare_mobile_message( new WC_Order(), self::BLOG_ID, $now );

		$this->assertEquals(
			'Process your orders on the go. <a href="https://woocommerce.com/mobile/">Get the app</a>.',
			$mobile_message
		);
	}

	/**
	 * Tests if SUT returns correct message when the user uses mobile app but store is not eligible for IPP
	 */
	public function test_show_manage_order_message_when_store_is_NOT_ipp_eligible() {
		$now = $this->prepare_timeline_with_valid_last_mobile_app_usage();

		$mobile_message = WC_Mobile_Messaging_Handler::prepare_mobile_message( new WC_Order(), self::BLOG_ID, $now );

		$this->assertEquals(
			'<a href="https://woocommerce.com/mobile/order?blog_id=' . self::BLOG_ID . '&#038;order_id=' . 0 . '">Manage the order</a> with the app.',
			$mobile_message
		);
	}

	/**
	 * Tests if SUT returns correct message when the user uses mobile app but store is not eligible for IPP
	 */
	public function test_show_accept_payment_message_when_store_is_but_order_is_NOT_ipp_eligible() {
		$now = $this->prepare_timeline_with_valid_last_mobile_app_usage();
		$this->make_store_ipp_eligible();

		$not_ipp_eligible_order = new WC_Order();

		$mobile_message = WC_Mobile_Messaging_Handler::prepare_mobile_message( $not_ipp_eligible_order, self::BLOG_ID, $now );

		$this->assertEquals(
			'<a href="https://woocommerce.com/mobile/order?blog_id=2&#038;order_id=0">Manage the order</a> with the app.',
			$mobile_message
		);
	}

	/**
	 * Tests if SUT returns correct message when the user uses mobile app and store and order are eligible for IPP
	 */
	public function test_show_accept_payment_message_when_store_and_order_are_ipp_eligible() {
		$now = $this->prepare_timeline_with_valid_last_mobile_app_usage();
		$this->make_store_ipp_eligible();

		$ipp_eligible_order = $this->generate_ipp_eligible_order();

		$mobile_message = WC_Mobile_Messaging_Handler::prepare_mobile_message( $ipp_eligible_order, self::BLOG_ID, $now );

		$this->assertEquals(
			'<a href="https://woocommerce.com/mobile/payments?blog_id=2&#038;order_id=0">Accept payments</a> with a card reader in our mobile app.<a href="https://woocommerce.com/in-person-payments/">Learn more about In-Person Payments.</a>',
			$mobile_message
		);
	}

	/**
	 * Sets store to location USA and currency USD making it In-Person Payments eligible
	 */
	private function make_store_ipp_eligible() {
		update_option(
			'woocommerce_get_base_location',
			'US:CA'
		);
		update_option(
			'woocommerce_currency',
			'USD'
		);
	}

	/**
	 * Sets time of last mobile app usage within specified by @const WC_Mobile_Messaging_Handler#OPEN_ORDER_INTERVAL_DAYS interval
	 *
	 * @return DateTime date of execution within interval with mobile app usage
	 */
	private function prepare_timeline_with_valid_last_mobile_app_usage(): DateTime {
		$now = new DateTime( '2022-08-05T00:00:00+00:00' );
		update_option(
			'woocommerce_mobile_app_usage',
			array(
				'android' => array(
					'last_used' => '2022-08-03T00:00:00+00:00',
				),
			)
		);
		return $now;
	}

	/**
	 * @return WC_Order returns an empty Order, eligible for In-Person Payment
	 */
	private function generate_ipp_eligible_order(): WC_Order {
		$ipp_eligible_order = new WC_Order();
		$ipp_eligible_order->set_status( 'pending' );
		try {
			$ipp_eligible_order->set_payment_method( 'cod' );
		} catch ( WC_Data_Exception $e ) {
			exit();
		}

		return $ipp_eligible_order;
	}
}
