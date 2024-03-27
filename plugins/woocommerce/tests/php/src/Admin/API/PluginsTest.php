<?php
/**
 * Test the API controller class that handles the marketing campaigns REST response.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

namespace Automattic\WooCommerce\Tests\Admin\API;

use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * MarketingCampaigns API controller test.
 *
 * @class MarketingCampaignsTest.
 */
class PluginsTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/plugins/';


	public function test_connect_jetpack_requires_manage_woocommerce() {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . 'connect-jetpack' );
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );

		$user = $this->factory->user->create(
			array(
				'role' => 'shop_manager',
			)
		);

		wp_set_current_user( $user );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT . 'connect-jetpack' );
		$request->set_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		// We'll get an error from the endpoint since Jetpack isn't installed.
		// We just want to make sure we're passing the permission check.
		$this->assertTrue( $response->get_status() !== 401 );
	}
}
