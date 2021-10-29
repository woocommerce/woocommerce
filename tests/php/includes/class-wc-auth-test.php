<?php
/**
 * Class WC_Auth_Test file.
 *
 * @package WooCommerce\Tests\WC_Auth.
 */

/**
 * Class WC_Auth_Test file.
 */
class WC_Auth_Test extends \WC_Unit_Test_Case {

	/**
	 * Test that API keys created via the REST API with long descriptions get saved correctly.
	 * See: https://github.com/woocommerce/woocommerce/issues/30594.
	 */
	public function test_api_key_long_description() {
		$wc_auth        = new WC_Auth();
		$reflected_auth = new ReflectionClass( WC_Auth::class );
		$create_keys    = $reflected_auth->getMethod( 'create_keys' );
		$create_keys->setAccessible( true );

		$app_name     = 'This_app_name_is_very_long_and_meant_to_exceed_the_column_length_of_200_characters_';
		$app_name    .= $app_name;
		$app_user_id  = 1;
		$scope        = 'read_write';

		$key_data = $create_keys->invoke( $wc_auth, $app_name, $app_user_id, $scope );

		// Verify the key was inserted successfully.
		$this->assertNotEquals( 0, $key_data['key_id'], 'API Key with long description was not written to database.' );

		// Clean up.
		$maybe_delete_key = $reflected_auth->getMethod( 'maybe_delete_key' );
		$maybe_delete_key->setAccessible( true );
		$maybe_delete_key->invoke( $wc_auth, $key_data );
	}
}
