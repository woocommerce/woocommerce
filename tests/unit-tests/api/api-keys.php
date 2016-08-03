<?php


/**
 * REST API Keys Functions.
 * @package WooCommerce\Tests\API
 * @since 2.6.0
 * @group api-keys
 */
class WC_Tests_API_API_Keys extends WC_Unit_Test_Case {

	public function test_insert_api_keys() {
		$user_id = $this->factory->user->create();

		$args   = array(
			'user_id' => $user_id,
		);
		$result = WC_Auth::create_api_key( $args );

		$this->assertTrue( is_array( $result ) );
		$this->assertTrue( is_integer( $result['key_id'] ) );
		$this->assertStringStartsWith( 'ck_', $result['consumer_key'] );
		$this->assertStringStartsWith( 'cs_', $result['consumer_secret'] );
		$this->assertEquals( 43, strlen( $result['consumer_key'] ) );
		$this->assertEquals( 43, strlen( $result['consumer_secret'] ) );
	}

	public function test_should_not_insert_wrong_user() {
		$args   = array(
			'user_id' => 500000
		);
		$result = WC_Auth::create_api_key( $args );
		$this->assertFalse( $result );
	}

	public function test_insert_api_keys_scopes() {
		$user_id = $this->factory->user->create();

		$args   = array(
			'user_id' => $user_id
		);
		$result = WC_Auth::create_api_key( $args );
		$this->assertEquals( 'read', $result['key_permissions'] );

		$args['scope'] = 'write';
		$result        = WC_Auth::create_api_key( $args );
		$this->assertEquals( 'write', $result['key_permissions'] );

		$args['scope'] = 'read_write';
		$result        = WC_Auth::create_api_key( $args );
		$this->assertEquals( 'read_write', $result['key_permissions'] );

		// Wrong scope, read is set by default
		$args['scope'] = 'wrong_scope';
		$result        = WC_Auth::create_api_key( $args );
		$this->assertEquals( 'read', $result['key_permissions'] );
	}

	public function test_get_api_key_data() {
		$user_id = $this->factory->user->create();

		$args   = array(
			'user_id'     => $user_id,
			'description' => 'APP_DESCRIPTION'
		);
		$result = WC_Auth::create_api_key( $args );

		$data = WC_Auth::get_api_key_data( $result['key_id'] );

		$this->assertEquals( $args['user_id'], $data->user_id );
		$this->assertEquals( $args['description'], $data->description );
		$this->assertNull( $data->nonces );
	}

	public function test_should_return_false_if_api_key_does_not_exist() {
		$this->assertFalse( WC_Auth::get_api_key_data( 100000 ) );
	}

	public function test_get_api_key_data_by_consumer_key() {
		$user_id = $this->factory->user->create();

		$args   = array(
			'user_id' => $user_id
		);
		$result = WC_Auth::create_api_key( $args );

		$data_by_ck  = WC_Auth::get_api_key_data_by_consumer_key( $result['consumer_key'] );
		$data_by_key = WC_Auth::get_api_key_data( $result['key_id'] );

		$this->assertEquals( $data_by_ck, $data_by_key );

		// Test that cache is cleared when the record is deleted
		WC_Auth::delete_api_key( $result['key_id'] );

		$data_by_ck = WC_Auth::get_api_key_data_by_consumer_key( $result['consumer_key'] );
		$this->assertFalse( $data_by_ck );
	}

	public function test_get_test_api_keys() {
		$user_id = $this->factory->user->create();

		$args    = array(
			'user_id'     => $user_id,
			'description' => 'APP_DESCRIPTION1'
		);
		$result1 = WC_Auth::create_api_key( $args );

		$args    = array(
			'user_id'     => $user_id,
			'description' => 'APP_DESCRIPTION2'
		);
		$result2 = WC_Auth::create_api_key( $args );

		$args    = array(
			'user_id'     => $user_id,
			'description' => 'APP_DESCRIPTION3'
		);
		$result3 = WC_Auth::create_api_key( $args );

		$args    = array(
			'user_id'     => $user_id,
			'description' => 'APP_DESCRIPTION4'
		);
		$result4 = WC_Auth::create_api_key( $args );

		$api_keys = WC_Auth::get_api_keys();
		$this->assertCount( 4, $api_keys );
		$count = WC_Auth::get_api_keys_count();
		$this->assertEquals( 4, $count );

		// Test searching
		$api_keys = WC_Auth::get_api_keys( array( 's' => 'DESCRIPTION2' ) );
		$this->assertCount( 1, $api_keys );
		$count = WC_Auth::get_api_keys_count( array( 's' => 'DESCRIPTION2' ) );
		$this->assertEquals( 1, $count );

		// Test pagination
		$api_keys = WC_Auth::get_api_keys( array( 'per_page' => 1 ) );
		$this->assertCount( 1, $api_keys );
		$this->assertEquals( $result4['key_id'], $api_keys[0]->key_id );

		$api_keys = WC_Auth::get_api_keys( array( 'per_page' => 1, 'page' => 2 ) );
		$this->assertCount( 1, $api_keys );
		$this->assertEquals( $result3['key_id'], $api_keys[0]->key_id );
	}

	public function test_update_api_key() {
		$user_id = $this->factory->user->create();

		$args   = array(
			'user_id'     => $user_id,
			'description' => 'APP_DESCRIPTION'
		);
		$result = WC_Auth::create_api_key( $args );
		$key_id = $result['key_id'];

		$user_id_2 = $this->factory->user->create();
		$new_args  = array(
			'user_id'     => $user_id_2,
			'scope'       => 'write',
			'description' => 'APP_DESCRIPTION_2',
			'last_access' => current_time( 'mysql' ),
			'nonces'      => array( 'nonce1' => '1', 'nonce2' => '2' )
		);
		$result    = WC_Auth::update_api_key( $key_id, $new_args );
		$this->assertTrue( $result );

		$data = WC_Auth::get_api_key_data( $key_id );
		$this->assertEquals( $new_args['user_id'], $data->user_id );
		$this->assertEquals( $new_args['scope'], $data->permissions );
		$this->assertEquals( $new_args['description'], $data->description );
		$this->assertEquals( $new_args['last_access'], $data->last_access );
		$this->assertEquals( $new_args['nonces'], $data->nonces );
	}

	public function test_update_last_access() {
		$user_id = $this->factory->user->create();

		$args   = array(
			'user_id' => $user_id
		);
		$result = WC_Auth::create_api_key( $args );

		WC_Auth::update_last_access( $result['key_id'] );

		$data = WC_Auth::get_api_key_data( $result['key_id'] );
		$this->assertNotNull( $data->last_access );
	}

	public function test_delete_api_key() {
		$user_id = $this->factory->user->create();

		$args   = array(
			'user_id'     => $user_id,
			'description' => 'APP_DESCRIPTION'
		);
		$result = WC_Auth::create_api_key( $args );

		// This will trigger cache, just in case we're not invalidating cache properly
		WC_Auth::get_api_key_data( $result['key_id'] );

		WC_Auth::delete_api_key( $result['key_id'] );

		$this->assertFalse( WC_Auth::get_api_key_data( $result['key_id'] ) );
	}

	public function test_get_api_key_cache() {
		global $wpdb;

		$user_id = $this->factory->user->create();

		$args   = array(
			'user_id' => $user_id
		);
		$result = WC_Auth::create_api_key( $args );

		$data = WC_Auth::get_api_key_data( $result['key_id'] );

		$current_queries = $wpdb->num_queries;

		// This should not make more queries
		$data = WC_Auth::get_api_key_data( $result['key_id'] );
		$this->assertEquals( $current_queries, $wpdb->num_queries );

		WC_Auth::delete_api_key( $result['key_id'] );
		$this->assertFalse( WC_Auth::get_api_key_data( $result['key_id'] ) );

	}

	public function test_get_api_keys_cache() {
		global $wpdb;
		$user_id = $this->factory->user->create();
		$args    = array(
			'user_id'     => $user_id,
			'description' => 'DESCRIPTION1'
		);
		$result1 = WC_Auth::create_api_key( $args );

		$args['description'] = 'DESCRIPTION2';
		$result2             = WC_Auth::create_api_key( $args );

		$args['description'] = 'DESCRIPTION3';
		$result3             = WC_Auth::create_api_key( $args );

		$args['description'] = 'DESCRIPTION4';
		$result4             = WC_Auth::create_api_key( $args );

		WC_Auth::get_api_keys();
		$num_queries = $wpdb->num_queries;

		// This should not make another query
		WC_Auth::get_api_keys();
		$this->assertEquals( $wpdb->num_queries, $num_queries );

		// And it also caches singular data keys. These functions should not make
		// additional queries too
		WC_Auth::get_api_key_data( $result1['key_id'] );
		$this->assertEquals( $wpdb->num_queries, $num_queries );
		WC_Auth::get_api_key_data_by_consumer_key( $result1['consumer_key'] );
		$this->assertEquals( $wpdb->num_queries, $num_queries );
	}

	public function test_get_api_keys_count_cache() {
		global $wpdb;
		$user_id = $this->factory->user->create();
		$args    = array(
			'user_id'     => $user_id,
			'description' => 'DESCRIPTION1'
		);
		$result1 = WC_Auth::create_api_key( $args );

		$args['description'] = 'DESCRIPTION2';
		$result2             = WC_Auth::create_api_key( $args );

		$args['description'] = 'DESCRIPTION3';
		$result3             = WC_Auth::create_api_key( $args );

		$args['description'] = 'DESCRIPTION4';
		$result4             = WC_Auth::create_api_key( $args );

		WC_Auth::get_api_keys_count();
		$num_queries = $wpdb->num_queries;

		// This should not make another query
		WC_Auth::get_api_keys_count();
		$this->assertEquals( $wpdb->num_queries, $num_queries );

		// A search should make another query
		WC_Auth::get_api_keys_count( array( 's' => 'DESCR' ) );
		$this->assertEquals( $wpdb->num_queries, ++ $num_queries );

		// But not searching twice
		WC_Auth::get_api_keys_count( array( 's' => 'DESCR' ) );
		$this->assertEquals( $wpdb->num_queries, $num_queries );

	}
}