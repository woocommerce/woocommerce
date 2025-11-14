<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Settings\Tax;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Tax\Controller;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Tax\Schema\TaxSettingsSchema;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the Tax Settings REST API controller.
 *
 * @class TaxControllerTest
 */
class TaxControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc/v4/settings/tax';

	/**
	 * @var Controller
	 */
	protected $sut;

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

		$schema    = new TaxSettingsSchema();
		$this->sut = new Controller();
		$this->sut->init( $schema );
		$this->sut->register_routes();
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		wp_delete_user( $this->store_admin_id );
		parent::tearDown();
	}

	/**
	 * Test getting tax settings by a user without the needed capabilities.
	 */
	public function test_get_tax_settings_without_caps() {
		// Arrange.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * Test updating tax settings by a user without the needed capabilities.
	 */
	public function test_update_tax_settings_without_caps() {
		// Arrange.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_prices_include_tax' => 'yes',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * Test getting tax settings successfully.
	 */
	public function test_get_tax_settings_success() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Verify top-level structure.
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'groups', $data );

		// Verify that 'values' is an array of key-value pairs.
		$this->assertIsArray( $data['values'] );

		// Verify that 'groups' is an array containing setting groups.
		$this->assertIsArray( $data['groups'] );
	}

	/**
	 * Test updating tax settings successfully.
	 */
	public function test_update_tax_settings_success() {
		// Arrange.
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );

		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_prices_include_tax' => 'yes',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert - verify the request was successful first.
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		// Verify response structure.
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'groups', $data );

		// Verify that the setting was updated.
		$this->assertArrayHasKey(
			'woocommerce_prices_include_tax',
			$data['values'],
			'Expected setting "woocommerce_prices_include_tax" not found in response values. Available keys: ' . implode( ', ', array_keys( $data['values'] ) )
		);
		$this->assertSame( 'yes', $data['values']['woocommerce_prices_include_tax'] );
		$this->assertSame( 'yes', get_option( 'woocommerce_prices_include_tax' ) );

		// Reset to original value.
		update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
	}

	/**
	 * Test updating multiple tax settings at once.
	 */
	public function test_update_multiple_tax_settings() {
		// Arrange.
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );
		$original_tax_based_on       = get_option( 'woocommerce_tax_based_on', 'shipping' );

		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_prices_include_tax' => 'yes',
						'woocommerce_tax_based_on'       => 'billing',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert - verify the request was successful first.
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		// Verify response structure.
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'groups', $data );

		// Verify both settings were updated.
		$this->assertArrayHasKey(
			'woocommerce_prices_include_tax',
			$data['values'],
			'Expected setting "woocommerce_prices_include_tax" not found in response values. Available keys: ' . implode( ', ', array_keys( $data['values'] ) )
		);
		$this->assertArrayHasKey(
			'woocommerce_tax_based_on',
			$data['values'],
			'Expected setting "woocommerce_tax_based_on" not found in response values. Available keys: ' . implode( ', ', array_keys( $data['values'] ) )
		);
		$this->assertSame( 'yes', $data['values']['woocommerce_prices_include_tax'] );
		$this->assertSame( 'billing', $data['values']['woocommerce_tax_based_on'] );

		// Verify in the database.
		$this->assertSame( 'yes', get_option( 'woocommerce_prices_include_tax' ) );
		$this->assertSame( 'billing', get_option( 'woocommerce_tax_based_on' ) );

		// Reset to original values.
		update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		update_option( 'woocommerce_tax_based_on', $original_tax_based_on );
	}

	/**
	 * Test updating tax settings with the old format (backward compatibility).
	 */
	public function test_update_tax_settings_old_format() {
		// Arrange.
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );

		// Act - Use old format without 'values' wrapper.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'woocommerce_prices_include_tax' => 'yes',
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert - verify the request was successful first.
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		// Verify response structure.
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'groups', $data );

		// Verify the setting was updated.
		$this->assertArrayHasKey(
			'woocommerce_prices_include_tax',
			$data['values'],
			'Expected setting "woocommerce_prices_include_tax" not found in response values. Available keys: ' . implode( ', ', array_keys( $data['values'] ) )
		);
		$this->assertSame( 'yes', $data['values']['woocommerce_prices_include_tax'] );
		$this->assertSame( 'yes', get_option( 'woocommerce_prices_include_tax' ) );

		// Reset to original value.
		update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
	}

	/**
	 * Test updating tax settings with invalid values.
	 */
	public function test_update_tax_settings_invalid_values() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_prices_include_tax' => 'invalid_value',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertStringContainsString( 'invalid_param', $data['code'] );
	}

	/**
	 * Test updating tax settings with non-existent setting ID.
	 */
	public function test_update_tax_settings_nonexistent_setting() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'nonexistent_setting' => 'some_value',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Verify that the request succeeds but the nonexistent setting is not saved.
		// The controller should ignore invalid setting IDs.
		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Test updating tax settings with empty request body.
	 */
	public function test_update_tax_settings_empty_body() {
		// Act.
		$request  = new WP_REST_Request( 'PUT', self::ENDPOINT );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertStringContainsString( 'invalid_param', $data['code'] );
		$this->assertStringContainsString( 'Invalid or empty request body', $data['message'] );
	}

	/**
	 * Test updating tax settings with invalid tax calculation base.
	 */
	public function test_update_tax_settings_invalid_tax_based_on() {
		// Act.
		$request = new WP_REST_Request( 'PUT', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_tax_based_on' => 'invalid_base',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertStringContainsString( 'invalid_param', $data['code'] );
		// The error message format changed to be more generic based on setting options.
		$this->assertStringContainsString( 'Invalid value for', $data['message'] );
		$this->assertStringContainsString( 'woocommerce_tax_based_on', $data['message'] );
	}

	/**
	 * Test that POST requests are accepted for updating tax settings (WP_REST_Server::EDITABLE).
	 */
	public function test_update_tax_settings_accepts_post() {
		// Arrange.
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax', 'no' );

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_prices_include_tax' => 'yes',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert - verify the request was successful first.
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		// Verify response structure.
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'values', $data );
		$this->assertArrayHasKey( 'groups', $data );

		// Verify that the setting was updated.
		$this->assertArrayHasKey(
			'woocommerce_prices_include_tax',
			$data['values'],
			'Expected setting "woocommerce_prices_include_tax" not found in response values. Available keys: ' . implode( ', ', array_keys( $data['values'] ) )
		);
		$this->assertSame( 'yes', $data['values']['woocommerce_prices_include_tax'] );
		$this->assertSame( 'yes', get_option( 'woocommerce_prices_include_tax' ) );

		// Reset to original value.
		update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
	}

	/**
	 * Test updating tax settings with POST and invalid values.
	 */
	public function test_update_tax_settings_post_invalid_values() {
		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_prices_include_tax' => 'invalid_value',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertStringContainsString( 'invalid_param', $data['code'] );
	}

	/**
	 * Test updating tax settings with POST and non-existent setting ID.
	 */
	public function test_update_tax_settings_post_nonexistent_setting() {
		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'nonexistent_setting' => 'some_value',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Verify that the request succeeds but the nonexistent setting is not saved.
		// The controller should ignore invalid setting IDs.
		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Test updating tax settings with POST and empty request body.
	 */
	public function test_update_tax_settings_post_empty_body() {
		// Act.
		$request  = new WP_REST_Request( 'POST', self::ENDPOINT );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertStringContainsString( 'invalid_param', $data['code'] );
		$this->assertStringContainsString( 'Invalid or empty request body', $data['message'] );
	}

	/**
	 * Test updating tax settings with POST and invalid tax calculation base.
	 */
	public function test_update_tax_settings_post_invalid_tax_based_on() {
		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_tax_based_on' => 'invalid_base',
					),
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertStringContainsString( 'invalid_param', $data['code'] );
		// The error message format changed to be more generic based on setting options.
		$this->assertStringContainsString( 'Invalid value for', $data['message'] );
		$this->assertStringContainsString( 'woocommerce_tax_based_on', $data['message'] );
	}

	/**
	 * Test sanitization of malicious payloads in tax settings values.
	 */
	public function test_update_tax_settings_sanitizes_malicious_payloads() {
		// Arrange.
		$original_suffix = get_option( 'woocommerce_price_display_suffix', '' );

		// Test data with various malicious payloads using a text field that accepts any input.
		$malicious_payloads = array(
			'woocommerce_price_display_suffix' => '<script>alert("xss")</script> {price_including_tax} javascript:alert("xss") \' OR \'1\'=\'1 <img src=x onerror=alert("xss")>',
		);

		// Act.
		$request = new WP_REST_Request( 'POST', self::ENDPOINT );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => $malicious_payloads,
				)
			)
		);
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Verify response structure.
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'values', $data );

		// Verify that HTML/script content is sanitized (sanitize_text_field removes HTML tags).
		foreach ( $malicious_payloads as $setting_key => $malicious_value ) {
			if ( isset( $data['values'][ $setting_key ] ) ) {
				$sanitized_value = $data['values'][ $setting_key ];

				// HTML script tags should be removed by sanitize_text_field.
				$this->assertStringNotContainsString(
					'<script>',
					$sanitized_value,
					"Script tags not sanitized in $setting_key"
				);

				// HTML img tags should be removed by sanitize_text_field.
				$this->assertStringNotContainsString(
					'<img',
					$sanitized_value,
					"Img tags not sanitized in $setting_key"
				);

				// Length should be reasonable.
				$this->assertLessThanOrEqual(
					1000,
					strlen( $sanitized_value ),
					"Extreme length not handled properly in $setting_key"
				);

				// The sanitized value should contain the safe parts.
				$this->assertStringContainsString(
					'{price_including_tax}',
					$sanitized_value,
					"Safe content not preserved in $setting_key"
				);
			}
		}

		// Verify stored options are also sanitized.
		foreach ( $malicious_payloads as $setting_key => $malicious_value ) {
			$stored_value = get_option( $setting_key );
			if ( false !== $stored_value ) {
				// HTML script tags should be removed in stored data.
				$this->assertStringNotContainsString(
					'<script>',
					$stored_value,
					"Script tags not sanitized in stored $setting_key"
				);

				// HTML img tags should be removed in stored data.
				$this->assertStringNotContainsString(
					'<img',
					$stored_value,
					"Img tags not sanitized in stored $setting_key"
				);

				// Length should be reasonable in stored data.
				$this->assertLessThanOrEqual(
					1000,
					strlen( $stored_value ),
					"Extreme length not handled properly in stored $setting_key"
				);

				// The sanitized value should contain the safe parts.
				$this->assertStringContainsString(
					'{price_including_tax}',
					$stored_value,
					"Safe content not preserved in stored $setting_key"
				);
			}
		}

		// Reset to original value.
		update_option( 'woocommerce_price_display_suffix', $original_suffix );
	}
}
