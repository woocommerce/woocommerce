<?php
namespace WooCommerce\Tests\CRUD;

/**
 * Meta
 * @package WooCommerce\Tests\CRUD
 */
class Meta extends \WC_Unit_Test_Case {

	public function create_test_post() {
		$object = new \WC_Mock_WC_data();
		$object->set_content( 'testing' );
		$object->save();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		add_metadata( 'post', $object_id, 'test_multi_meta_key', 'val2'  );
		add_metadata( 'post', $object_id, 'test_multi_meta_key', 'val3'  );
		$object->read( $object_id ); // reload to make sure we get our meta...
		return $object;
	}

	/**
	 * Tests reading and getting set metadata.
	 */
	function test_get_meta_data() {
		$object    = $this->create_test_post();
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

	}

	/**
	 * Test adding meta data.
	 */
	function test_add_meta_data() {

	}

	/**
	 * Test updating meta data.
	 */
	function test_update_meta_data() {

	}

	/**
	 * Test getting user meta data.
	 */
	function test_user_get_meta_data() {

	}

	/**
	 * Test setting user meta data.
	 */
	function test_user_set_meta_data() {

	}

	/**
	 * Test adding user meta data.
	 */
	function test_user_add_meta_data() {

	}

	/**
	 * Test updating user meta data.
	 */
	function test_user_update_meta_data() {

	}

}
