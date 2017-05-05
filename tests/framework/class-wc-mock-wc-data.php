<?php
class WC_Mock_WC_Data_Store extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	protected $meta_type = 'post';
	protected $object_id_field_for_meta = '';
	protected $internal_meta_keys = array();

	/*
	|--------------------------------------------------------------------------
	| Setters for internal properties.
	|--------------------------------------------------------------------------
	| Normally we wouldn't want to be able to change this once the class is defined,
	| but to make testing different types of meta/storage, we should be able to
	| switch out our class settings.
	| These functions just change the properties set above.
	*/

	/**
	 * Set meta type (user or post).
	 * @param string $meta_type
	 */
	function set_meta_type( $meta_type ) {
		$this->meta_type = $meta_type;
	}

	/**
	 * Set object ID field dynamically for testing.
	 * @param string $object_id_field
	 */
	function set_object_id_field( $object_id_field ) {
		$this->object_id_field_for_meta = $object_id_field;
	}

	public function create( &$object ) {
		if ( 'user' === $this->meta_type ) {
			$content_id = wc_create_new_customer( $object->get_content(), 'username-' . time(), 'hunter2' );
		} else {
			$content_id = wp_insert_post( array( 'post_title' => $object->get_content() ) );
		}
		if ( $content_id ) {
			$object->set_id( $content_id );
		}

		$object->apply_changes();
	}

	/**
	 * Simple read.
	 */
	public function read( &$object ) {
		$object->set_defaults();
		$id = $object->get_id();

		if ( 'user' === $this->meta_type ) {
			if ( empty( $id ) || ! ( $user_object = get_userdata( $id ) ) ) {
				return;
			}
			$object->set_content( $user_object->user_email );
		} else {
			if ( empty( $id ) || ! ( $post_object = get_post( $id ) ) ) {
				return;
			}
			$object->set_content( $post_object->post_title );
		}

		$object->read_meta_data();
		$object->set_object_read( true );
	}

	/**
	 * Simple update.
	 */
	public function update( &$object ) {
		global $wpdb;
		$content_id = $object->get_id();

		if ( 'user' === $this->meta_type ) {
			wp_update_user( array( 'ID' => $customer_id, 'user_email' => $object->get_content() ) );
		} else {
			wp_update_post( array( 'ID' => $content_id, 'post_title' => $object->get_content() ) );
		}
	}

	/**
	 * Simple delete.
	 */
	public function delete( &$object, $args = array() ) {
		if ( 'user' === $this->meta_type ) {
			wp_delete_user( $object->get_id() );
		} else {
			wp_delete_post( $object->get_id() );
		}

		$object->set_id( 0 );
	}

}

/**
 * Used for exposing and testing the various Abstract WC_Data methods.
 */
class WC_Mock_WC_Data extends WC_Data {

	/**
	 * Data array
	 */
	protected $data = array(
		'content'    => '',
		'bool_value' => false,
	);

	// see WC_Data
	protected $cache_group = '';
	public $data_store;

	/*
	|--------------------------------------------------------------------------
	| Abstract methods.
	|--------------------------------------------------------------------------
	| Define the abstract methods WC_Data classes expect, so we can go on to
	| testing the good bits.
	*/

	/**
	 * Simple read.
	 */
	public function __construct( $id = '' ) {
		parent::__construct();
		if ( ! empty( $id ) ) {
			$this->set_id( $id );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = new WC_Mock_WC_Data_Store;

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Simple get content.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_content( $context = 'view' ) {
		return $this->get_prop( 'content', $context );
	}

	/**
	 * Simple set content.
	 *
	 * @param string $content
	 */
	public function set_content( $content ) {
		$this->set_prop( 'content', $content );
	}

	/**
	 * Simple get bool value.
	 *
	 * @param  string $context
	 * @return bool
	 */
	public function get_bool_value( $context = 'view' ) {
		return $this->get_prop( 'bool_value', $context );
	}

	/**
	 * Simple set bool value.
	 *
	 * @return bool
	 */
	public function set_bool_value( $value ) {
		if ( ! is_bool( $value ) ) {
			$this->error( 'invalid_bool_value', 'O noes' );
		}
		$this->set_prop( 'bool_value', $value );
	}

	/**
	 * Simple get data as array.
	 * @return array
	 */
	public function get_data() {
		return array_merge(
			$this->data,
			array(
				'meta_data' => $this->get_meta_data(),
			)
		);
	}

	/**
	 * Set the data to any arbitrary data.
	 * @param array $data
	 */
	public function set_data( $data ) {
		$this->data = $data;
	}

	/**
	 * Set the changes to any arbitrary changes.
	 * @param array $changes
	 */
	public function set_changes( $changes ) {
		$this->changes = $changes;
	}

	/**
	 * Simple save.
	 */
	public function save() {
		if ( $this->data_store ) {
			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}
		}
		$this->save_meta_data();
		return $this->get_id();
	}
}
