<?php
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
	protected $meta_type = 'post';
	protected $object_id_field_for_meta = '';
	protected $internal_meta_keys = array();

	/*
	|--------------------------------------------------------------------------
	| Setters for internal WC_Data properties.
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
			$this->read( $id );
		}
	}

	/**
	 * Simple get content.
	 * @return string
	 */
	public function get_content() {
		return $this->data['content'];
	}

	/**
	 * Simple set content.
	 * @param string $content
	 */
	public function set_content( $content ) {
		$this->data['content'] = $content;
	}

	/**
	 * Simple get bool value.
	 * @return bool
	 */
	public function get_bool_value() {
		return $this->data['bool_value'];
	}

	/**
	 * Simple set bool value.
	 * @return bool
	 */
	public function set_bool_value( $value ) {
		if ( ! is_bool( $value ) ) {
			$this->error( 'invalid_bool_value', 'O noes' );
		}
		$this->data['bool_value'] = $value;
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
	 * Simple create.
	 */
	public function create() {
		if ( 'user' === $this->meta_type ) {
			$content_id = wc_create_new_customer( $this->get_content(), 'username-' . time(), 'hunter2' );
		} else {
			$content_id = wp_insert_post( array( 'post_title' => $this->get_content() ) );
		}
		if ( $content_id ) {
			$this->set_id( $content_id );
		}
	}

	/**
	 * Simple read.
	 */
	public function read( $id ) {
		$this->set_defaults();

		if ( 'user' === $this->meta_type ) {
			if ( empty( $id ) || ! ( $user_object = get_userdata( $id ) ) ) {
				return;
			}
			$this->set_id( absint( $user_object->ID ) );
			$this->set_content( $user_object->user_email );
		} else {
			if ( empty( $id ) || ! ( $post_object = get_post( $id ) ) ) {
				return;
			}
			$this->set_id( absint( $post_object->ID ) );
			$this->set_content( $post_object->post_title );
		}

		$this->read_meta_data();
	}

	/**
	 * Simple update.
	 */
	public function update() {
		global $wpdb;
		$content_id = $this->get_id();

		if ( 'user' === $this->meta_type ) {
			wp_update_user( array( 'ID' => $customer_id, 'user_email' => $this->get_content() ) );
		} else {
			wp_update_post( array( 'ID' => $content_id, 'post_title' => $this->get_content() ) );
		}
	}

	/**
	 * Simple delete.
	 */
	public function delete() {
		if ( 'user' === $this->meta_type ) {
			wp_delete_user( $this->get_id() );
		} else {
			wp_delete_post( $this->get_id() );
		}
	}

	/**
	 * Simple save.
	 */
	public function save() {
		if ( ! $this->get_id() ) {
			$this->create();
		} else {
			$this->update();
		}
		$this->save_meta_data();
	}
}
