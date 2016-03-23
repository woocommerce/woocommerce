<?php

/**
 * Meta
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_CRUD_Meta extends WC_Unit_Test_Case {

	/**
	 * Create a test post we can add/test meta against.
	 */
	public function create_test_post() {
		$object = new WC_Mock_WC_Data();
		$object->set_content( 'testing' );
		$object->save();
		return $object;
	}

	/**
	 * Create a test user we can add/test meta against.
	 */
	public function create_test_user() {
		$object = new WC_Mock_WC_Data();
		$object->set_meta_type( 'user' );
		$object->set_object_id_field( 'user_id' );
		$object->set_content( 'testing@woo.dev' );
		$object->save();
		return $object;
	}

	/**
	 * Tests reading and getting set metadata.
	 */
	function test_get_meta_data() {
		$object    = $this->create_test_post();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		add_metadata( 'post', $object_id, 'test_multi_meta_key', 'val2'  );
		add_metadata( 'post', $object_id, 'test_multi_meta_key', 'val3'  );
		$object->read( $object_id );

		$meta_data = $object->get_meta_data();
		$i         = 1;

		$this->assertNotEmpty( $meta_data );
		foreach ( $meta_data as $mid => $data ) {
			$this->assertEquals( "val{$i}", $data->value );
			$i++;
		}
	}

	/**
	 * Test getting meta by ID.
	 */
	function test_get_meta() {
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		add_metadata( 'post', $object_id, 'test_multi_meta_key', 'val2'  );
		add_metadata( 'post', $object_id, 'test_multi_meta_key', 'val3'  );
		$object->read( $object_id );

		// test single meta key
		$single_meta = $object->get_meta( 'test_meta_key' );
		$this->assertEquals( 'val1', $single_meta );

		// test getting multiple
		$meta = $object->get_meta( 'test_multi_meta_key', false );
		$i    = 2;
		foreach ( $meta as $data ) {
			$this->assertEquals( 'test_multi_meta_key', $data->key );
			$this->assertEquals( "val{$i}", $data->value );
			$i++;
		}
	}

	/**
	 * Test setting meta.
	 */
	function test_set_meta_data() {
		global $wpdb;
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		add_metadata( 'post', $object_id, 'test_meta_key_2', 'val2', true );
		$object->read( $object_id );

		$metadata     = array();
		$raw_metadata = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_id, meta_key, meta_value
			FROM {$wpdb->prefix}postmeta
			WHERE post_id = %d ORDER BY meta_id
		", $object_id ) );

		foreach ( $raw_metadata as $meta ) {
			$metadata[] = (object) array(
				'key'     => $meta->meta_key,
				'value'   => $meta->meta_value,
				'meta_id' =>  $meta->meta_id,
			);
		}

		$object = new WC_Mock_WC_Data();
		$object->set_meta_data( $metadata );

		$this->assertEquals( $metadata, $object->get_meta_data() );
	}

	/**
	 * Test adding meta data.
	 */
	function test_add_meta_data() {
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		$data = 'add_meta_data_' . time();
		$object->add_meta_data( 'test_new_field', $data );
		$meta = $object->get_meta( 'test_new_field' );
		$this->assertEquals( $data, $meta );
	}

	/**
	 * Test updating meta data.
	 */
	function test_update_meta_data() {
		global $wpdb;
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		$object->read( $object_id );

		$this->assertEquals( 'val1', $object->get_meta( 'test_meta_key' ) );

		$metadata     = array();
		$meta_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT meta_id
			FROM {$wpdb->prefix}postmeta
			WHERE post_id = %d LIMIT 1
		", $object_id ) );

		$object->update_meta_data( 'test_meta_key', 'updated_value', $meta_id );
		$this->assertEquals( 'updated_value', $object->get_meta( 'test_meta_key' ) );
	}

	/**
	 * Test deleting meta.
	 */
	function test_delete_meta_data() {
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		$object->read( $object_id );

		$this->assertEquals( 'val1', $object->get_meta( 'test_meta_key' ) );

		$object->delete_meta_data( 'test_meta_key' );

		$this->assertEmpty( $object->get_meta( 'test_meta_key' ) );
	}


	/**
	 * Test saving metadata.. (Actually making sure changes are written to DB)
	 */
	function test_save_meta_data() {
		global $wpdb;
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		add_metadata( 'post', $object_id, 'test_meta_key_2', 'val2', true );
		$object->read( $object_id );

		$raw_metadata = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_id, meta_key, meta_value
			FROM {$wpdb->prefix}postmeta
			WHERE post_id = %d ORDER BY meta_id
		", $object_id ) );


		$object->delete_meta_data( 'test_meta_key' );
		$object->update_meta_data( 'test_meta_key_2', 'updated_value', $raw_metadata[1]->meta_id );

		$object->save();
		$object->read( $object_id ); // rereads from the DB

		$this->assertEmpty( $object->get_meta( 'test_meta_key' ) );
		$this->assertEquals( 'updated_value', $object->get_meta( 'test_meta_key_2' ) );
	}

	/**
	 * Test reading/getting user meta data too.
	 */
	function test_usermeta() {
		$object = $this->create_test_user();
		$object_id = $object->get_id();
		add_metadata( 'user', $object_id, 'test_meta_key', 'val1', true );
		add_metadata( 'user', $object_id, 'test_meta_key_2', 'val2', true );
		$object->read( $object_id );

		$this->assertEquals( 'val1', $object->get_meta( 'test_meta_key' ) );
		$this->assertEquals( 'val2', $object->get_meta( 'test_meta_key_2' ) );
	}

	/**
	 * Test adding meta data/updating meta data just added without keys colliding when changing
	 * data before a save.
	 */
	function test_add_meta_data_overwrite_before_save() {
		$object = new WC_Mock_WC_Data;
		$object->add_meta_data( 'test_field_0', 'another field', true );
		$object->add_meta_data( 'test_field_1', 'another field', true );
		$object->add_meta_data( 'test_field_2', 'val1', true );
		$object->update_meta_data( 'test_field_0', 'another field 2' );
		$this->assertEquals( 'val1', $object->get_meta( 'test_field_2' ) );
	}

}
