<?php

/**
 * Webhook CRUD
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_CRUD_Webhooks extends WC_Unit_Test_Case {

	/**
	 * Test: get_id
	 */
	function test_get_id() {
		$object = new WC_Webhook();
		$id     = $object->save();
		$this->assertEquals( $id, $object->get_id() );
		$object->delete();
	}

	/**
	 * Test: get_data
	 */
	function test_get_data() {
		$object = new WC_Webhook();
		$this->assertInternalType( 'array', $object->get_data() );
	}

	/**
	 * Test: get_name
	 */
	function test_get_name() {
		$object   = new WC_Webhook();
		$expected = 'test';
		$object->set_name( $expected );
		$this->assertEquals( $expected, $object->get_name() );
	}

	/**
	 * Test: get_date_created
	 */
	function test_get_date_created() {
		$object = new WC_Webhook();
		$object->set_date_created( '2016-12-12' );
		$this->assertEquals( '1481500800', $object->get_date_created()->getOffsetTimestamp() );

		$object->set_date_created( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_created()->getTimestamp() );
	}

	/**
	 * Test: get_date_modified
	 */
	function test_get_date_modified() {
		$object = new WC_Webhook();
		$object->set_date_modified( '2016-12-12' );
		$this->assertEquals( '1481500800', $object->get_date_modified()->getOffsetTimestamp() );

		$object->set_date_modified( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_modified()->getTimestamp() );
	}

	/**
	 * Test: get_status
	 */
	function test_get_status() {
		$object   = new WC_Webhook();
		$this->assertEquals( 'disabled', $object->get_status() );

		$expected = 'active';
		$object->set_status( $expected );
		$this->assertEquals( $expected, $object->get_status() );
	}

	/**
	 * Test: get_secret
	 */
	function test_get_secret() {
		$object   = new WC_Webhook();
		$expected = 'secret';
		$object->set_secret( $expected );
		$this->assertEquals( $expected, $object->get_secret() );
	}

	/**
	 * Test: get_topic
	 */
	function test_get_topic() {
		$object   = new WC_Webhook();
		$expected = 'order.created';
		$object->set_topic( $expected );
		$this->assertEquals( $expected, $object->get_topic() );
	}

	/**
	 * Test: get_delivery_url
	 */
	function test_get_delivery_url() {
		$object   = new WC_Webhook();
		$expected = 'https://woocommerce.com';
		$object->set_delivery_url( $expected );
		$this->assertEquals( $expected, $object->get_delivery_url() );
	}

	/**
	 * Test: get_user_id
	 */
	function test_get_user_id() {
		$object   = new WC_Webhook();
		$expected = 1;
		$object->set_user_id( $expected );
		$this->assertEquals( $expected, $object->get_user_id() );
	}

	/**
	 * Test: get_api_version
	 */
	function test_get_api_version() {
		$object   = new WC_Webhook();
		$expected = 'wp_api_v2';
		$object->set_api_version( $expected );
		$this->assertEquals( $expected, $object->get_api_version() );
	}

	/**
	 * Test: get_failure_count
	 */
	function test_get_failure_count() {
		$object   = new WC_Webhook();
		$expected = 1;
		$object->set_failure_count( $expected );
		$this->assertEquals( $expected, $object->get_failure_count() );
	}

	/**
	 * Test: get_pending_delivery
	 */
	function test_get_pending_delivery() {
		$object   = new WC_Webhook();
		$expected = true;
		$object->set_pending_delivery( $expected );
		$this->assertEquals( $expected, $object->get_pending_delivery() );
	}

	/**
	 * Test: get_hooks
	 */
	function test_get_hooks() {
		$object = new WC_Webhook();
		$object->set_topic( 'order.created' );
		$expected = array(
			'woocommerce_process_shop_order_meta',
			'woocommerce_new_order',
		);
		$this->assertEquals( $expected, $object->get_hooks() );
	}

	/**
	 * Test: get_resource
	 */
	function test_get_resource() {
		$object = new WC_Webhook();
		$object->set_topic( 'order.created' );
		$this->assertEquals( 'order', $object->get_resource() );
	}

	/**
	 * Test: get_event
	 */
	function test_get_event() {
		$object = new WC_Webhook();
		$object->set_topic( 'order.created' );
		$this->assertEquals( 'created', $object->get_event() );
	}

	/**
	 * Test: get_i18n_status
	 */
	function test_get_i18n_status() {
		$object = new WC_Webhook();
		$object->set_status( 'active' );
		$this->assertEquals( 'Active', $object->get_i18n_status() );
	}

	/**
	 * Test: generate_signature
	 */
	function test_generate_signature() {
		$object = new WC_Webhook();
		$this->assertEquals( 'GBDo00G55h6IiV+6CxqivQPLbI//KzaOZm747971tPs=', $object->generate_signature( 'secret' ) );
	}
}
