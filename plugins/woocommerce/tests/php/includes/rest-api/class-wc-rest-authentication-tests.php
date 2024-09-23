<?php

/**
 * Tests relating to our REST authentication logic.
 */
class WC_REST_Authentication_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * The default behaviour is to record a last_access datetime only once per request.
	 *
	 * This should only be for the 'primary' REST API request, and not programmatically
	 * generated REST API requests used for internal purposes.
	 *
	 * @return void
	 */
	public function test_last_access(): void {
		global $wp;
		$original_request = $wp->request;

		// Prepare the WC_Rest_Authentication instance for testing.
		$wc_rest_authentication = WC_REST_Authentication::instance();
		$update_last_access     = new ReflectionMethod( $wc_rest_authentication, 'update_last_access' );
		$authenticated_user     = new ReflectionProperty( $wc_rest_authentication, 'user' );

		$update_last_access->setAccessible( true );
		$authenticated_user->setAccessible( true );
		$original_authenticated_user = $authenticated_user->getValue( $wc_rest_authentication );
		$authenticated_user->setValue(
			$wc_rest_authentication,
			(object) array(
				'key_id'      => 1,
				'user_id'     => 1,
				'permissions' => 'read_write',
			)
		);

		// Spy on decisions to log REST API access.
		$last_access_updated = false;
		$last_access_spy     = function ( $do_not_record ) use ( &$last_access_updated ) {
			$last_access_updated = ! $do_not_record;
		};
		add_filter( 'woocommerce_disable_rest_api_access_log', $last_access_spy );

		// Test if last_access is updated for programmatic API requests.
		$update_last_access->invoke( $wc_rest_authentication, new WP_REST_Request( 'GET', '/wc/v3/products' ) );
		$this->assertFalse(
			$last_access_updated,
			'If a REST API request is created programmatically, the default is to not update the corresponding last_access time.'
		);

		// Test if last_access is updated for 'real' API requests.
		$wp->request = '/wp-json/wc/v3/products';
		$update_last_access->invoke( $wc_rest_authentication, new WP_REST_Request( 'GET', '/wc/v3/products' ) );
		$this->assertTrue(
			$last_access_updated,
			'If a REST API request is received over HTTP, then by default the corresponding last_access time should be updated.'
		);

		// Clean-up.
		$wp->request = $original_request;
		$authenticated_user->setValue( $wc_rest_authentication, $original_authenticated_user );
		$authenticated_user->setAccessible( false );
		$update_last_access->setAccessible( false );
		add_filter( 'woocommerce_disable_rest_api_access_log', $last_access_spy );
	}
}
