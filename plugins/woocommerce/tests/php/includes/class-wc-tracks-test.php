<?php
declare(strict_types=1);

/**
 * Class WC_Tracks_Test.
 */
class WC_Tracks_Test extends \WC_Unit_Test_Case {

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks.php';
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-client.php';
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-event.php';
	}

	/**
	 * Test that custom event properties are returned when passed.
	 */
	public function test_get_properties() {
		$properties = \WC_Tracks::get_properties(
			'test_event',
			array(
				'test_property' => 5,
			)
		);
		$this->assertContains( 'test_property', array_keys( $properties ) );
		$this->assertEquals( 5, $properties['test_property'] );
	}


	/**
	 * Test that identity properties are added to the properties.
	 */
	public function test_addition_of_identity() {
		$properties = \WC_Tracks::get_properties(
			'test_event',
			array(
				'test_property' => 5,
			)
		);
		$this->assertContains( '_ui', array_keys( $properties ) );
		$this->assertContains( '_ut', array_keys( $properties ) );
	}

	/**
	 * Test that custom identity properties cannot be added.
	 */
	public function test_invalid_identity() {
		$properties = \WC_Tracks::get_properties(
			'test_event',
			array(
				'_ui' => 'bad',
				'_ut' => 'bad',
			)
		);
		$this->assertNotEquals( 'bad', $properties['_ui'] );
		$this->assertNotEquals( 'bad', $properties['_ut'] );
	}


	/**
	 * Test role properties for logged out user
	 */
	public function test_role_properties_for_logged_out_user() {
		$properties = \WC_Tracks::get_properties( 'test_event', array() );

		$this->assertContains( 'role', array_keys( $properties ) );
		$this->assertEquals( '', $properties['role'] );
		$this->assertEquals( false, $properties['can_install_plugins'] );
		$this->assertEquals( false, $properties['can_activate_plugins'] );
		$this->assertEquals( false, $properties['can_manage_woocommerce'] );
	}

	/**
	 * Test role properties for administrator
	 */
	public function test_role_properties_for_administrator() {
		$user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );

		$properties = \WC_Tracks::get_properties( 'test_event', array() );

		$this->assertEquals( 'administrator', $properties['role'] );
		$this->assertEquals( true, $properties['can_install_plugins'] );
		$this->assertEquals( true, $properties['can_activate_plugins'] );
		$this->assertEquals( true, $properties['can_manage_woocommerce'] );
	}

	/**
	 * Test role properties for user with multiple roles
	 */
	public function test_role_properties_for_multiple_roles() {
		$user = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $user );
		$current_user = wp_get_current_user();
		$current_user->add_role( 'editor' );

		$properties = \WC_Tracks::get_properties( 'test_event', array() );

		$this->assertEquals( 'shop_manager', $properties['role'] );
		$this->assertEquals( false, $properties['can_install_plugins'] );
		$this->assertEquals( false, $properties['can_activate_plugins'] );
		$this->assertEquals( true, $properties['can_manage_woocommerce'] );
	}

	/**
	 * Test role properties for non-sequential roles.
	 */
	public function test_role_properties_for_non_sequential_roles() {
		$user = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $user );
		$current_user = wp_get_current_user();
		$current_user->add_role( 'administrator' );
		// Mock the roles to be an associative array to simulate the scenario where the roles are not sequential.
		$current_user->roles = array(
			2 => 'administrator',
		);
		$properties          = \WC_Tracks::get_role_details( $current_user );
		$this->assertEquals( 'administrator', $properties['role'] );
		$this->assertEquals( true, $properties['can_install_plugins'] );
		$this->assertEquals( true, $properties['can_activate_plugins'] );
		$this->assertEquals( true, $properties['can_manage_woocommerce'] );
	}

	/**
	 * Test the event validation and sanitization with a valid event.
	 */
	public function test_event_validation_and_sanitization_valid_event() {
		$event_props = array(
			'_en'            => 'valid_event_name',
			'_ts'            => WC_Tracks_Client::build_timestamp(),
			'valid_property' => 'My value',
			'_via_ip'        => '192.168.10.1',
		);

		// Valid event and property names.
		$event = \WC_Tracks_Event::validate_and_sanitize( $event_props );
		$this->assertTrue( property_exists( $event, 'browser_type' ) );
		$this->assertTrue( property_exists( $event, '_ts' ) );
		$this->assertTrue( property_exists( $event, 'valid_property' ) );
		$this->assertFalse( property_exists( $event, '_via_ip' ) );
	}

	/**
	 * Test the event validation and sanitization with an invalid event.
	 */
	public function test_event_validation_and_sanitization_invalid_event_name() {
		$event_props = array(
			'_en'            => 'valid_event_name',
			'_ts'            => WC_Tracks_Client::build_timestamp(),
			'valid_property' => 'My value',
			'_via_ip'        => '192.168.10.1',
		);

		// Invalid event name.
		$event = \WC_Tracks_Event::validate_and_sanitize(
			array_merge(
				$event_props,
				array( '_en' => 'invalidName' )
			)
		);
		$this->assertTrue( is_wp_error( $event ) );
		$this->assertEquals( $event->get_error_code(), 'invalid_event_name' );

		$event = \WC_Tracks_Event::validate_and_sanitize(
			array_merge(
				$event_props,
				array( '_en' => 'invalid-name' )
			)
		);
		$this->assertTrue( is_wp_error( $event ) );
		$this->assertEquals( $event->get_error_code(), 'invalid_event_name' );

		// Invalid property name.
		$event = \WC_Tracks_Event::validate_and_sanitize(
			array_merge(
				$event_props,
				array( 'invalid-property-name' => 'My value' )
			)
		);
		$this->assertTrue( is_wp_error( $event ) );
		$this->assertEquals( $event->get_error_code(), 'invalid_prop_name' );

		$event = \WC_Tracks_Event::validate_and_sanitize(
			array_merge(
				$event_props,
				array( 'invalid property name' => 'my-value' )
			)
		);
		$this->assertTrue( is_wp_error( $event ) );
		$this->assertEquals( $event->get_error_code(), 'invalid_prop_name' );
	}

	/**
	 * Test that the store_id is added to the properties.
	 */
	public function test_store_id_is_added_to_properties() {
		$store_id   = get_option( \WC_Install::STORE_ID_OPTION, '12345' );
		$properties = \WC_Tracks::get_properties(
			'test_event',
			array(
				'test_property' => 5,
			)
		);
		$this->assertContains( 'store_id', array_keys( $properties ) );
		$this->assertEquals( $store_id, $properties['store_id'] );
		delete_option( \WC_Install::STORE_ID_OPTION );
	}

	/**
	 * Test that get_blog_details ensures the store ID is set.
	 */
	public function test_get_blog_details_ensures_store_id_is_set() {
		// Delete the store ID option to simulate a fresh installation.
		delete_option( \WC_Install::STORE_ID_OPTION );
		delete_transient( 'wc_tracks_blog_details' );

		// Call get_blog_details which should ensure store ID is set.
		$blog_details = \WC_Tracks::get_blog_details( get_current_user_id() );

		// Verify that store_id exists and is not null.
		$this->assertArrayHasKey( 'store_id', $blog_details );
		$this->assertNotNull( $blog_details['store_id'] );

		// Verify that the store ID option was actually set in the database.
		$store_id = get_option( \WC_Install::STORE_ID_OPTION );
		$this->assertNotEmpty( $store_id );
		$this->assertEquals( $store_id, $blog_details['store_id'] );
	}

	/**
	 * Test that get_blog_details uses cached data from transients.
	 */
	public function test_get_blog_details_uses_cached_data() {
		// Delete existing transient to start fresh.
		delete_transient( 'wc_tracks_blog_details' );

		// Set a known store ID.
		$test_store_id = 'test_store_id_' . uniqid();
		update_option( \WC_Install::STORE_ID_OPTION, $test_store_id );

		// First call should set the transient.
		$first_call = \WC_Tracks::get_blog_details( get_current_user_id() );
		$this->assertEquals( $test_store_id, $first_call['store_id'] );

		// Change the store ID in the database.
		$new_store_id = 'new_store_id_' . uniqid();
		update_option( \WC_Install::STORE_ID_OPTION, $new_store_id );

		// Second call should use the cached data and not reflect the new store ID.
		$second_call = \WC_Tracks::get_blog_details( get_current_user_id() );
		$this->assertEquals( $test_store_id, $second_call['store_id'] );
		$this->assertNotEquals( $new_store_id, $second_call['store_id'] );

		// Delete the transient.
		delete_transient( 'wc_tracks_blog_details' );

		// Third call should get fresh data with the new store ID.
		$third_call = \WC_Tracks::get_blog_details( get_current_user_id() );
		$this->assertEquals( $new_store_id, $third_call['store_id'] );

		// Clean up.
		delete_option( \WC_Install::STORE_ID_OPTION );
	}

	/**
	 * Test that indexed arrays are converted to comma-separated strings.
	 */
	public function test_array_property_indexed_array() {
		$event_props = array(
			'_en'         => 'test_event',
			'_ts'         => WC_Tracks_Client::build_timestamp(),
			'blocks'      => array( 'block-a', 'block-b', 'block-c' ),
			'simple_prop' => 'value',
		);

		$event = new \WC_Tracks_Event( $event_props );
		$url   = $event->build_pixel_url();

		// Verify URL was built successfully.
		$this->assertNotEmpty( $url );
		$this->assertStringContainsString( 'block-a%2Cblock-b%2Cblock-c', $url );

		// Parse the query string.
		$parsed_url = wp_parse_url( $url );
		parse_str( $parsed_url['query'], $query_params );
		// Verify no bracket notation in URL (parse_str would create array values if brackets were present).
		foreach ( $query_params as $key => $value ) {
			$this->assertIsNotArray( $value, "Property '$key' should not be an array after parse_str (indicates bracket notation in URL)" );
		}
	}

	/**
	 * Test that associative arrays are converted to JSON strings.
	 */
	public function test_array_property_associative_array() {
		$event_props = array(
			'_en'     => 'test_event',
			'_ts'     => WC_Tracks_Client::build_timestamp(),
			'options' => array(
				'enabled' => true,
				'count'   => 5,
				'name'    => 'test',
			),
		);

		$event = new \WC_Tracks_Event( $event_props );
		$url   = $event->build_pixel_url();

		// Verify URL was built successfully.
		$this->assertNotEmpty( $url );

		// Parse the query string.
		$parsed_url = wp_parse_url( $url );
		$this->assertStringContainsString( 'options=%7B%22enabled%22%3Atrue%2C%22count%22%3A5%2C%22name%22%3A%22test%22%7D', $url );

		parse_str( $parsed_url['query'], $query_params );
		// Verify no bracket notation in URL (parse_str would create array values if brackets were present).
		foreach ( $query_params as $key => $value ) {
			$this->assertIsNotArray( $value, "Property '$key' should not be an array after parse_str (indicates bracket notation in URL)" );
		}
	}

	/**
	 * Test that empty arrays are converted to empty strings.
	 */
	public function test_array_property_empty_array() {
		$event_props = array(
			'_en'        => 'test_event',
			'_ts'        => WC_Tracks_Client::build_timestamp(),
			'empty_arr'  => array(),
			'other_prop' => 'value',
		);

		$event = new \WC_Tracks_Event( $event_props );
		$url   = $event->build_pixel_url();

		// Verify URL was built successfully.
		$this->assertNotEmpty( $url );

		// Parse the query string.
		$parsed_url = wp_parse_url( $url );
		parse_str( $parsed_url['query'], $query_params );

		// Verify empty array became empty string.
		$this->assertArrayHasKey( 'empty_arr', $query_params );
		$this->assertEquals( '', $query_params['empty_arr'] );
	}

	/**
	 * Test that nested arrays (indexed with associative) are handled.
	 */
	public function test_array_property_nested_array() {
		$event_props = array(
			'_en'   => 'test_event',
			'_ts'   => WC_Tracks_Client::build_timestamp(),
			'items' => array(
				array(
					'id'   => 1,
					'name' => 'Item 1',
				),
				array(
					'id'   => 2,
					'name' => 'Item 2',
				),
			),
		);

		$event = new \WC_Tracks_Event( $event_props );
		$url   = $event->build_pixel_url();

		// Verify URL was built successfully.
		$this->assertNotEmpty( $url );
		$this->assertStringContainsString( 'items=%5B%7B%22id%22%3A1%2C%22name%22%3A%22Item+1%22%7D%2C%7B%22id%22%3A2%2C%22name%22%3A%22Item+2%22%7D%5D', $url );

		// Parse the query string.
		$parsed_url = wp_parse_url( $url );
		parse_str( $parsed_url['query'], $query_params );
		// Verify no bracket notation in URL (parse_str would create array values if brackets were present).
		foreach ( $query_params as $key => $value ) {
			$this->assertIsNotArray( $value, "Property '$key' should not be an array after parse_str (indicates bracket notation in URL)" );
		}
	}

	/**
	 * Test array with values containing commas.
	 */
	public function test_array_property_with_commas() {
		$event_props = array(
			'_en'  => 'test_event',
			'_ts'  => WC_Tracks_Client::build_timestamp(),
			'tags' => array( 'tag1', 'tag,with,commas', 'tag3' ),
		);

		$event = new \WC_Tracks_Event( $event_props );
		$url   = $event->build_pixel_url();

		// Verify URL was built successfully.
		$this->assertNotEmpty( $url );
		$this->assertStringContainsString( 'tags=tag1%2Ctag%2Cwith%2Ccommas%2Ctag3', $url );

		// Parse the query string.
		$parsed_url = wp_parse_url( $url );
		parse_str( $parsed_url['query'], $query_params );
		// Verify no bracket notation in URL (parse_str would create array values if brackets were present).
		foreach ( $query_params as $key => $value ) {
			$this->assertIsNotArray( $value, "Property '$key' should not be an array after parse_str (indicates bracket notation in URL)" );
		}
	}

	/**
	 * Test that all property names remain valid after array conversion.
	 */
	public function test_array_property_names_remain_valid() {
		$event_props = array(
			'_en'                  => 'test_event',
			'_ts'                  => WC_Tracks_Client::build_timestamp(),
			'simple_indexed_array' => array( 'a', 'b', 'c' ),
			'complex_associative'  => array( 'key' => 'value' ),
			'empty_array'          => array(),
		);

		$event = new \WC_Tracks_Event( $event_props );
		$url   = $event->build_pixel_url();

		// Verify URL was built successfully.
		$this->assertNotEmpty( $url );

		// Parse the query string directly (without parse_str to avoid bracket notation conversion).
		$parsed_url   = wp_parse_url( $url );
		$query_string = $parsed_url['query'];

		// Extract all parameter names from the query string.
		preg_match_all( '/([^=&]+)=/', $query_string, $matches );
		$param_names = $matches[1];

		// Verify all property names are valid (match regex).
		foreach ( $param_names as $param_name ) {
			// URL decode the parameter name to get the actual property name.
			$decoded_name = urldecode( $param_name );
			$this->assertMatchesRegularExpression(
				'/^[a-z_][a-z0-9_]*$/',
				$decoded_name,
				"Property name '$decoded_name' should match the valid naming pattern"
			);
		}
	}

	/**
	 * Test multiple array properties in the same event.
	 */
	public function test_multiple_array_properties() {
		$event_props = array(
			'_en'      => 'test_event',
			'_ts'      => WC_Tracks_Client::build_timestamp(),
			'blocks'   => array( 'block-a', 'block-b' ),
			'settings' => array( 'enabled' => true ),
			'empty'    => array(),
			'normal'   => 'regular_value',
		);

		$event = new \WC_Tracks_Event( $event_props );
		$url   = $event->build_pixel_url();

		// Verify URL was built successfully.
		$this->assertNotEmpty( $url );
		$this->assertStringContainsString( 'blocks=block-a%2Cblock-b', $url );
		$this->assertStringContainsString( 'settings=%7B%22enabled%22%3Atrue%7D', $url );
		$this->assertStringContainsString( 'empty=', $url );
		$this->assertStringContainsString( 'normal=regular_value', $url );

		// Parse the query string.
		$parsed_url = wp_parse_url( $url );
		parse_str( $parsed_url['query'], $query_params );
		// Verify no bracket notation in URL (parse_str would create array values if brackets were present).
		foreach ( $query_params as $key => $value ) {
			$this->assertIsNotArray( $value, "Property '$key' should not be an array after parse_str (indicates bracket notation in URL)" );
		}
	}
}
