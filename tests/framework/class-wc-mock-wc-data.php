<?php
/**
 * Used for exposing and testing the various Abstract WC_Data methods.
 */
class WC_Mock_WC_data extends WC_Data {

	/**
	 * Data array
	 */
	protected $_data = array(
		'id'      => 0,
		'content' => '',
	);

	// see WC_Data
	protected $_cache_group = '';
	protected $_meta_type = 'post';
	protected $object_id_field_for_meta = '';
	protected $_internal_meta_keys = array();

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
		$this->_meta_type = $meta_type;
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
		if ( ! empty( $id ) ) {
			$this->read( $id );
		}
	}

	/**
	 * Simple get ID.
	 * @return integer
	 */
	public function get_id() {
		return intval( $this->_data['id'] );
	}

	/**
	 * Simple get content.
	 * @return string
	 */
	public function get_content() {
		return $this->_data['content'];
	}

	/**
	 * SImple set content.
	 * @param string $content
	 */
	public function set_content( $content ) {
		$this->_data['content'] = $content;
	}

	/**
	 * Simple get data as array.
	 * @return array
	 */
	public function get_data() {
		return array_merge(
			$this->_data,
			array(
				'meta_data' => $this->get_meta_data(),
			)
		);
	}

	/**
	 * Simple create.
	 */
	public function create() {
		if ( 'user' === $this->_meta_type ) {
			$content_id = wc_create_new_customer( $this->get_content(), 'username-' . time(), 'hunter2' );
		} else {
			$content_id = wp_insert_post( array ( 'post_title' => $this->get_content() ) );
		}
		if ( $content_id ) {
			$this->_data['id'] = $content_id;
		}
	}

	/**
	 * Simple read.
	 */
	public function read( $id ) {

		if ( 'user' === $this->_meta_type ) {
			if ( empty( $id ) || ! ( $user_object = get_userdata( $id ) ) ) {
				return;
			}
			$this->_data['id'] = absint( $user_object->ID );
			$this->set_content( $user_object->user_email );
		} else {
			if ( empty( $id ) || ! ( $post_object = get_post( $id ) ) ) {
				return;
			}
			$this->_data['id'] = absint( $post_object->ID );
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

		if ( 'user' === $this->_meta_type ) {
			wp_update_user( array( 'ID' => $customer_id, 'user_email' => $this->get_content() ) );
		} else {
			wp_update_post( array( 'ID' => $content_id, 'post_title' => $this->get_content() ) );
		}
	}

	/**
	 * Simple delete.
	 */
	public function delete() {
		if ( 'user' === $this->_meta_type ) {
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
