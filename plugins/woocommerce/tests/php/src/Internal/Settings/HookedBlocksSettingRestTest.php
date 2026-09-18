<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Settings;

use Automattic\WooCommerce\Internal\Settings\HookedBlocksSetting;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the HookedBlocksSetting field through the settings REST API.
 */
class HookedBlocksSettingRestTest extends WC_REST_Unit_Test_Case {

	/**
	 * REST route for the header icons setting.
	 */
	private const ROUTE = '/wc/v3/settings/general/' . HookedBlocksSetting::FIELD_ID;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->initialize_rest_api_routes();
		switch_theme( 'twentytwentytwo' );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		delete_option( HookedBlocksSetting::OPTION_NAME );
	}

	/**
	 * @testdox Should report the real header icons state through the settings REST API.
	 *
	 * @testWith ["8.4.0", "yes"]
	 *           ["no", "no"]
	 *
	 * @param string $stored   Stored hooked blocks option value.
	 * @param string $expected Expected REST value.
	 */
	public function test_rest_get_reports_real_state( string $stored, string $expected ): void {
		update_option( HookedBlocksSetting::OPTION_NAME, $stored );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $expected, $response->get_data()['value'], 'The REST value should reflect the hooked blocks option' );
	}

	/**
	 * @testdox Should write the hooked blocks option when the setting is updated through the REST API.
	 */
	public function test_rest_update_writes_hooked_blocks_option(): void {
		update_option( HookedBlocksSetting::OPTION_NAME, '9.2.0' );
		$request = new WP_REST_Request( 'PUT', self::ROUTE );
		$request->set_body_params( array( 'value' => 'no' ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'no', get_option( HookedBlocksSetting::OPTION_NAME ) );
		$this->assertSame( 'no', $response->get_data()['value'] );
	}
}
