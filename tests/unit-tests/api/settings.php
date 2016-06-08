<?php
namespace WooCommerce\Tests\API;

/**
 * Settings API Tests
 * @package WooCommerce\Tests\API
 * @since 2.7.0
 */
class Settings extends \WC_Unit_Test_Case {

	protected $server;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_Test_Spy_REST_Server;
		do_action( 'rest_api_init' );
		$this->endpoint = new \WC_Rest_Settings_Controller();
		\WC_Helper_Settings::register();
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Unset the server.
	 */
	public function tearDown() {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Test route registration.
	 * @since 2.7.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v1/settings', $routes );
		$this->assertArrayHasKey( '/wc/v1/settings/(?P<group>[\w-]+)', $routes );
		$this->assertArrayHasKey( '/wc/v1/settings/(?P<group>[\w-]+)/(?P<id>[\w-]+)', $routes );
	}

	/**
	 * Test getting all groups.
	 * @since 2.7.0
	 */
	public function test_get_groups() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertContains( array(
			'id'          => 'test',
			'label'       => 'Test Extension',
			'parent_id'   => '',
			'description' => 'My awesome test settings.',
			'sub_groups'  => array( 'sub-test' ),
			'_links'      => array(
				'item' => array(
					array(
						'href'       => rest_url( '/wc/v1/settings/test' ),
						'embeddable' => true,
					),
				),
			),
		), $data );

		$this->assertContains( array(
			'id'          => 'sub-test',
			'label'       => 'Sub test',
			'parent_id'   => 'test',
			'description' => '',
			'sub_groups'  => array(),
			'_links'      => array(
				'item' => array(
					array(
						'href'       => rest_url( '/wc/v1/settings/sub-test' ),
						'embeddable' => true,
					),
				),
			),
		), $data );
	}

	/**
	 * Test /settings without valid permissions/creds.
	 * @since 2.7.0
	 */
	public function test_get_groups_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test groups schema.
	 * @since 2.7.0
	 */
	public function test_get_group_schema() {
		$request = new \WP_REST_Request( 'OPTIONS', '/wc/v1/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 5, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'parent_id', $properties );
		$this->assertArrayHasKey( 'label', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'sub_groups', $properties );
	}

	/**
	 * Test settings schema.
	 * @since 2.7.0
	 */
	public function test_get_setting_schema() {
		$request = new \WP_REST_Request( 'OPTIONS', '/wc/v1/settings/test/woocommerce_shop_page_display' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 8, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'label', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'default', $properties );
		$this->assertArrayHasKey( 'tip', $properties );
		$this->assertArrayHasKey( 'placeholder', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'options', $properties );
	}

	/**
	 * Test getting a single group.
	 * @since 2.7.0
	 */
	public function test_get_group() {
		wp_set_current_user( $this->user );

		// test getting a group that does not exist
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/not-real' ) );
		$data = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting the 'invalid' group
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/invalid' ) );
		$data = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting a valid group
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/general' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertContains( array(
    		'id' => 'woocommerce_demo_store',
			'label' => 'Store Notice',
			'description' => 'Enable site-wide store notice text',
			'type' => 'checkbox',
			'default' => 'no',
			'value' => 'no',
			'_links' => array(
				'self' => array(
					array(
						'href' => rest_url( '/wc/v1/settings/general/woocommerce_demo_store' ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v1/settings/general' ),
					),
				),
			)
		), $data );

		// test getting a valid group with settings attached to it
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/test' ) );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		$this->assertEquals( 'woocommerce_shop_page_display', $data[0]['id'] );
		$this->assertEmpty( $data[0]['value'] );
		$this->assertEquals( 'woocommerce_enable_lightbox', $data[1]['id'] );
		$this->assertEquals( 'yes', $data[1]['value'] );
	}

	/**
	 * Test getting a single group without permission.
	 * @since 2.7.0
	 */
	public function test_get_group_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/coupon-data' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a single setting.
	 * @since 2.7.0
	 */
	public function test_update_setting() {
		wp_set_current_user( $this->user );

		// test defaults first
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/test/woocommerce_shop_page_display' ) );
		$data = $response->get_data();
		$this->assertEquals( '', $data['value'] );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/test/woocommerce_enable_lightbox' ) );
		$data = $response->get_data();
		$this->assertEquals( 'yes', $data['value'] );

		// test updating shop display setting
		$request = new \WP_REST_Request( 'PUT', sprintf( '/wc/v1/settings/%s/%s', 'test', 'woocommerce_shop_page_display' ) );
		$request->set_body_params( array(
			'value' => 'both',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 'both', $data['value'] );
		$this->assertEquals( 'both', get_option( 'woocommerce_shop_page_display' ) );

		$request = new \WP_REST_Request( 'PUT', sprintf( '/wc/v1/settings/%s/%s', 'test', 'woocommerce_shop_page_display' ) );
		$request->set_body_params( array(
			'value' => 'subcategories',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 'subcategories', $data['value'] );
		$this->assertEquals( 'subcategories', get_option( 'woocommerce_shop_page_display' ) );

		$request = new \WP_REST_Request( 'PUT', sprintf( '/wc/v1/settings/%s/%s', 'test', 'woocommerce_shop_page_display' ) );
		$request->set_body_params( array(
			'value' => '',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( '', $data['value'] );
		$this->assertEquals( '', get_option( 'woocommerce_shop_page_display' ) );

		// test updating ligtbox
		$request = new \WP_REST_Request( 'PUT', sprintf( '/wc/v1/settings/%s/%s', 'test', 'woocommerce_enable_lightbox' ) );
		$request->set_body_params( array(
			'value' => 'no',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 'no', $data['value'] );
		$this->assertEquals( 'no', get_option( 'woocommerce_enable_lightbox' ) );

		$request = new \WP_REST_Request( 'PUT', sprintf( '/wc/v1/settings/%s/%s', 'test', 'woocommerce_enable_lightbox' ) );
		$request->set_body_params( array(
			'value' => 'yes',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 'yes', $data['value'] );
		$this->assertEquals( 'yes', get_option( 'woocommerce_enable_lightbox' ) );
	}

	/**
	 * Test updating multiple settings at once.
	 * @since 2.7.0
	 */
	public function test_update_settings() {
		wp_set_current_user( $this->user );

		// test defaults first
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/test' ) );
		$data = $response->get_data();
		$this->assertEquals( '', $data[0]['value'] );
		$this->assertEquals( 'yes', $data[1]['value'] );

		// test setting both at once
		$request = new \WP_REST_Request( 'POST', '/wc/v1/settings/test/batch' );
		$request->set_body_params( array(
			'update' => array(
				array(
				'id'    => 'woocommerce_shop_page_display',
				'value' => 'both',
				),
				array(
					'id'    => 'woocommerce_enable_lightbox',
					'value' => 'no',
				),
			),
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'both', $data['update'][0]['value'] );
		$this->assertEquals( 'both', get_option( 'woocommerce_shop_page_display' ) );
		$this->assertEquals( 'no', $data['update'][1]['value'] );
		$this->assertEquals( 'no', get_option( 'woocommerce_enable_lightbox' ) );

		// test updating one, but making sure the other value stays the same
		$request = new \WP_REST_Request( 'POST', '/wc/v1/settings/test/batch' );
		$request->set_body_params( array(
			'update' => array(
				array(
					'id'    => 'woocommerce_shop_page_display',
					'value' => 'subcategories',
				),
			),
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'subcategories', $data['update'][0]['value'] );
		$this->assertEquals( 'subcategories', get_option( 'woocommerce_shop_page_display' ) );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/test' ) );
		$data = $response->get_data();
		$this->assertEquals( 'no', $data[1]['value'] );
		$this->assertEquals( 'no', get_option( 'woocommerce_enable_lightbox' ) );
	}

	/**
	 * Test getting a single setting.
	 * @since 2.7.0
	 */
	public function test_get_setting() {
		wp_set_current_user( $this->user );

		// test getting an invalid setting from a group that does not exist
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/not-real/woocommerce_enable_lightbox' ) );
		$data = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting an invalid setting from a group that does exist
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/invalid/invalid' ) );
		$data = $response->get_data();
		$this->assertEquals( 404, $response->get_status() );

		// test getting a valid setting
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/test/woocommerce_enable_lightbox' ) );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 'woocommerce_enable_lightbox', $data['id'] );
		$this->assertEquals( 'Product Image Gallery', $data['label'] );
		$this->assertEquals( 'yes', $data['default'] );
		$this->assertEquals( 'Product gallery images will open in a lightbox.', $data['tip'] );
		$this->assertEquals( 'checkbox', $data['type'] );
		$this->assertEquals( 'yes', $data['value'] );
	}

	/**
	 * Test getting a single setting without valid user permissions.
	 * @since 2.7.0
	 */
	public function test_get_setting_without_permission() {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/test/woocommerce_enable_lightbox' ) );
		$this->assertEquals( 401, $response->get_status() );
	}


	/**
	 * Test updating a single setting without valid user permissions.
	 * @since 2.7.0
	 */
	public function test_update_setting_without_permission() {
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'PUT', sprintf( '/wc/v1/settings/%s/%s', 'test', 'woocommerce_enable_lightbox' ) );
		$request->set_body_params( array(
			'value' => 'yes',
		) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}


	/**
	 * Test updating multiple settings without valid user permissions.
	 * @since 2.7.0
	 */
	public function test_update_settings_without_permission() {
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'POST', '/wc/v1/settings/test/batch' );
		$request->set_body_params( array(
			'update' => array(
				array(
					'id'    => 'woocommerce_shop_page_display',
					'value' => 'subcategories',
				),
			),
		) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Makes sure our sanitize function runs correctly for different types.
	 * @since 2.7.0
	 */
	public function test_sanitize_setting() {
		$endpoint = new \WC_Rest_Settings_Controller;

		// checkbox
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'checkbox', 'default' => 'yes' ), 'no' );
		$this->assertEquals( 'no', $value );
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'checkbox', 'default' => 'yes' ), 'yes' );
		$this->assertEquals( 'yes', $value );
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'checkbox', 'default' => 'yes' ), 'invalid' );
		$this->assertEquals( 'yes', $value );

		// email
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'email' ), 'test@woo.local' );
		$this->assertEquals( 'test@woo.local', $value );
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'email' ), '     admin@woo.local!     ' );
		$this->assertEquals( 'admin@woo.local', $value );
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'email' ), 'blah' );
		$this->assertEquals( '', $value );
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'email', 'default' => 'woo@woo.local' ), 'blah' );
		$this->assertEquals( 'woo@woo.local', $value );

		// textarea
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'textarea' ), ' <strong>blah</strong>' );
		$this->assertEquals( '<strong>blah</strong>', $value );
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'textarea' ), '<script></script><strong>blah</strong>' );
		$this->assertEquals( '<strong>blah</strong>', $value );

		// multiselect / multiselect countries
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'multiselect' ), array( 'test', '<test ' ) );
		$this->assertEquals( array( 'test', '&lt;test' ), $value );
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'multi_select_countries' ), array( 'test', '<test ' ) );
		$this->assertEquals( array( 'test', '&lt;test' ), $value );

		// image_width
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'image_width' ), array( 'width' => ' 100%', 'height' => '25px ' ) );
		$this->assertEquals( array( 'width' => '100%', 'height' => '25px', 'crop' => 0 ), $value );
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'image_width' ), array( 'width' => '100%', 'height' => '25px', 'crop' => 'something' ) );
		$this->assertEquals( array( 'width' => '100%', 'height' => '25px', 'crop' => 1 ), $value );
		$value = $endpoint->sanitize_setting_value( array( 'id' => 'test', 'type' => 'image_width', 'default' => array( 'width' => '50px', 'height' => '50px', 'crop' => true ) ), array() );
		$this->assertEquals( array( 'width' => '50px', 'height' => '50px', 'crop' => 1 ), $value );
	}

	/**
	* Tests our classic setting registration to make sure settings added for WP-Admin are available over the API.
	* @since  2.7.0
	*/
	public function test_classic_settings() {
		wp_set_current_user( $this->user );

		// Make sure the group is properly registered
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/products' ) );
		$data = $response->get_data();
		$this->assertTrue( is_array( $data ) );
		$this->assertContains( array(
			'id'          => 'woocommerce_downloads_require_login',
			'label'       => 'Access Restriction',
			'description' => 'Downloads require login',
			'type'        => 'checkbox',
			'default'     => 'no',
			'tip'         => 'This setting does not apply to guest purchases.',
			'value'       => 'no',
			'_links'      => array(
				'self' => array(
					array(
						'href' => rest_url( '/wc/v1/settings/products/woocommerce_downloads_require_login' ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v1/settings/products' ),
					),
				),
			),
		), $data );

		// test get single
		$response = $this->server->dispatch( new \WP_REST_Request( 'GET', '/wc/v1/settings/products/woocommerce_dimension_unit' ) );
		$data = $response->get_data();

		$this->assertEquals( 'cm', $data['default'] );

		// test update
		$request = new \WP_REST_Request( 'PUT', sprintf( '/wc/v1/settings/%s/%s', 'products', 'woocommerce_dimension_unit' ) );
		$request->set_body_params( array(
			'value' => 'yd',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 'yd', $data['value'] );
		$this->assertEquals( 'yd', get_option(' woocommerce_dimension_unit' ) );
	}

}
