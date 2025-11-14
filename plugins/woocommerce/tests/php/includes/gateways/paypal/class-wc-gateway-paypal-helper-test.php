<?php
/**
 * Unit tests for WC_Gateway_Paypal_Helper class.
 *
 * @package WooCommerce\Tests\Paypal.
 */

declare(strict_types=1);

require_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-helper.php';

/**
 * Class WC_Gateway_Paypal_Helper_Test.
 */
class WC_Gateway_Paypal_Helper_Test extends \WC_Unit_Test_Case {

	/**
	 * Tear down the test environment.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_paypal_settings' );

		parent::tearDown();
	}

	/**
	 * Data provider for is_paypal_gateway_available test scenarios.
	 *
	 * @return array
	 */
	public function provider_paypal_gateway_availability_scenarios() {
		return array(
			'enabled_and_should_load'      => array(
				'settings' => array(
					'enabled'      => 'yes',
					'_should_load' => 'yes',
				),
				'expected' => true,
			),
			'enabled_but_should_not_load'  => array(
				'settings' => array(
					'enabled'      => 'yes',
					'_should_load' => 'no',
				),
				'expected' => false,
			),
			'disabled_but_should_load'     => array(
				'settings' => array(
					'enabled'      => 'no',
					'_should_load' => 'yes',
				),
				'expected' => false,
			),
			'disabled_and_should_not_load' => array(
				'settings' => array(
					'enabled'      => 'no',
					'_should_load' => 'no',
				),
				'expected' => false,
			),
			'missing_enabled_setting'      => array(
				'settings' => array( '_should_load' => 'yes' ),
				'expected' => false,
			),
			'missing_should_load_setting'  => array(
				'settings' => array( 'enabled' => 'yes' ),
				'expected' => false,
			),
			'empty_settings'               => array(
				'settings' => array(),
				'expected' => false,
			),
		);
	}

	/**
	 * Test is_paypal_gateway_available with various scenarios.
	 *
	 * @dataProvider provider_paypal_gateway_availability_scenarios
	 *
	 * @param array $settings The PayPal settings to test.
	 * @param bool  $expected The expected result.
	 */
	public function test_is_paypal_gateway_available_scenarios( $settings, $expected ) {
		update_option( 'woocommerce_paypal_settings', $settings );

		$result = WC_Gateway_Paypal_Helper::is_paypal_gateway_available();

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for migration eligibility test scenarios.
	 *
	 * @return array
	 */
	public function provider_migration_eligibility_scenarios() {
		return array(
			'no_api_keys_live_mode'     => array(
				'settings' => array( 'testmode' => 'no' ),
				'expected' => true,
			),
			'no_api_keys_test_mode'     => array(
				'settings' => array( 'testmode' => 'yes' ),
				'expected' => true,
			),
			'live_api_keys'             => array(
				'settings' => array(
					'testmode'      => 'no',
					'api_username'  => 'username',
					'api_password'  => 'password',
					'api_signature' => 'signature',
				),
				'expected' => false,
			),
			'live_signature_only'       => array(
				'settings' => array(
					'testmode'      => 'no',
					'api_signature' => 'signature',
				),
				'expected' => false,
			),
			'sandbox_api_keys'          => array(
				'settings' => array(
					'testmode'              => 'yes',
					'sandbox_api_username'  => 'username',
					'sandbox_api_password'  => 'password',
					'sandbox_api_signature' => 'signature',
				),
				'expected' => false,
			),
			'sandbox_username_only'     => array(
				'settings' => array(
					'testmode'             => 'yes',
					'sandbox_api_username' => 'username',
				),
				'expected' => false,
			),
			'live_keys_in_test_mode'    => array(
				'settings' => array(
					'testmode'      => 'yes',
					'api_username'  => 'username',
					'api_password'  => 'password',
					'api_signature' => 'signature',
				),
				'expected' => true, // Should ignore live keys in test mode.
			),
			'sandbox_keys_in_live_mode' => array(
				'settings' => array(
					'testmode'              => 'no',
					'sandbox_api_username'  => 'username',
					'sandbox_api_password'  => 'password',
					'sandbox_api_signature' => 'signature',
				),
				'expected' => true, // Should ignore sandbox keys in live mode.
			),
		);
	}

	/**
	 * Test is_orders_v2_migration_eligible with various scenarios.
	 *
	 * @dataProvider provider_migration_eligibility_scenarios
	 *
	 * @param array $settings The PayPal settings to test.
	 * @param bool  $expected The expected result.
	 */
	public function test_is_orders_v2_migration_eligible_scenarios( $settings, $expected ) {
		update_option( 'woocommerce_paypal_settings', $settings );

		$result = WC_Gateway_Paypal_Helper::is_orders_v2_migration_eligible();

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test get_wc_order_from_paypal_custom_id returns order when valid custom ID is provided.
	 */
	public function test_get_wc_order_from_paypal_custom_id_returns_order_when_valid() {
		// Create a test order.
		$order = WC_Helper_Order::create_order();
		$order->save();

		$custom_id = wp_json_encode(
			array(
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
				'site_url'  => 'https://example.com',
				'site_id'   => 12345,
			)
		);

		$result = WC_Gateway_Paypal_Helper::get_wc_order_from_paypal_custom_id( $custom_id );

		$this->assertInstanceOf( WC_Order::class, $result );
		$this->assertEquals( $order->get_id(), $result->get_id() );

		// Clean up.
		$order->delete( true );
	}

	/**
	 * Test get_wc_order_from_paypal_custom_id returns null when order_id is missing.
	 */
	public function test_get_wc_order_from_paypal_custom_id_returns_null_when_order_id_missing() {
		$custom_id = wp_json_encode(
			array(
				'order_key' => 'some_key',
				'site_url'  => 'https://example.com',
			)
		);

		$result = WC_Gateway_Paypal_Helper::get_wc_order_from_paypal_custom_id( $custom_id );

		$this->assertNull( $result );
	}

	/**
	 * Test get_wc_order_from_paypal_custom_id returns null when order doesn't exist.
	 */
	public function test_get_wc_order_from_paypal_custom_id_returns_null_when_order_not_exists() {
		$custom_id = wp_json_encode(
			array(
				'order_id'  => 99999,
				'order_key' => 'some_key',
				'site_url'  => 'https://example.com',
			)
		);

		$result = WC_Gateway_Paypal_Helper::get_wc_order_from_paypal_custom_id( $custom_id );

		$this->assertNull( $result );
	}

	/**
	 * Test get_wc_order_from_paypal_custom_id returns null when order key doesn't match.
	 */
	public function test_get_wc_order_from_paypal_custom_id_returns_null_when_order_key_mismatch() {
		// Create a test order.
		$order = WC_Helper_Order::create_order();
		$order->save();

		$custom_id = wp_json_encode(
			array(
				'order_id'  => $order->get_id(),
				'order_key' => 'wrong_order_key', // Wrong order key.
				'site_url'  => 'https://example.com',
			)
		);

		$result = WC_Gateway_Paypal_Helper::get_wc_order_from_paypal_custom_id( $custom_id );

		$this->assertNull( $result );

		// Clean up.
		$order->delete( true );
	}

	/**
	 * Data provider for custom ID test scenarios.
	 *
	 * @return array
	 */
	public function provider_custom_id_scenarios() {
		return array(
			'null_input'            => array(
				'custom_id' => null,
				'expected'  => null,
			),
			'empty_string'          => array(
				'custom_id' => '',
				'expected'  => null,
			),
			'integer_input'         => array(
				'custom_id' => 123,
				'expected'  => null,
			),
			'array_input'           => array(
				'custom_id' => array( 'test' ),
				'expected'  => null,
			),
			'invalid_json'          => array(
				'custom_id' => 'not-json',
				'expected'  => null,
			),
			'json_string_not_array' => array(
				'custom_id' => '"just-a-string"',
				'expected'  => null,
			),
		);
	}

	/**
	 * Test get_wc_order_from_paypal_custom_id with invalid inputs.
	 *
	 * @dataProvider provider_custom_id_scenarios
	 *
	 * @param mixed $custom_id The custom ID to test.
	 * @param mixed $expected  The expected result.
	 */
	public function test_get_wc_order_from_paypal_custom_id_invalid_inputs( $custom_id, $expected ) {
		$result = WC_Gateway_Paypal_Helper::get_wc_order_from_paypal_custom_id( $custom_id );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test mask_email masks email addresses correctly.
	 *
	 * @dataProvider provider_email_masking_scenarios
	 *
	 * @param string $email    The email to mask.
	 * @param string $expected The expected masked result.
	 */
	public function test_mask_email( $email, $expected ) {
		$result = WC_Gateway_Paypal_Helper::mask_email( $email );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for email masking test scenarios.
	 *
	 * @return array
	 */
	public function provider_email_masking_scenarios() {
		return array(
			'normal_email'               => array(
				'email'    => 'john.doe@example.com',
				'expected' => 'jo*****e@example.com',
			),
			'short_email'                => array(
				'email'    => 'ab@example.com',
				'expected' => '**@example.com',
			),
			'single_char_email'          => array(
				'email'    => 'a@example.com',
				'expected' => '*@example.com',
			),
			'long_email'                 => array(
				'email'    => 'verylongusername@example.com',
				'expected' => 've*************e@example.com',
			),
			'empty_string'               => array(
				'email'    => '',
				'expected' => '',
			),
			'not_string'                 => array(
				'email'    => 123,
				'expected' => 123,
			),
			'invalid_email_string'       => array(
				'email'    => 'notanemail',
				'expected' => 'notanemail',
			),
			'invalid_email_empty_local'  => array(
				'email'    => '@example.com',
				'expected' => '@example.com',
			),
			'invalid_email_empty_domain' => array(
				'email'    => 'user@',
				'expected' => 'user@',
			),
			'multiple_at_symbols'        => array(
				'email'    => 'user@domain@com',
				'expected' => 'us*r@domain@com',
			),
		);
	}

	/**
	 * Test redact_data removes PII from arrays.
	 */
	public function test_redact_data_removes_pii_from_arrays() {
		$data = array(
			'order_id'       => '12345',
			'amount'         => '100.00',
			'currency'       => 'USD',
			'status'         => 'completed',
			'transaction_id' => 'TXN123456',
			'given_name'     => 'John',
			'surname'        => 'Doe',
			'email_address'  => 'john.doe@example.com',
			'phone'          => '123-456-7890',
			'safe_field'     => 'keep_this',
			'payee'          => array(
				'email_address' => 'merchant@example.com',
			),
			'nested'         => array(
				'address_line_1' => '123 Main St',
				'safe_nested'    => 'keep_this_too',
			),
		);

		$result = WC_Gateway_Paypal_Helper::redact_data( $data );

		// PII fields should be redacted.
		$this->assertEquals( '[redacted]', $result['given_name'] );
		$this->assertEquals( '[redacted]', $result['surname'] );
		$this->assertEquals( 'jo*****e@example.com', $result['email_address'] );
		$this->assertEquals( '[redacted]', $result['phone'] );

		// Safe fields should be preserved.
		$this->assertEquals( 'keep_this', $result['safe_field'] );
		$this->assertEquals( '12345', $result['order_id'] );
		$this->assertEquals( '100.00', $result['amount'] );
		$this->assertEquals( 'USD', $result['currency'] );
		$this->assertEquals( 'completed', $result['status'] );
		$this->assertEquals( 'TXN123456', $result['transaction_id'] );

		// Payee information should be preserved (merchant data).
		$this->assertEquals( 'merchant@example.com', $result['payee']['email_address'] );

		// Nested PII should be redacted.
		$this->assertEquals( '[redacted]', $result['nested']['address_line_1'] );
		$this->assertEquals( 'keep_this_too', $result['nested']['safe_nested'] );
	}

	/**
	 * Test redact_data handles non-array inputs.
	 */
	public function test_redact_data_handles_non_array_inputs() {
		$this->assertEquals( 'string', WC_Gateway_Paypal_Helper::redact_data( 'string' ) );
		$this->assertEquals( 123, WC_Gateway_Paypal_Helper::redact_data( 123 ) );
		$this->assertEquals( null, WC_Gateway_Paypal_Helper::redact_data( null ) );
		$this->assertEquals( true, WC_Gateway_Paypal_Helper::redact_data( true ) );
	}

	/**
	 * Test redact_data handles empty arrays.
	 */
	public function test_redact_data_handles_empty_array() {
		$result = WC_Gateway_Paypal_Helper::redact_data( array() );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}
}
