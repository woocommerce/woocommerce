<?php
/**
 * Convert data in the product schema format to an object.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Requests;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractObjectRequest class.
 */
abstract class AbstractObjectRequest {

	/**
	 * Request data.
	 *
	 * @var array Array of request data.
	 */
	protected $request;

	/**
	 * Constructor. Takes an existing or blank object and updates values based on the requset.
	 *
	 * @param \WP_REST_Request $request Request data.
	 */
	public function __construct( $request ) {
		$this->request = (array) $request->get_params();
	}

	/**
	 * Get param from request.
	 *
	 * @param string $name Param name.
	 * @param mixed  $default Default to return if not set.
	 */
	protected function get_param( $name, $default = null ) {
		return isset( $this->request[ $name ] ) ? $this->request[ $name ] : $default;
	}

	/**
	 * Convert request to object.
	 *
	 * @throws \WC_REST_Exception Will throw an exception if the resulting product object is invalid.
	 */
	abstract public function prepare_object();

	/**
	 * Set meta data.
	 *
	 * @param mixed $object Product object reference.
	 */
	protected function set_meta_data( &$object ) {
		$meta_data = $this->get_param( 'meta_data', null );

		if ( ! is_null( $meta_data ) ) {
			foreach ( $meta_data as $meta ) {
				$object->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}
	}
}
