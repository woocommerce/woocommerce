<?php
/**
 * REST API CustomFields controller
 *
 * Handles requests to the /products endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   9.2.0
 */

use Automattic\WooCommerce\Utilities\I18nUtil;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Custom Fields controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Product_Custom_Fields_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'product-custom-fields';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'product';

	/**
	 * Register the routes for products.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/names',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_names' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get a collection of custom field names.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item_names( $request ) {
		global $wpdb;

		$search = trim( $request['search'] );
		$order  = strtoupper( $request['order'] ) === 'DESC' ? 'DESC' : 'ASC';

		$query = $wpdb->prepare(
			"SELECT DISTINCT m.meta_key
			FROM {$wpdb->postmeta} m LEFT JOIN {$wpdb->posts} p ON m.post_id = p.id
			WHERE p.post_type = '{$this->post_type}' AND m.meta_key NOT LIKE '\_%' AND m.meta_key LIKE %s
			ORDER BY m.meta_key {$order}
			LIMIT 100",
			"%{$search}%"
		);

		$query_result = $wpdb->get_results( $query );

		$custom_field_names = array();
		foreach ( $query_result as $custom_field_name ) {
			$custom_field_names[] = $custom_field_name->meta_key;
		}

		$response = rest_ensure_response( $custom_field_names );

		return $response;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}
}
