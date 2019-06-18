<?php
/**
 * REST API Data controller.
 *
 * Handles requests to the /data endpoint.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Coupons controller class.
 */
class Data extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'data';

	/**
	 * Permission to check.
	 *
	 * @var string
	 */
	protected $resource_type = 'settings';

	/**
	 * Register routes.
	 *
	 * @since 3.5.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
			true
		);
	}

	/**
	 * Return the list of data resources.
	 *
	 * @since  3.5.0
	 * @param  \WP_REST_Request $request Request data.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$data      = array();
		$resources = array(
			array(
				'slug'        => 'continents',
				'description' => __( 'List of supported continents, countries, and states.', 'woocommerce' ),
			),
			array(
				'slug'        => 'countries',
				'description' => __( 'List of supported states in a given country.', 'woocommerce' ),
			),
			array(
				'slug'        => 'currencies',
				'description' => __( 'List of supported currencies.', 'woocommerce' ),
			),
			array(
				'slug'        => 'download-ips',
				'description' => __( 'An endpoint used for searching download logs for a specific IP address.', 'woocommerce' ),
			),
		);

		foreach ( $resources as $resource ) {
			$item   = $this->prepare_item_for_response( (object) $resource, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get data for this object in the format of this endpoint's schema.
	 *
	 * @param \stdClass        $object Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array Array of data in the correct format.
	 */
	protected function get_data_for_response( $object, $request ) {
		return array(
			'slug'        => $object->slug,
			'description' => $object->description,
		);
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function prepare_links( $item, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $item->slug ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the data index schema, conforming to JSON Schema.
	 *
	 * @since  3.5.0
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'data_index',
			'type'       => 'object',
			'properties' => array(
				'slug'        => array(
					'description' => __( 'Data resource ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Data resource description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
