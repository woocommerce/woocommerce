<?php

/**
 * Class Functions.
 * @package WooCommerce\Tests\Webhook
 * @since 2.3
 */
class WC_Tests_Webhook_Functions extends WC_Unit_Test_Case {

	/**
	 * Data provider for test_wc_is_webhook_valid_topic.
	 *
	 * @since 3.2.0
	 */
	public function data_provider_test_wc_is_webhook_valid_topic() {
		return array(
			array( true, wc_is_webhook_valid_topic( 'action.woocommerce_add_to_cart' ) ),
			array( true, wc_is_webhook_valid_topic( 'action.wc_add_to_cart' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.restored' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.restored' ) ),
			array( true, wc_is_webhook_valid_topic( 'customer.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'customer.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'customer.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.restored' ) ),
			array( false, wc_is_webhook_valid_topic( 'coupon.upgraded' ) ),
			array( false, wc_is_webhook_valid_topic( 'wc.product.updated' ) ),
			array( false, wc_is_webhook_valid_topic( 'missingdot' ) ),
			array( false, wc_is_webhook_valid_topic( 'with space' ) ),
		);
	}

	/**
	 * Test wc_is_webhook_valid_topic().
	 *
	 * @dataProvider data_provider_test_wc_is_webhook_valid_topic
	 * @since 3.2.0
	 */
	public function test_wc_is_webhook_valid_topic( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Test wc_get_webhook_statuses().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_webhook_statuses() {
		$expected = array(
			'active'   => 'Active',
			'paused'   => 'Paused',
			'disabled' => 'Disabled',
		);

		$this->assertEquals( $expected, wc_get_webhook_statuses() );
	}

	/**
	 * Test wc_load_webhooks().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_load_webhooks() {
		$webhook = new WC_Webhook;
		$webhook->set_props( array(
			'status'           => 'active',
			'name'             => 'Testing webhook',
			'user_id'          => 0,
			'delivery_url'     => 'https://requestb.in/17jajv31',
			'secret'           => 'secret',
			'topic'            => 'action.woocommerce_some_action',
			'api_version'      => 2,
		) );
		$webhook->save();

		$this->assertTrue( wc_load_webhooks() );

		$webhook->delete( true );
		$this->assertFalse( wc_load_webhooks() );
	}
}
