<?php
/**
 * REST API CustomFields controller
 *
 * Handles requests to the /products/custom-fields endpoint.
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
	protected $rest_base = 'products/custom-fields';

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
		$page   = (int) $request['page'];
		$limit  = (int) $request['per_page'];
		$offset = ( $page - 1 ) * $limit;

		$base_query = $wpdb->prepare(
			"SELECT DISTINCT post_metas.meta_key
			FROM {$wpdb->postmeta} post_metas LEFT JOIN {$wpdb->posts} posts ON post_metas.post_id = posts.id
			WHERE posts.post_type = %s AND post_metas.meta_key NOT LIKE %s AND post_metas.meta_key LIKE %s",
			$this->post_type,
			$wpdb->esc_like( '_' ) . '%',
			'%' . $wpdb->esc_like( $search ) . '%'
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $base_query has been prepared already and $order is a static value.
		$query = $wpdb->prepare(
			"$base_query ORDER BY post_metas.meta_key $order LIMIT %d, %d",
			$offset,
			$limit
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $base_query has been prepared already.
		$total_query = "SELECT COUNT(1) FROM ($base_query) AS total";

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $query has been prepared already.
		$query_result = $wpdb->get_results( $query );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $total_query has been prepared already.
		$total_items = $wpdb->get_var( $total_query );

		$custom_field_names = array();
		foreach ( $query_result as $custom_field_name ) {
			$custom_field_names[] = $custom_field_name->meta_key;
		}

		$response = rest_ensure_response( $custom_field_names );

		$response->header( 'X-WP-Total', (int) $total_items );
		$max_pages = ceil( $total_items / $limit );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( $request->get_query_params(), rest_url( '/' . $this->namespace . '/' . $this->rest_base . '/names' ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

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

	/**
	 * Add new options for 'order' to the collection params.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params          = parent::get_collection_params();
		$params['order'] = array(
			'description'       => __( 'Order sort items ascending or descending.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'asc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}
}
