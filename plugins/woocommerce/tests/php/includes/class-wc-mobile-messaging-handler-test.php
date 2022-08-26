<?php

/**
 * Class WC_Mobile_Messaging_Handler_Test file.
 *
 * @package WooCommerce\Tests
 */
class WC_Mobile_Messaging_Handler_Test extends WC_Unit_Test_Case {

	const BLOG_ID  = 2;
	const ORDER_ID = 6;

	/**
	 * Tests if SUT is generates correct mobile messaging caption if Jetpack's
	 * blog id exists.
	 */
	public function test_preparing_order_details_link() {
		update_option(
			'woocommerce_mobile_app_usage',
			array(
				'android' => array(
					'last_used' => '2022-08-03T00:00:00+00:00',
				),
			)
		);

		$mobile_message = WC_Mobile_Messaging_Handler::prepare_mobile_message( self::ORDER_ID, self::BLOG_ID );

		$this->assertEquals(
			'<a href="https://woocommerce.com/mobile?blog_id=' . self::BLOG_ID . '&#038;order_id=' . self::ORDER_ID . '">Manage the order</a> in the mobile app.',
			$mobile_message
		);
	}

	/**
	 * Tests if SUT returns null when Jetpack's blog id is null.
	 */
	public function test_no_jetpack_id() {
		$mobile_message = WC_Mobile_Messaging_Handler::prepare_mobile_message( self::ORDER_ID, null );

		$this->assertNull( $mobile_message );
	}

}
