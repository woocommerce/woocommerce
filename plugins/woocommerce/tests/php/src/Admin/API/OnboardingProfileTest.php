<?php
/**
 * Test the API controller class that handles the onboarding profile REST endpoints.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\Tests\Admin\API;

use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * OnboardingProfile API controller test.
 *
 * @class OnboardingProfileTest.
 */
class OnboardingProfileTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/onboarding/profile';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->useAdmin();
	}

	/**
	 * Use a user with administrator role.
	 *
	 * @return void
	 */
	public function useAdmin() {
		// Register an administrator user and log in.
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Use a user without any permissions.
	 *
	 * @return void
	 */
	public function useUserWithoutPluginsPermission() {
		$this->user = $this->factory->user->create();
		wp_set_current_user( $this->user );
	}

	/**
	 * Request to install-async endpoint.
	 *
	 * @param string $endpoint Request endpoint.
	 * @param array  $body Request body.
	 *
	 * @return mixed
	 */
	private function request( $endpoint, $body ) {
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . $endpoint );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
		$response = $this->server->dispatch( $request );

		return $response;
	}

	/**
	 * Test it requires 'country_code' param.
	 * @return void
	 */
	public function test_update_store_currency_and_measurement_units_requires_country_code() {
		$result = $this->request(
			'/update-store-currency-and-measurement-units',
			array()
		);
		$data   = $result->get_data();
		$this->assertArrayHasKey( 'data', $data );
		$this->assertEquals( '400', $data['data']['status'] );
		$this->assertEquals( 'country_code', $data['data']['params'][0] );
	}

	/**
	 * Test that it returns 400 status when a country code that is not available in WC was provided.
	 * @return void
	 */
	public function test_update_store_currency_and_measurement_units_throws_400_when_invalid_country_code_is_provided() {
		$result = $this->request(
			'/update-store-currency-and-measurement-units',
			array(
				'country_code' => 'invalid country code',
			)
		);

		$data = $result->get_data();
		$this->assertEquals( '400', $data['data']['status'] );
		$this->assertEquals( 'Invalid country code.', $data['message'] );
		$this->assertEquals( 'woocommerce_rest_invalid_country_code', $data['code'] );
	}

	/**
	 * Test that it updates all the required options when a valid country code is provided.
	 *
	 * @return void
	 */
	public function test_update_store_currency_and_measurement_units() {

		$get_options = function () {
			$options = array();
			// Save the current options.
			$update_fields = array(
				'woocommerce_currency',
				'woocommerce_currency_pos',
				'woocommerce_price_thousand_sep',
				'woocommerce_price_decimal_sep',
				'woocommerce_price_num_decimals',
				'woocommerce_weight_unit',
				'woocommerce_dimension_unit',
			);

			foreach ( $update_fields as $field ) {
				$options[ $field ] = get_option( $field );
			}
			return $options;
		};

		$prev_options = $get_options();

		// Update the values to UK.
		$this->request(
			'/update-store-currency-and-measurement-units',
			array(
				'country_code' => 'GB',
			)
		);

		$uk_options = $get_options();

		// Update the values to US.
		$this->request(
			'/update-store-currency-and-measurement-units',
			array(
				'country_code' => 'US',
			)
		);

		$us_options = $get_options();

		$expected_values = array(
			'woocommerce_currency'           => 'USD',
			'woocommerce_currency_pos'       => 'left',
			'woocommerce_price_thousand_sep' => ',',
			'woocommerce_price_decimal_sep'  => '.',
			'woocommerce_price_num_decimals' => '2',
			'woocommerce_weight_unit'        => 'lbs',
			'woocommerce_dimension_unit'     => 'in',
		);

		$this->assertNotSame( $expected_values, $uk_options );
		$this->assertSame( $expected_values, $us_options );

		// Restore the prev options.
		foreach ( $prev_options as $field => $value ) {
			update_option( $field, $value );
		}
	}
}
