<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Settings\PaymentGateways;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Controller;
use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Payment_Gateway;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the Payment Gateways Settings REST API controller.
 *
 * @class PaymentGatewaysSettingsControllerTest
 */
class PaymentGatewaysSettingsControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc/v4/settings/payment-gateways';

	/**
	 * @var Controller
	 */
	protected Controller $sut;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->sut = new Controller();
		$this->sut->register_routes();
	}

	/**
	 * Test getting a payment gateway by a user without the needed capabilities.
	 */
	public function test_get_payment_gateway_without_caps() {
		// Arrange.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/bacs' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * Test updating a payment gateway by a user without the needed capabilities.
	 */
	public function test_update_payment_gateway_without_caps() {
		// Arrange.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/bacs' );
		$request->set_param(
			'values',
			array(
				'enabled' => true,
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * Test getting a payment gateway successfully (BACS).
	 */
	public function test_get_payment_gateway_bacs_success() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/bacs' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'id', $data );
		$this->assertSame( 'bacs', $data['id'] );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'order', $data );
		$this->assertArrayHasKey( 'enabled', $data );
		$this->assertArrayHasKey( 'method_title', $data );
		$this->assertArrayHasKey( 'method_description', $data );
		$this->assertArrayHasKey( 'method_supports', $data );
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'groups', $data );

		// Verify values is an object/array with field values.
		$this->assertIsArray( $data['values'] );
		$this->assertArrayHasKey( 'enabled', $data['values'] );
		$this->assertArrayHasKey( 'title', $data['values'] );
		$this->assertArrayHasKey( 'description', $data['values'] );

		// Verify groups structure.
		$this->assertIsArray( $data['groups'] );
		$this->assertArrayHasKey( 'settings', $data['groups'] );
		$this->assertArrayHasKey( 'fields', $data['groups']['settings'] );
		$this->assertIsArray( $data['groups']['settings']['fields'] );
	}

	/**
	 * Test getting a payment gateway successfully (COD).
	 */
	public function test_get_payment_gateway_cod_success() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/cod' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'cod', $data['id'] );
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'groups', $data );
	}

	/**
	 * Test getting a payment gateway successfully (Cheque).
	 */
	public function test_get_payment_gateway_cheque_success() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/cheque' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'cheque', $data['id'] );
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'groups', $data );
	}

	/**
	 * Test getting a payment gateway with an invalid ID.
	 */
	public function test_get_payment_gateway_invalid_id() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/invalid_gateway' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_payment_gateway_invalid_id', $response->get_data()['code'] );
	}

	/**
	 * Test updating a payment gateway successfully.
	 */
	public function test_update_payment_gateway_success() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/bacs' );
		$request->set_param(
			'values',
			array(
				'enabled'     => true,
				'title'       => 'Bank Transfer',
				'description' => 'Pay via bank transfer',
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'bacs', $data['id'] );
		$this->assertTrue( $data['enabled'] );
		$this->assertSame( 'Bank Transfer', $data['title'] );
		$this->assertSame( 'Pay via bank transfer', $data['description'] );
	}

	/**
	 * Test updating a payment gateway with invalid ID.
	 */
	public function test_update_payment_gateway_invalid_id() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/invalid_gateway' );
		$request->set_param(
			'values',
			array(
				'enabled' => true,
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_payment_gateway_invalid_id', $response->get_data()['code'] );
	}

	/**
	 * Test updating a payment gateway without values parameter.
	 */
	public function test_update_payment_gateway_missing_values() {
		// Act.
		$request  = new WP_REST_Request( 'PUT', self::ENDPOINT . '/bacs' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_missing_callback_param', $response->get_data()['code'] );
	}

	/**
	 * Test updating a payment gateway with order field.
	 */
	public function test_update_payment_gateway_with_order() {
		// Arrange.
		delete_option( 'woocommerce_gateway_order' );

		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/bacs' );
		$request->set_param(
			'values',
			array(
				'order' => 5,
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 5, $data['order'] );

		// Verify the order was saved.
		$gateway_order = get_option( 'woocommerce_gateway_order' );
		$this->assertIsArray( $gateway_order );
		$this->assertArrayHasKey( 'bacs', $gateway_order );
		$this->assertSame( 5, $gateway_order['bacs'] );
	}

	/**
	 * Test updating payment gateway standard settings.
	 */
	public function test_update_payment_gateway_standard_settings() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/bacs' );
		$request->set_param(
			'values',
			array(
				'instructions' => 'Please send payment to our bank account.',
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'instructions', $data['values'] );
		$this->assertSame( 'Please send payment to our bank account.', $data['values']['instructions'] );

		// Verify settings were actually saved.
		$gateway = WC()->payment_gateways->payment_gateways()['bacs'];
		$this->assertSame( 'Please send payment to our bank account.', $gateway->settings['instructions'] );
	}

	/**
	 * Test updating COD gateway with enable_for_methods (multiselect field).
	 */
	public function test_update_cod_gateway_multiselect_field() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/cod' );
		$request->set_param(
			'values',
			array(
				'enable_for_methods' => array(),
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'enable_for_methods', $data['values'] );
	}

	/**
	 * Test updating payment gateway with unknown fields (should be silently ignored).
	 */
	public function test_update_payment_gateway_with_unknown_fields() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/bacs' );
		$request->set_param(
			'values',
			array(
				'enabled'            => true,
				'unknown_field_1234' => 'should be ignored',
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert - should succeed but ignore unknown field.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['enabled'] );
		// Unknown field should not appear in response.
		$this->assertArrayNotHasKey( 'unknown_field_1234', $data['values'] );
	}

	/**
	 * Test sanitization of text fields.
	 */
	public function test_update_payment_gateway_sanitizes_text_fields() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/bacs' );
		$request->set_param(
			'values',
			array(
				'title'       => '  Bank Transfer  ',
				'description' => '<script>alert("xss")</script>Safe text',
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		// Title should be trimmed and sanitized.
		$this->assertSame( 'Bank Transfer', $data['title'] );
		// Description should have script tags removed.
		$this->assertStringNotContainsString( '<script>', $data['description'] );
	}

	/**
	 * Test that the schema is properly registered.
	 */
	public function test_schema_is_registered() {
		// Act.
		$schema = $this->sut->get_public_item_schema();

		// Assert.
		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertSame( 'payment_gateway_settings', $schema['title'] );
		$this->assertSame( 'object', $schema['type'] );

		// Verify key properties exist.
		$properties = $schema['properties'];
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'enabled', $properties );
		$this->assertArrayHasKey( 'values', $properties );
		$this->assertArrayHasKey( 'groups', $properties );
	}

	/**
	 * Test getting BACS gateway includes account details fields.
	 */
	public function test_get_bacs_gateway_includes_account_details() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/bacs' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'values', $data );

		// BACS should have account_details in values.
		$this->assertArrayHasKey( 'account_details', $data['values'] );
	}

	/**
	 * Test updating BACS gateway with account details (special field).
	 */
	public function test_update_bacs_gateway_with_account_details() {
		// Arrange.
		$account_details = array(
			array(
				'account_name'   => 'Test Company',
				'account_number' => '12345678',
				'bank_name'      => 'Test Bank',
				'sort_code'      => '12-34-56',
				'iban'           => 'GB00TEST12345678',
				'bic'            => 'TESTBIC',
			),
		);

		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/bacs' );
		$request->set_param(
			'values',
			array(
				'account_details' => $account_details,
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'account_details', $data['values'] );

		// Verify the account details were saved.
		$saved_details = $data['values']['account_details'];
		$this->assertIsArray( $saved_details );
		$this->assertCount( 1, $saved_details );
		$this->assertSame( 'Test Company', $saved_details[0]['account_name'] );
		$this->assertSame( '12345678', $saved_details[0]['account_number'] );

		// Verify the account details were persisted to the database option.
		$saved_option = get_option( 'woocommerce_bacs_accounts' );
		$this->assertIsArray( $saved_option );
		$this->assertCount( 1, $saved_option );
		$this->assertSame( $account_details[0]['account_name'], $saved_option[0]['account_name'] );
		$this->assertSame( $account_details[0]['account_number'], $saved_option[0]['account_number'] );
		$this->assertSame( $account_details[0]['bank_name'], $saved_option[0]['bank_name'] );
		$this->assertSame( $account_details[0]['sort_code'], $saved_option[0]['sort_code'] );
		$this->assertSame( $account_details[0]['iban'], $saved_option[0]['iban'] );
		$this->assertSame( $account_details[0]['bic'], $saved_option[0]['bic'] );
	}

	/**
	 * Test updating COD gateway with enable_for_virtual (checkbox field).
	 */
	public function test_update_cod_gateway_checkbox_field() {
		// Act - enable virtual products.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/cod' );
		$request->set_param(
			'values',
			array(
				'enable_for_virtual' => true,
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'enable_for_virtual', $data['values'] );

		// Verify settings were saved (COD saves as 'yes'/'no').
		$gateway = WC()->payment_gateways->payment_gateways()['cod'];
		$this->assertSame( 'yes', $gateway->settings['enable_for_virtual'] );
	}

	/**
	 * Test that boolean fields are properly converted.
	 */
	public function test_boolean_field_conversion() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT . '/bacs' );
		$request->set_param(
			'values',
			array(
				'enabled' => false,
			)
		);
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['enabled'] );

		// Verify the gateway is actually disabled.
		$gateway = WC()->payment_gateways->payment_gateways()['bacs'];
		$this->assertSame( 'no', $gateway->enabled );
	}

	/**
	 * Test that groups contain field metadata.
	 */
	public function test_groups_contain_field_metadata() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/bacs' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertArrayHasKey( 'settings', $data['groups'] );

		$settings_group = $data['groups']['settings'];
		$this->assertArrayHasKey( 'title', $settings_group );
		$this->assertArrayHasKey( 'fields', $settings_group );
		$this->assertIsArray( $settings_group['fields'] );
		$this->assertNotEmpty( $settings_group['fields'] );

		// Check first field has required metadata.
		$first_field = $settings_group['fields'][0];
		$this->assertArrayHasKey( 'id', $first_field );
		$this->assertArrayHasKey( 'label', $first_field );
		$this->assertArrayHasKey( 'type', $first_field );
	}

	/**
	 * Test that values object contains all current settings.
	 */
	public function test_values_contain_all_settings() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/bacs' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'values', $data );

		// BACS should have standard fields.
		$this->assertArrayHasKey( 'enabled', $data['values'] );
		$this->assertArrayHasKey( 'title', $data['values'] );
		$this->assertArrayHasKey( 'description', $data['values'] );
		$this->assertArrayHasKey( 'instructions', $data['values'] );
	}

	/**
	 * Test that COD gateway enable_for_methods field has options populated.
	 */
	public function test_cod_gateway_enable_for_methods_has_options() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/cod' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertArrayHasKey( 'settings', $data['groups'] );
		$this->assertArrayHasKey( 'fields', $data['groups']['settings'] );

		// Find the enable_for_methods field.
		$enable_for_methods_field = null;
		foreach ( $data['groups']['settings']['fields'] as $field ) {
			if ( 'enable_for_methods' === $field['id'] ) {
				$enable_for_methods_field = $field;
				break;
			}
		}

		// Verify the field exists.
		$this->assertNotNull( $enable_for_methods_field, 'enable_for_methods field should exist in COD gateway fields' );

		// Verify field metadata.
		$this->assertSame( 'enable_for_methods', $enable_for_methods_field['id'] );
		$this->assertSame( 'multiselect', $enable_for_methods_field['type'] );
		$this->assertArrayHasKey( 'options', $enable_for_methods_field );

		// Verify options is an array.
		$this->assertIsArray( $enable_for_methods_field['options'] );

		// Verify options is not empty (there should be at least some shipping methods).
		$this->assertNotEmpty( $enable_for_methods_field['options'], 'enable_for_methods should have shipping method options' );

		// Verify the options structure is nested (by shipping method title).
		// The structure should be: { "Method Title": { "method_id": "Label", ... }, ... }.
		foreach ( $enable_for_methods_field['options'] as $method_group ) {
			$this->assertIsArray( $method_group, 'Each shipping method group should be an array' );
		}
	}
}
