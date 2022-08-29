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
		$now = new DateTime( '2022-08-05T00:00:00+00:00' );

		update_option(
			'woocommerce_mobile_app_usage',
			array(
				'android' => array(
					'last_used' => '2022-08-03T00:00:00+00:00',
				),
			)
		);

		$mobile_message = WC_Mobile_Messaging_Handler::prepare_mobile_message( new WC_Order(), self::BLOG_ID, $now );

		$this->assertEquals(
			'<a href="https://woocommerce.com/mobile/order?blog_id=' . self::BLOG_ID . '&#038;order_id=' . 0 . '">Manage the order</a> with the app.',
			$mobile_message
		);
	}
}
