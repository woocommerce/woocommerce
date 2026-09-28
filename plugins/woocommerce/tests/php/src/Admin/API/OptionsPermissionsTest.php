<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API;

use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests permissions for the REST API options controller.
 */
class OptionsPermissionsTest extends WC_REST_Unit_Test_Case {

	private const UNLISTED_OPTION = 'unlisted_option';

	/**
	 * @testdox Administrators cannot access an unlisted option.
	 * @testWith [false, false, "Administrator"]
	 *           [true, false, "subsite Administrator"]
	 *           [true, true, "Super Admin"]
	 *
	 * @param bool   $requires_multisite Whether the test requires Multisite.
	 * @param bool   $grant_super_admin Whether to grant Super Admin privileges.
	 * @param string $user_description Description of the tested user.
	 */
	public function test_administrator_cannot_access_unlisted_option( bool $requires_multisite, bool $grant_super_admin, string $user_description ): void {
		if ( $requires_multisite ) {
			$this->skipWithoutMultisite();
		} elseif ( is_multisite() ) {
			$this->markTestSkipped( 'This test requires a single-site installation.' );
		}

		$user_id = $this->login_as_administrator();
		if ( $grant_super_admin ) {
			grant_super_admin( $user_id );
		}
		update_option( self::UNLISTED_OPTION, 'original' );

		if ( $requires_multisite && ! $grant_super_admin ) {
			$this->assertFalse( is_super_admin( $user_id ), 'The test user should not be a Super Admin.' );
			$this->assertTrue( current_user_can( 'manage_options' ), 'A subsite Administrator should have manage_options.' );
			$this->assertFalse( current_user_can( 'manage_network_options' ), 'A subsite Administrator should not have manage_network_options.' );
		}
		$this->expect_unlisted_option_deprecations();

		$read_response = $this->get_options( array( self::UNLISTED_OPTION ) );
		$this->assertSame( 403, $read_response->get_status(), "A {$user_description} should not be able to read an unlisted option." );

		$write_response = $this->post_options( array( self::UNLISTED_OPTION => 'changed' ) );
		$this->assertSame( 403, $write_response->get_status(), "A {$user_description} should not be able to update an unlisted option." );
		$this->assertSame( 'original', get_option( self::UNLISTED_OPTION ), 'The denied update should not change the option.' );
	}

	/**
	 * @testdox An eligible Administrator retains access to allowlisted options.
	 * @dataProvider allowlisted_option_provider
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $original_value Original option value.
	 * @param mixed  $new_value New option value.
	 */
	public function test_administrator_can_access_allowlisted_option( string $option_name, $original_value, $new_value ): void {
		$this->login_as_administrator();
		update_option( $option_name, $original_value );

		$read_response = $this->get_options( array( $option_name ) );
		$this->assertSame( 200, $read_response->get_status(), "{$option_name} should retain allowlisted read access." );
		$this->assertSame( $original_value, $read_response->get_data()[ $option_name ], "{$option_name} should return its stored value." );

		$write_response = $this->post_options( array( $option_name => $new_value ) );
		$this->assertSame( 200, $write_response->get_status(), "{$option_name} should retain allowlisted write access." );
		$this->assertSame( $new_value, get_option( $option_name ), "{$option_name} should be updated." );
	}

	/**
	 * Data provider for allowlisted options.
	 *
	 * @return array<string, array{string, mixed, mixed}>
	 */
	public function allowlisted_option_provider(): array {
		return array(
			'existing option'      => array( 'woocommerce_allow_tracking', 'no', 'yes' ),
			'shipping options'     => array( 'wcshipping_options', array( 'tos_accepted' => false ), array( 'tos_accepted' => true ) ),
			'mobile app dismissal' => array( 'woocommerce_admin_dismissed_mobile_app_modal', 'no', 'yes' ),
		);
	}

	/**
	 * @testdox The legacy filter can explicitly grant option access.
	 */
	public function test_filter_can_grant_option_access(): void {
		$this->login_as_administrator();
		update_option( self::UNLISTED_OPTION, 'original' );

		$grant_permission = static function ( array $permissions ): array {
			$permissions[ self::UNLISTED_OPTION ] = true;
			return $permissions;
		};
		add_filter( 'woocommerce_rest_api_option_permissions', $grant_permission );
		$this->setExpectedDeprecated( 'woocommerce_rest_api_option_permissions' );

		try {
			$read_response = $this->get_options( array( self::UNLISTED_OPTION ) );
			$this->assertSame( 200, $read_response->get_status(), 'A filtered option should be readable.' );
			$this->assertSame( 'original', $read_response->get_data()[ self::UNLISTED_OPTION ], 'The filtered option should return its stored value.' );

			$write_response = $this->post_options( array( self::UNLISTED_OPTION => 'changed' ) );
			$this->assertSame( 200, $write_response->get_status(), 'A filtered option should be writable.' );
			$this->assertSame( 'changed', get_option( self::UNLISTED_OPTION ), 'The filtered option should be updated.' );
		} finally {
			remove_filter( 'woocommerce_rest_api_option_permissions', $grant_permission );
		}
	}

	/**
	 * @testdox Core options cannot be accessed through the legacy endpoint.
	 * @dataProvider core_option_provider
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $original_value Original option value.
	 * @param mixed  $new_value New option value.
	 */
	public function test_core_options_cannot_be_accessed( string $option_name, $original_value, $new_value ): void {
		$this->login_as_administrator();
		update_option( $option_name, $original_value );
		$this->expect_unlisted_option_deprecations();

		$read_response = $this->get_options( array( $option_name ) );
		$this->assertSame( 403, $read_response->get_status(), "{$option_name} should not be readable." );

		$write_response = $this->post_options( array( $option_name => $new_value ) );
		$this->assertSame( 403, $write_response->get_status(), "{$option_name} should not be writable." );
		$this->assertSame( $original_value, get_option( $option_name ), "{$option_name} should remain unchanged." );
	}

	/**
	 * Data provider for core options.
	 *
	 * @return array<string, array{string, mixed, mixed}>
	 */
	public function core_option_provider(): array {
		return array(
			'blog name'   => array( 'blogname', 'Original store', 'Changed store' ),
			'date format' => array( 'date_format', 'F j, Y', 'Y-m-d' ),
			'time format' => array( 'time_format', 'g:i a', 'H:i' ),
			'stylesheet'  => array( 'stylesheet', 'original-theme', 'changed-theme' ),
			'theme mods'  => array( 'theme_mods_test_theme', array( 'custom_logo' => 123 ), array( 'custom_logo' => 456 ) ),
		);
	}

	/**
	 * Expect the deprecation notices emitted by unlisted option requests.
	 */
	private function expect_unlisted_option_deprecations(): void {
		$this->setExpectedDeprecated( 'Automattic\\WooCommerce\\Admin\\API\\Options::get_options' );
		$this->setExpectedDeprecated( 'Automattic\\WooCommerce\\Admin\\API\\Options::update_options' );
	}

	/**
	 * Send a GET request to the options endpoint.
	 *
	 * @param string[] $options Option names.
	 * @return \WP_REST_Response
	 */
	private function get_options( array $options ) {
		$request = new WP_REST_Request( 'GET', '/wc-admin/options' );
		$request->set_query_params( array( 'options' => implode( ',', $options ) ) );

		return $this->server->dispatch( $request );
	}

	/**
	 * Send a POST request to the options endpoint.
	 *
	 * @param array<string, mixed> $options Option values.
	 * @return \WP_REST_Response
	 */
	private function post_options( array $options ) {
		$request = new WP_REST_Request( 'POST', '/wc-admin/options' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $options ) );

		return $this->server->dispatch( $request );
	}
}
