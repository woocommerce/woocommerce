<?php
/**
 * Settings UI View Config integration tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIViewConfig;
use WC_Unit_Test_Case;

/**
 * Tests the Settings UI View Config REST integration.
 */
class SettingsUIViewConfigIntegrationTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should return the Products layout through the public View Config REST route.
	 */
	public function test_public_api_returns_the_products_layout(): void {
		if ( ! SettingsUIViewConfig::is_supported() ) {
			$this->markTestSkipped( 'WordPress View Config is not available.' );
		}

		$callback       = array( SettingsUIViewConfig::class, 'register_filter_for_request' );
		$was_registered = false !== has_filter( 'rest_pre_dispatch', $callback );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		SettingsUIViewConfig::register_rest_filter();
		$request = new \WP_REST_Request( 'GET', '/wp/v2/view-config' );
		$request->set_query_params(
			array(
				'kind' => 'woocommerce-settings',
				'name' => 'products:default',
			)
		);
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $data['form']['fields'] ?? array() );

		wp_set_current_user( 0 );
		if ( ! $was_registered ) {
			remove_filter( 'rest_pre_dispatch', $callback, 10 );
		}
	}
}
