<?php
declare( strict_types = 1 );

/**
 * Tests for the WooCommerce.com settings WC_Helper_Admin adds to wcSettings.
 */
class WC_Helper_Admin_Test extends WC_Unit_Test_Case {

	private const ACCESS_TOKEN = 'test-access-token';

	/**
	 * @testdox Should build the full marketplace settings only for users who can manage WooCommerce.
	 *
	 * @testWith ["contributor", false, false]
	 *           ["shop_manager", true, false]
	 *           ["administrator", true, true]
	 *
	 * @param string $role                 Role of the current user.
	 * @param bool   $gets_connection      Whether the user should see the connection details.
	 * @param bool   $gets_install_url     Whether the user should get the install link.
	 */
	public function test_add_marketplace_settings_by_capability( string $role, bool $gets_connection, bool $gets_install_url ): void {
		WC_Helper_Options::update(
			'auth',
			array(
				'access_token'        => self::ACCESS_TOKEN,
				'access_token_secret' => 'test-access-token-secret',
				'site_id'             => 1,
			)
		);
		WC_Helper_Options::update( 'auth_user_data', array( 'email' => 'owner@example.org' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );

		$settings = WC_Helper_Admin::add_marketplace_settings( array() )['wccomHelper'];

		$this->assertSame( $gets_connection, $settings['isConnected'] );
		$this->assertSame( $gets_connection, str_contains( wp_json_encode( $settings ), 'owner@example.org' ) );
		$this->assertSame( $gets_install_url, str_contains( wp_json_encode( $settings ), self::ACCESS_TOKEN ) );
		$this->assertIsArray( $settings['installedProducts'] );
	}

	/**
	 * @testdox Should give users who can't manage WooCommerce only the minimal marketplace settings.
	 */
	public function test_add_marketplace_settings_minimal_keys(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );

		$settings = WC_Helper_Admin::add_marketplace_settings( array() )['wccomHelper'];

		$this->assertSameSets(
			array( 'isConnected', 'storeCountry', 'installedProducts', 'wooUpdateManagerInstalled', 'wooUpdateManagerActive', 'trackingAllowed' ),
			array_keys( $settings )
		);
	}
}
