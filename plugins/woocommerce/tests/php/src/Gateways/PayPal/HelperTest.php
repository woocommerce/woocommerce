<?php
/**
 * Unit tests for Automattic\WooCommerce\Gateways\PayPal\Helper class.
 *
 * @package WooCommerce\Tests\Gateways\PayPal
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;
use Automattic\WooCommerce\Gateways\PayPal\Helper;

/**
 * Class HelperTest.
 */
class HelperTest extends \WC_Unit_Test_Case {

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

		$result = Helper::is_paypal_gateway_available();

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

		$result = Helper::is_orders_v2_migration_eligible();

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test get_wc_order_from_paypal_custom_id returns order when valid custom ID is provided.
	 */
	public function test_get_wc_order_from_paypal_custom_id_returns_order_when_valid() {
		// Create a test order.
		$order = \WC_Helper_Order::create_order();
		$order->save();

		$custom_id = wp_json_encode(
			array(
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
				'site_url'  => 'https://example.com',
				'site_id'   => 12345,
			)
		);

		$result = Helper::get_wc_order_from_paypal_custom_id( $custom_id );

		$this->assertInstanceOf( \WC_Order::class, $result );
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

		$result = Helper::get_wc_order_from_paypal_custom_id( $custom_id );

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

		$result = Helper::get_wc_order_from_paypal_custom_id( $custom_id );

		$this->assertNull( $result );
	}

	/**
	 * Test get_wc_order_from_paypal_custom_id returns null when order key doesn't match.
	 */
	public function test_get_wc_order_from_paypal_custom_id_returns_null_when_order_key_mismatch() {
		// Create a test order.
		$order = \WC_Helper_Order::create_order();
		$order->save();

		$custom_id = wp_json_encode(
			array(
				'order_id'  => $order->get_id(),
				'order_key' => 'wrong_order_key', // Wrong order key.
				'site_url'  => 'https://example.com',
			)
		);

		$result = Helper::get_wc_order_from_paypal_custom_id( $custom_id );

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
			'empty_string'          => array(
				'custom_id' => '',
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
	 * @param string $custom_id The custom ID to test.
	 * @param mixed  $expected  The expected result.
	 */
	public function test_get_wc_order_from_paypal_custom_id_invalid_inputs( $custom_id, $expected ) {
		$result = Helper::get_wc_order_from_paypal_custom_id( $custom_id );

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
		$result = Helper::mask_email( $email );

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

		$result = Helper::redact_data( $data );

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
		$this->assertEquals( 'string', Helper::redact_data( 'string' ) );
		$this->assertEquals( 123, Helper::redact_data( 123 ) );
		$this->assertEquals( null, Helper::redact_data( null ) );
		$this->assertEquals( true, Helper::redact_data( true ) );
	}

	/**
	 * Test redact_data handles empty arrays.
	 */
	public function test_redact_data_handles_empty_array() {
		$result = Helper::redact_data( array() );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test update_addresses_in_order updates both shipping and billing addresses.
	 */
	public function test_update_addresses_in_order_updates_addresses() {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		$paypal_order_details = array(
			'purchase_units' => array(
				array(
					'shipping' => array(
						'name'    => array(
							'full_name' => 'John Doe',
						),
						'address' => array(
							'country_code'   => 'US',
							'postal_code'    => '12345',
							'admin_area_1'   => 'CA',
							'admin_area_2'   => 'San Francisco',
							'address_line_1' => '123 Main St',
							'address_line_2' => 'Apt 4B',
						),
					),
				),
			),
			'payer'          => array(
				'name'          => array(
					'given_name' => 'Jane',
					'surname'    => 'Smith',
				),
				'email_address' => 'jane.smith@example.com',
				'address'       => array(
					'country_code'   => 'US',
					'postal_code'    => '54321',
					'admin_area_1'   => 'NY',
					'admin_area_2'   => 'New York',
					'address_line_1' => '456 Broadway',
					'address_line_2' => 'Suite 100',
				),
			),
		);

		Helper::update_addresses_in_order( $order, $paypal_order_details );

		// Verify shipping address was updated.
		$this->assertEquals( 'John', $order->get_shipping_first_name() );
		$this->assertEquals( 'Doe', $order->get_shipping_last_name() );
		$this->assertEquals( 'US', $order->get_shipping_country() );
		$this->assertEquals( '12345', $order->get_shipping_postcode() );
		$this->assertEquals( 'CA', $order->get_shipping_state() );
		$this->assertEquals( 'San Francisco', $order->get_shipping_city() );
		$this->assertEquals( '123 Main St', $order->get_shipping_address_1() );
		$this->assertEquals( 'Apt 4B', $order->get_shipping_address_2() );

		// Verify billing address was updated.
		$this->assertEquals( 'Jane', $order->get_billing_first_name() );
		$this->assertEquals( 'Smith', $order->get_billing_last_name() );
		$this->assertEquals( 'jane.smith@example.com', $order->get_billing_email() );
		$this->assertEquals( 'US', $order->get_billing_country() );
		$this->assertEquals( '54321', $order->get_billing_postcode() );
		$this->assertEquals( 'NY', $order->get_billing_state() );
		$this->assertEquals( 'New York', $order->get_billing_city() );
		$this->assertEquals( '456 Broadway', $order->get_billing_address_1() );
		$this->assertEquals( 'Suite 100', $order->get_billing_address_2() );

		// Verify meta flag was set.
		$this->assertEquals( 'yes', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ADDRESSES_UPDATED, true ) );

		// Clean up.
		$order->delete( true );
	}

	/**
	 * Test update_addresses_in_order does not update when order is null.
	 */
	public function test_update_addresses_in_order_skips_null_order() {
		$paypal_order_details = array(
			'purchase_units' => array(
				array(
					'shipping' => array(
						'name' => array( 'full_name' => 'John Doe' ),
					),
				),
			),
		);

		// Should not throw an error.
		Helper::update_addresses_in_order( null, $paypal_order_details );

		// No assertions, just ensuring no exception is thrown.
		$this->assertTrue( true );
	}

	/**
	 * Test update_addresses_in_order does not update when paypal_order_details is empty.
	 */
	public function test_update_addresses_in_order_skips_empty_details() {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		$original_shipping_first_name = $order->get_shipping_first_name();
		$original_billing_first_name  = $order->get_billing_first_name();

		Helper::update_addresses_in_order( $order, array() );

		// Order should not be modified.
		$this->assertEquals( $original_shipping_first_name, $order->get_shipping_first_name() );
		$this->assertEquals( $original_billing_first_name, $order->get_billing_first_name() );
		$this->assertEmpty( $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ADDRESSES_UPDATED, true ) );

		// Clean up.
		$order->delete( true );
	}

	/**
	 * Test update_addresses_in_order does not update when already updated.
	 */
	public function test_update_addresses_in_order_skips_already_updated() {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ADDRESSES_UPDATED, 'yes' );
		$order->save();

		$original_shipping_first_name = $order->get_shipping_first_name();

		$paypal_order_details = array(
			'purchase_units' => array(
				array(
					'shipping' => array(
						'name' => array(
							'full_name' => 'Different Name',
						),
					),
				),
			),
		);

		Helper::update_addresses_in_order( $order, $paypal_order_details );

		// Order should not be modified.
		$this->assertEquals( $original_shipping_first_name, $order->get_shipping_first_name() );

		// Clean up.
		$order->delete( true );
	}

	/**
	 * Test update_addresses_in_order handles partial address data.
	 */
	public function test_update_addresses_in_order_handles_partial_address_data() {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		$paypal_order_details = array(
			'purchase_units' => array(
				array(
					'shipping' => array(
						'name'    => array(
							'full_name' => 'John Doe',
						),
						'address' => array(
							'country_code' => 'US',
							// Only country code, missing other fields.
						),
					),
				),
			),
			'payer'          => array(
				'name'    => array(
					'given_name' => 'Jane',
					// Missing surname.
				),
				'address' => array(
					'postal_code' => '12345',
					// Only postal code.
				),
			),
		);

		Helper::update_addresses_in_order( $order, $paypal_order_details );

		// Shipping country should be set, other fields should be empty strings.
		$this->assertEquals( 'US', $order->get_shipping_country() );
		$this->assertEquals( '', $order->get_shipping_postcode() );
		$this->assertEquals( '', $order->get_shipping_state() );

		// Billing should have given name and postal code.
		$this->assertEquals( 'Jane', $order->get_billing_first_name() );
		$this->assertEquals( '', $order->get_billing_last_name() );
		$this->assertEquals( '12345', $order->get_billing_postcode() );

		// Clean up.
		$order->delete( true );
	}
}
