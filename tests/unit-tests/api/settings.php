<?php
namespace WooCommerce\Tests\API;

/**
 * Settings API Tests
 * @package WooCommerce\Tests\API
 * @since 2.7.0
 */
class Settings extends \WP_Test_REST_Controller_Testcase {

	public function setUp() {
		parent::setUp();
		$this->endpoint = new \WC_Rest_Settings_Controller();
	}

	/**
	 * Test route registration.
	 * @since 2.7.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v1/settings/locations', $routes );
	}

	/**
	 * Test getting all locations.
	 * @since 2.7.0
	 */
	public function test_get_locations() {

	}

	/**
	 * Test /settings/locations without valid permissions/creds.
	 * @since 2.7.0
	 */
	public function test_get_locations_without_permission() {

	}

	/**
	 * Test /settings/locations correctly filters out bad values.
	 * Handles required fields and bogus fields.
	 * @since 2.7.0
	 */
	public function test_get_locations_filters_values() {

	}

	/**
	 * Test /settings/locations with type.
	 * @since 2.7.0
	 */
	public function test_get_locations_with_type() {

	}

	/**
	 * Test /settings/locations schema.
	 * @since 2.7.0
	 */
	public function test_get_item_schema() {

	}

	/**
	 * Test getting a single location item.
	 * @since 2.7.0
	 */
	public function test_get_location() {

	}

	/**
	 * Test getting a single location item.
	 * @since 2.7.0
	 */
	public function test_get_location_without_permission() {

	}

	public function test_get_items() { }
	public function test_get_item() { }
	public function test_context_param() { }
	public function test_create_item() { }
	public function test_update_item() { }
	public function test_delete_item() { }
	public function test_prepare_item() { }

}
