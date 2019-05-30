<?php
/**
 * REST API Products Controller
 *
 * Handles requests to /products/*
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Products controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Products_Controller
 */
class WC_Admin_REST_Products_Controller extends WC_REST_Products_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Adds properties that can be embed via ?_embed=1.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$properties_to_embed = array(
			'id',
			'name',
			'slug',
			'permalink',
			'images',
			'description',
			'short_description',
		);

		foreach ( $properties_to_embed as $property ) {
			$schema['properties'][ $property ]['context'][] = 'embed';
		}

		return $schema;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                 = parent::get_collection_params();
		$params['low_in_stock'] = array(
			'description'       => __( 'Limit result set to products that are low or out of stock.', 'woocommerce-admin' ),
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'wc_string_to_bool',
		);
		$params['search']       = array(
			'description'       => __( 'Search by similar product name or sku.', 'woocommerce-admin' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}


	/**
	 * Add product name and sku filtering to the WC API.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		if ( ! empty( $request['search'] ) ) {
			$args['search'] = trim( $request['search'] );
			unset( $args['s'] );
		}
		if ( ! empty( $request['low_in_stock'] ) ) {
			$args['low_in_stock'] = $request['low_in_stock'];
			$args['post_type']    = array( 'product', 'product_variation' );
		}

		return $args;
	}

	/**
	 * Get a collection of posts and add the post title filter option to WP_Query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		add_filter( 'posts_where', array( __CLASS__, 'add_wp_query_filter' ), 10, 2 );
		add_filter( 'posts_join', array( __CLASS__, 'add_wp_query_join' ), 10, 2 );
		add_filter( 'posts_groupby', array( __CLASS__, 'add_wp_query_group_by' ), 10, 2 );
		$response = parent::get_items( $request );
		remove_filter( 'posts_where', array( __CLASS__, 'add_wp_query_filter' ), 10 );
		remove_filter( 'posts_join', array( __CLASS__, 'add_wp_query_join' ), 10 );
		remove_filter( 'posts_groupby', array( __CLASS__, 'add_wp_query_group_by' ), 10 );
		return $response;
	}

	/**
	 * Add in conditional search filters for products.
	 *
	 * @param string $where Where clause used to search posts.
	 * @param object $wp_query WP_Query object.
	 * @return string
	 */
	public static function add_wp_query_filter( $where, $wp_query ) {
		global $wpdb;

		$search = $wp_query->get( 'search' );
		if ( $search ) {
			$search = $wpdb->esc_like( $search );
			$search = "'%" . $search . "%'";
			$where .= " AND ({$wpdb->posts}.post_title LIKE {$search}";
			$where .= wc_product_sku_enabled() ? ' OR ps_post_meta.meta_key = "_sku" AND ps_post_meta.meta_value LIKE ' . $search . ')' : ')';
		}

		if ( $wp_query->get( 'low_in_stock' ) ) {
			$low_stock_amount = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
			$where           .= " AND lis_postmeta2.meta_key = '_manage_stock'
			AND lis_postmeta2.meta_value = 'yes'
			AND lis_postmeta.meta_key = '_stock'
			AND lis_postmeta.meta_value IS NOT NULL
			AND lis_postmeta3.meta_key = '_low_stock_amount'
			AND (
				lis_postmeta3.meta_value > ''
				AND CAST(lis_postmeta.meta_value AS SIGNED) <= CAST(lis_postmeta3.meta_value AS SIGNED)
				OR lis_postmeta3.meta_value <= ''
				AND CAST(lis_postmeta.meta_value AS SIGNED) <= {$low_stock_amount}
			)";
		}

		return $where;
	}

	/**
	 * Join posts meta tables when product search or low stock query is present.
	 *
	 * @param string $join Join clause used to search posts.
	 * @param object $wp_query WP_Query object.
	 * @return string
	 */
	public static function add_wp_query_join( $join, $wp_query ) {
		global $wpdb;

		$search = $wp_query->get( 'search' );
		if ( $search && wc_product_sku_enabled() ) {
			$join .= " INNER JOIN {$wpdb->postmeta} AS ps_post_meta ON ps_post_meta.post_id = {$wpdb->posts}.ID";
		}

		if ( $wp_query->get( 'low_in_stock' ) ) {
			$join .= " INNER JOIN {$wpdb->postmeta} AS lis_postmeta ON {$wpdb->posts}.ID = lis_postmeta.post_id
			INNER JOIN {$wpdb->postmeta} AS lis_postmeta2 ON {$wpdb->posts}.ID = lis_postmeta2.post_id
			INNER JOIN {$wpdb->postmeta} AS lis_postmeta3 ON {$wpdb->posts}.ID = lis_postmeta3.post_id";
		}

		return $join;
	}

	/**
	 * Group by post ID to prevent duplicates.
	 *
	 * @param string $groupby Group by clause used to organize posts.
	 * @param object $wp_query WP_Query object.
	 * @return string
	 */
	public static function add_wp_query_group_by( $groupby, $wp_query ) {
		global $wpdb;

		$search       = $wp_query->get( 'search' );
		$low_in_stock = $wp_query->get( 'low_in_stock' );
		if ( empty( $groupby ) && ( $search || $low_in_stock ) ) {
			$groupby = $wpdb->posts . '.ID';
		}
		return $groupby;
	}
}
