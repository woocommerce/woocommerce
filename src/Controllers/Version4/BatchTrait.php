<?php
/**
 * Batch trait.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4;

/**
 * BatchTrait.
 */
trait BatchTrait {

	/**
	 * Bulk create, update and delete items.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return array Of \WP_Error or \WP_REST_Response.
	 */
	public function batch_items( $request ) {
		$items = array_filter( $request->get_params() );
		$limit = $this->check_batch_limit( $items );
		if ( is_wp_error( $limit ) ) {
			return $limit;
		}

		$response = [];
		$batches  = [ 'create', 'update', 'delete' ];
		foreach ( $batches as $batch ) {
			if ( ! isset( $items[ $batch ] ) ) {
				continue;
			}
			$items[ $batch ]    = wp_parse_id_list( $items[ $batch ] );
			$response[ $batch ] = $this->{"batch_$batch"}( $items[ $batch ] );
		}

		return $response;
	}

	/**
	 * Check if a given request has access batch create, update and delete items.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return boolean|\WP_Error
	 */
	abstract public function batch_items_permissions_check( $request );

	/**
	 * Register route for batch requests.
	 */
	protected function register_batch_route() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			),
			true
		);
	}

	/**
	 * Get the batch schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	protected function get_public_batch_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'batch',
			'type'       => 'object',
			'properties' => array(
				'create' => array(
					'description' => __( 'List of created resources.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'object',
					),
				),
				'update' => array(
					'description' => __( 'List of updated resources.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'object',
					),
				),
				'delete' => array(
					'description' => __( 'List of delete resources.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'integer',
					),
				),
			),
		);

		return $schema;
	}

	/**
	 * Get normalized rest base.
	 *
	 * @return string
	 */
	protected function get_normalized_rest_base() {
		return preg_replace( '/\(.*\)\//i', '', $this->rest_base );
	}

	/**
	 * Check batch limit.
	 *
	 * @param array $items Request items.
	 * @return bool|\WP_Error
	 */
	protected function check_batch_limit( $items ) {
		$limit = apply_filters( 'woocommerce_rest_batch_items_limit', 100, $this->get_normalized_rest_base() );
		$total = 0;

		$batches  = [ 'create', 'update', 'delete' ];
		foreach ( $batches as $batch ) {
			if ( ! isset( $items[ $batch ] ) ) {
				continue;
			}
			$items[ $batch ] = wp_parse_id_list( $items[ $batch ] );
			$total           = $total + count( $items[ $batch ] );
		}

		if ( $total > $limit ) {
			/* translators: %s: items limit */
			return new \WP_Error( 'woocommerce_rest_request_entity_too_large', sprintf( __( 'Unable to accept more than %s items for this request.', 'woocommerce' ), $limit ), array( 'status' => 413 ) );
		}

		return true;
	}

	/**
	 * Get default params from schema.
	 *
	 * @return array
	 */
	protected function get_default_params() {
		$defaults = [];
		$schema   = $this->get_public_item_schema();
		foreach ( $schema['properties'] as $arg => $options ) {
			if ( isset( $options['default'] ) ) {
				$defaults[ $arg ] = $options['default'];
			}
		}
		return $defaults;
	}

	/**
	 * Batch create items.
	 *
	 * @param array $items Array of item ids.
	 * @return array Response data.
	 */
	protected function batch_create( $items ) {
		$response = [];

		foreach ( $items as $id ) {
			$item = new \WP_REST_Request( 'POST' );
			$item->set_default_params( $this->get_default_params() );
			$item->set_body_params( $item );
			$item_response = $this->create_item( $item );
			$response[]    = $this->format_response( $id, $item_response );
		}

		return $response;
	}

	/**
	 * Batch update items.
	 *
	 * @param array $items Array of item ids.
	 * @return array Response data.
	 */
	protected function batch_update( $items ) {
		$response = [];

		foreach ( $items as $id ) {
			$item = new \WP_REST_Request( 'PUT' );
			$item->set_body_params( $item );
			$item_response = $this->update_item( $item );
			$response[]    = $this->format_response( $id, $item_response );
		}

		return $response;
	}

	/**
	 * Batch delete items.
	 *
	 * @param array $items Array of item ids.
	 * @return array Response data.
	 */
	protected function batch_delete( $items ) {
		$response = [];

		foreach ( $items as $id ) {
			$item = new \WP_REST_Request( 'DELETE' );
			$item->set_query_params(
				[
					'id'    => $id,
					'force' => true,
				]
			);
			$item_response = $this->delete_item( $item );
			$response[]    = $this->format_response( $id, $item_response );
		}

		return $response;
	}

	/**
	 * Format response data.
	 *
	 * @param int                         $id ID of item being updated.
	 * @param \WP_REST_Response|\WP_Error $response Response object.
	 * @return array
	 */
	protected function format_response( $id, $response ) {
		/**
		 * REST Server
		 *
		 * @var \WP_REST_Server $wp_rest_server
		 */
		global $wp_rest_server;

		if ( is_wp_error( $response ) ) {
			return $this->format_error_response( $response );
		} else {
			return array(
				'id'    => $id,
				'error' => $wp_rest_server->response_to_data( $response, '' ),
			);
		}
	}

	/**
	 * Format WP Error to response data.
	 *
	 * @param \WP_Error $error Error object.
	 * @return array
	 */
	protected function format_error_response( $error ) {
		return array(
			'code'    => $error->get_error_code(),
			'message' => $error->get_error_message(),
			'data'    => $error->get_error_data(),
		);
	}
}
