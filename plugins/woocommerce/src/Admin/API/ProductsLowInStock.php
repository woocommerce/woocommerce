<?php
/**
 * REST API ProductsLowInStock Controller
 *
 * Handles request to /products/low-in-stock
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

/**
 * ProductsLowInStock controller.
 *
 * @internal
 * @extends WC_REST_Products_Controller
 */
final class ProductsLowInStock extends \WC_REST_Products_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'products/low-in-stock',
			array(
				'args'   => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'products/count-low-in-stock',
			array(
				'args'   => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_low_in_stock_count' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_low_in_stock_count_params(),
				),
				'schema' => array( $this, 'get_low_in_stock_count_schema' ),
			)
		);
	}

	/**
	 * Return # of low in stock count.
	 *
	 * @param WP_REST_Request $request request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function get_low_in_stock_count( $request ) {
		global $wpdb;
		$status              = $request->get_param( 'status' );
		$low_stock_threshold = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );

		$sidewide_stock_threshold_only = $this->is_using_sitewide_stock_threshold_only();
		$count_query_string            = $this->get_count_query( $sidewide_stock_threshold_only );
		$count_query_results           = $wpdb->get_results(
		// phpcs:ignore -- not sure why phpcs complains about this line when prepare() is used here.
			$wpdb->prepare( $count_query_string, $status, $low_stock_threshold ),
		);

		$total_results = (int) $count_query_results[0]->total;
		$response      = rest_ensure_response( array( 'total' => $total_results ) );
		$response->header( 'X-WP-Total', $total_results );
		$response->header( 'X-WP-TotalPages', 0 );

		return $response;
	}

	/**
	 * Get low in stock products.
	 *
	 * @param WP_REST_Request $request request object.
	 *
	 * @return WP_REST_Response|WP_ERROR
	 */
	public function get_items( $request ) {
		$query_results = $this->get_low_in_stock_products(
			$request->get_param( 'page' ),
			$request->get_param( 'per_page' ),
			$request->get_param( 'status' )
		);

		// set images and attributes.
		$query_results['results'] = array_map(
			function( $query_result ) {
				$product                  = wc_get_product( $query_result );
				$query_result->images     = $this->get_images( $product );
				$query_result->attributes = $this->get_attributes( $product );
				return $query_result;
			},
			$query_results['results']
		);

		// set last_order_date.
		$query_results['results'] = $this->set_last_order_date( $query_results['results'] );

		// convert the post data to the expected API response for the backward compatibility.
		$query_results['results'] = array_map( array( $this, 'transform_post_to_api_response' ), $query_results['results'] );

		$response = rest_ensure_response( array_values( $query_results['results'] ) );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', $query_results['pages'] );

		return $response;
	}

	/**
	 * Set the last order date for each data.
	 *
	 * @param array $results query result from get_low_in_stock_products.
	 *
	 * @return mixed
	 */
	protected function set_last_order_date( $results = array() ) {
		global $wpdb;
		if ( 0 === count( $results ) ) {
			return $results;
		}

		$wheres = array();
		foreach ( $results as $result ) {
			'product_variation' === $result->post_type ?
				array_push( $wheres, "(product_id={$result->post_parent} and variation_id={$result->ID})" )
				: array_push( $wheres, "product_id={$result->ID}" );
		}

		count( $wheres ) ? $where_clause = implode( ' or ', $wheres ) : $where_clause = $wheres[0];

		$product_lookup_table = $wpdb->prefix . 'wc_order_product_lookup';
		$query_string         = "
			select
				product_id,
				variation_id,
				MAX( wc_order_product_lookup.date_created ) AS last_order_date
			from {$product_lookup_table} wc_order_product_lookup
			where {$where_clause}
			group by product_id
			order by date_created desc
		";

		// phpcs:ignore -- ignore prepare() warning as we're not using any user input here.
		$last_order_dates = $wpdb->get_results( $query_string );
		$last_order_dates_index = array();
		// Make an index with product_id_variation_id as a key
		// so that it can be referenced back without looping the whole array.
		foreach ( $last_order_dates as $last_order_date ) {
			$last_order_dates_index[ $last_order_date->product_id . '_' . $last_order_date->variation_id ] = $last_order_date;
		}

		foreach ( $results as &$result ) {
			'product_variation' === $result->post_type ?
				$index_key   = $result->post_parent . '_' . $result->ID
				: $index_key = $result->ID . '_' . $result->post_parent;

			if ( isset( $last_order_dates_index[ $index_key ] ) ) {
				$result->last_order_date = $last_order_dates_index[ $index_key ]->last_order_date;
			}
		}

		return $results;
	}

	/**
	 * Get low in stock products data.
	 *
	 * @param int    $page current page.
	 * @param int    $per_page items per page.
	 * @param string $status post status.
	 *
	 * @return array
	 */
	protected function get_low_in_stock_products( $page = 1, $per_page = 1, $status = ProductStatus::PUBLISH ) {
		global $wpdb;

		$offset              = ( $page - 1 ) * $per_page;
		$low_stock_threshold = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );

		$sidewide_stock_threshold_only = $this->is_using_sitewide_stock_threshold_only();

		$query_string = $this->get_query( $sidewide_stock_threshold_only );

		$query_results = $wpdb->get_results(
			// phpcs:ignore -- not sure why phpcs complains about this line when prepare() is used here.
			$wpdb->prepare( $query_string, $status, $low_stock_threshold, $offset, $per_page ),
			OBJECT_K
		);

		$count_query_string  = $this->get_count_query( $sidewide_stock_threshold_only );
		$count_query_results = $wpdb->get_results(
		// phpcs:ignore -- not sure why phpcs complains about this line when prepare() is used here.
			$wpdb->prepare( $count_query_string, $status, $low_stock_threshold ),
		);

		$total_results = $count_query_results[0]->total;

		return array(
			'results' => $query_results,
			'total'   => (int) $total_results,
			'pages'   => (int) ceil( $total_results / (int) $per_page ),
		);
	}

	/**
	 * Check to see if store is using sitewide threshold only. Meaning that it does not have any custom
	 * stock threshold for a product.
	 *
	 * @return bool
	 */
	protected function is_using_sitewide_stock_threshold_only() {
		global $wpdb;
		$count = $wpdb->get_var( "select count(*) as total from {$wpdb->postmeta} where meta_key='_low_stock_amount'" );
		return 0 === (int) $count;
	}

	/**
	 * Transform post object to expected API response.
	 *
	 * @param object $query_result a row of query result from get_low_in_stock_products().
	 *
	 * @return array
	 */
	protected function transform_post_to_api_response( $query_result ) {
		$low_stock_amount = null;
		if ( isset( $query_result->low_stock_amount ) ) {
			$low_stock_amount = (int) $query_result->low_stock_amount;
		}

		if ( ! isset( $query_result->last_order_date ) ) {
			$query_result->last_order_date = null;
		}

		return array(
			'id'               => (int) $query_result->ID,
			'images'           => $query_result->images,
			'attributes'       => $query_result->attributes,
			'low_stock_amount' => $low_stock_amount,
			'last_order_date'  => wc_rest_prepare_date_response( $query_result->last_order_date ),
			'name'             => $query_result->post_title,
			'parent_id'        => (int) $query_result->post_parent,
			'stock_quantity'   => (int) $query_result->stock_quantity,
			'type'             => 'product_variation' === $query_result->post_type ? ProductType::VARIATION : ProductType::SIMPLE,
		);
	}

	/**
	 * Return a query string for low in stock products.
	 * The query string includes the following replacement strings:
	 * - :selects
	 * - :postmeta_join
	 * - :postmeta_wheres
	 * - :orderAndLimit
	 *
	 * @param array $replacements  of replacement strings.
	 *
	 * @return string
	 */
	private function get_base_query( $replacements = array() ) {
		global $wpdb;
		$query = "
			SELECT
				:selects
			FROM
			  {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup
			  LEFT JOIN {$wpdb->posts} wp_posts ON wp_posts.ID = wc_product_meta_lookup.product_id
			  :postmeta_join
			WHERE
			  wp_posts.post_type IN ('product', 'product_variation')
			  AND wp_posts.post_status = %s
			  AND wc_product_meta_lookup.stock_quantity IS NOT NULL
			  AND wc_product_meta_lookup.stock_status IN('instock', 'outofstock')
			  :postmeta_wheres
			  :orderAndLimit
		";

		return strtr( $query, $replacements );
	}

	/**
	 * Add sitewide stock query string to base query string.
	 *
	 * @param string $query Base query string.
	 *
	 * @return string
	 */
	private function add_sitewide_stock_query_str( $query ) {
		global $wpdb;
		$postmeta = array(
			'select' => 'meta.meta_value AS low_stock_amount,',
			'join'   => "LEFT JOIN {$wpdb->postmeta} AS meta ON wp_posts.ID = meta.post_id
			  AND meta.meta_key = '_low_stock_amount'",
			'wheres' => "AND (
			    (
			      meta.meta_value > ''
			      AND wc_product_meta_lookup.stock_quantity <= CAST(
			        meta.meta_value AS SIGNED
			      )
			    )
			    OR (
			      (
			        meta.meta_value IS NULL
			        OR meta.meta_value <= ''
			      )
			      AND wc_product_meta_lookup.stock_quantity <= %d
			    )
		    )",
		);

		return strtr(
			$query,
			array(
				':postmeta_select' => $postmeta['select'],
				':postmeta_join'   => $postmeta['join'],
				':postmeta_wheres' => $postmeta['wheres'],
			)
		);
	}

	/**
	 * Generate a query.
	 *
	 * @param bool $sitewide_only generates a query for sitewide low stock threshold only query.
	 *
	 * @return string
	 */
	protected function get_query( $sitewide_only = false ) {
		$query = $this->get_base_query(
			array(
				':selects'       => 'wp_posts.*, :postmeta_select wc_product_meta_lookup.stock_quantity',
				':orderAndLimit' => 'order by wc_product_meta_lookup.product_id DESC limit %d, %d',
			)
		);

		if ( ! $sitewide_only ) {
			return $this->add_sitewide_stock_query_str( $query );
		}

		return strtr(
			$query,
			array(
				':postmeta_select' => '',
				':postmeta_join'   => '',
				':postmeta_wheres' => 'AND wc_product_meta_lookup.stock_quantity <= %d',
			)
		);
	}

	/**
	 * Generate a count query.
	 *
	 * @param bool $sitewide_only generates a query for sitewide low stock threshold only query.
	 *
	 * @return string
	 */
	protected function get_count_query( $sitewide_only = false ) {
		$query = $this->get_base_query(
			array(
				':selects'       => 'count(*) as total',
				':orderAndLimit' => '',
			)
		);

		if ( ! $sitewide_only ) {
			return $this->add_sitewide_stock_query_str( $query );
		}

		return strtr(
			$query,
			array(
				':postmeta_select' => '',
				':postmeta_join'   => '',
				':postmeta_wheres' => 'AND wc_product_meta_lookup.stock_quantity <= %d',
			)
		);
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['page']     = array(
			'description'       => __( 'Current page of the collection.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page'] = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'default'           => 'publish',
			'description'       => __( 'Limit result set to products assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_merge( array_keys( get_post_statuses() ), array( ProductStatus::FUTURE ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get the query params for collections for /count-low-in-stock endpoint.
	 *
	 * @return array
	 */
	public function get_low_in_stock_count_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';
		$params['status']             = array(
			'default'           => 'publish',
			'description'       => __( 'Limit result set to products assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_merge( array_keys( get_post_statuses() ), array( ProductStatus::FUTURE ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get the schema for /count-low-in-stock response.
	 *
	 * @return array
	 */
	public function get_low_in_stock_count_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Count Low in Stock Items',
			'type'       => 'object',
			'properties' => array(
				'type'       => 'object',
				'properties' => array(
					'total' => 'integer',
				),
			),
		);
	}
}
