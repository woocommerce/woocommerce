<?php

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\Init;
use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannels as MarketingChannelsService;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * PaymentGatewaySuggestionsTest API controller test.
 *
 * @class PaymentGatewaySuggestionsTest.
 */
class PaymentGatewaySuggestionsTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/payment-gateway-suggestions';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		// Register an administrator user and log in.
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );

	}

	/**
	 * Test it clears cache when the base country gets updated.
	 *
	 * @return void
	 */
	public function test_it_clears_cache_when_the_base_country_gets_updated() {
		// Clear any exisiting cache first.
		Init::delete_specs_transient();

		$existing_base_country = wc_get_base_location();
		// update the base country to the U.S for testing purposes.
		update_option( 'woocommerce_default_country', 'US:CA' );

		$response_mock_ref = function( $preempt, $parsed_args, $url ) {
			if ( str_contains( $url, 'https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/1.0/suggestions.json' ) ) {
				return array(
					'success' => true,
					'body'    => wp_json_encode(
						array(
							array(
								'id' => wc_get_base_location()['country'],
							),
						)
					),
				);
			}

			return $preempt;
		};

		// Make a new request -- this should populate the cache with the base country.
		add_filter( 'pre_http_request', $response_mock_ref, 10, 3 );
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = rest_get_server()->dispatch( $request )->get_data();

		// Confirm the current data returns id = US.
		$this->assertEquals( 'US', $response[0]->id );

		// Remove filter just in case and a new request still returns the cached data.
		remove_filter( 'pre_http_request', $response_mock_ref );
		$response = rest_get_server()->dispatch( $request )->get_data();
		$this->assertEquals( 'US', $response[0]->id );

		add_filter( 'pre_http_request', $response_mock_ref, 10, 3 );

		// Update the base country to CA.
		update_option( 'woocommerce_default_country', 'CA:ON' );

		// Make a new request -- this should populate the cache with the updated country.
		$response = rest_get_server()->dispatch( $request )->get_data();
		$this->assertEquals( 'CA', $response[0]->id );

		// Clean up.
		remove_filter( 'pre_http_request', $response_mock_ref );

		// restore the base country.
		update_option( 'woocommerce_default_country', $existing_base_country['country'] . ':' . $existing_base_country['state'] );
	}
}
