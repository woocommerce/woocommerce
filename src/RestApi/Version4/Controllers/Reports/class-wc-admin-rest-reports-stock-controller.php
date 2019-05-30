<?php
/**
 * REST API Reports stock controller
 *
 * Handles requests to the /reports/stock endpoint.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports stock controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Reports_Controller
 */
class WC_Admin_REST_Reports_Stock_Controller extends WC_REST_Reports_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/stock';

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param  WP_REST_Request $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args                        = array();
		$args['offset']              = $request['offset'];
		$args['order']               = $request['order'];
		$args['orderby']             = $request['orderby'];
		$args['paged']               = $request['page'];
		$args['post__in']            = $request['include'];
		$args['post__not_in']        = $request['exclude'];
		$args['posts_per_page']      = $request['per_page'];
		$args['post_parent__in']     = $request['parent'];
		$args['post_parent__not_in'] = $request['parent_exclude'];

		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'date ID';
		} elseif ( 'stock_status' === $args['orderby'] ) {
			$args['meta_query'] = array( // WPCS: slow query ok.
				'relation'      => 'AND',
				'_stock_status' => array(
					'key'     => '_stock_status',
					'compare' => 'EXISTS',
				),
				'_stock'        => array(
					'key'     => '_stock',
					'compare' => 'EXISTS',
					'type'    => 'NUMERIC',
				),
			);
			$args['orderby']    = array(
				'_stock_status' => $args['order'],
				'_stock'        => 'desc' === $args['order'] ? 'asc' : 'desc',
			);
		} elseif ( 'stock_quantity' === $args['orderby'] ) {
			$args['meta_key'] = '_stock'; // WPCS: slow query ok.
			$args['orderby']  = 'meta_value_num';
		} elseif ( 'include' === $args['orderby'] ) {
			$args['orderby'] = 'post__in';
		} elseif ( 'id' === $args['orderby'] ) {
			$args['orderby'] = 'ID'; // ID must be capitalized.
		} elseif ( 'sku' === $args['orderby'] ) {
			$args['meta_key'] = '_sku'; // WPCS: slow query ok.
			$args['orderby']  = 'meta_value';
		}

		$args['post_type'] = array( 'product', 'product_variation' );

		if ( 'lowstock' === $request['type'] ) {
			$low_stock = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
			$no_stock  = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );

			$args['meta_query'] = array( // WPCS: slow query ok.
				array(
					'key'   => '_manage_stock',
					'value' => 'yes',
				),
				array(
					'key'     => '_stock',
					'value'   => array( $no_stock, $low_stock ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
			);
		} elseif ( in_array( $request['type'], array_keys( wc_get_product_stock_status_options() ), true ) ) {
			$args['meta_query'] = array( // WPCS: slow query ok.
				array(
					'key'   => '_stock_status',
					'value' => $request['type'],
				),
			);
		}

		$query_args['ignore_sticky_posts'] = true;

		return $args;
	}

	/**
	 * Query products.
	 *
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_products( $query_args ) {
		$query  = new WP_Query();
		$result = $query->query( $query_args );

		$total_posts = $query->found_posts;
		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );
			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		return array(
			'objects' => array_map( 'wc_get_product', $result ),
			'total'   => (int) $total_posts,
			'pages'   => (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] ),
		);
	}

	/**
	 * Get all reports.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$query_args    = $this->prepare_reports_query( $request );
		$query_results = $this->get_products( $query_args );

		$objects = array();
		foreach ( $query_results['objects'] as $object ) {
			$data      = $this->prepare_item_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$page      = (int) $query_args['paged'];
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

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
	 * Prepare a report object for serialization.
	 *
	 * @param  WC_Product      $product  Report data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $product, $request ) {
		$data = array(
			'id'             => $product->get_id(),
			'parent_id'      => $product->get_parent_id(),
			'name'           => $product->get_name(),
			'sku'            => $product->get_sku(),
			'stock_status'   => $product->get_stock_status(),
			'stock_quantity' => (float) $product->get_stock_quantity(),
			'manage_stock'   => $product->get_manage_stock(),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $product ) );

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Product       $product   The original product object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_stock', $response, $product, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param  WC_Product $product Object data.
	 * @return array
	 */
	protected function prepare_links( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			$links = array(
				'product' => array(
					'href' => rest_url( sprintf( '/%s/products/%d/variations/%d', $this->namespace, $product->get_parent_id(), $product->get_id() ) ),
				),
				'parent'  => array(
					'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product->get_parent_id() ) ),
				),
			);
		} elseif ( $product->get_parent_id() ) {
			$links = array(
				'product' => array(
					'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product->get_id() ) ),
				),
				'parent'  => array(
					'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product->get_parent_id() ) ),
				),
			);
		} else {
			$links = array(
				'product' => array(
					'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product->get_id() ) ),
				),
			);
		}

		return $links;
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_stock',
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce-admin' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id'      => array(
					'description' => __( 'Product parent ID.', 'woocommerce-admin' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'           => array(
					'description' => __( 'Product name.', 'woocommerce-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'sku'            => array(
					'description' => __( 'Unique identifier.', 'woocommerce-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'stock_status'   => array(
					'description' => __( 'Stock status.', 'woocommerce-admin' ),
					'type'        => 'string',
					'enum'        => array_keys( wc_get_product_stock_status_options() ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'stock_quantity' => array(
					'description' => __( 'Stock quantity.', 'woocommerce-admin' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'manage_stock'   => array(
					'description' => __( 'Manage stock.', 'woocommerce-admin' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                   = array();
		$params['context']        = $this->get_context_param( array( 'default' => 'view' ) );
		$params['page']           = array(
			'description'       => __( 'Current page of the collection.', 'woocommerce-admin' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page']       = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce-admin' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['exclude']        = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'woocommerce-admin' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['include']        = array(
			'description'       => __( 'Limit result set to specific ids.', 'woocommerce-admin' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['offset']         = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'woocommerce-admin' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']          = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce-admin' ),
			'type'              => 'string',
			'default'           => 'asc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby']        = array(
			'description'       => __( 'Sort collection by object attribute.', 'woocommerce-admin' ),
			'type'              => 'string',
			'default'           => 'stock_status',
			'enum'              => array(
				'stock_status',
				'stock_quantity',
				'date',
				'id',
				'include',
				'title',
				'sku',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['parent']         = array(
			'description'       => __( 'Limit result set to those of particular parent IDs.', 'woocommerce-admin' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => 'wp_parse_id_list',
			'default'           => array(),
		);
		$params['parent_exclude'] = array(
			'description'       => __( 'Limit result set to all items except those of a particular parent ID.', 'woocommerce-admin' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => 'wp_parse_id_list',
			'default'           => array(),
		);
		$params['type']           = array(
			'description' => __( 'Limit result set to items assigned a stock report type.', 'woocommerce-admin' ),
			'type'        => 'string',
			'default'     => 'all',
			'enum'        => array_merge( array( 'all', 'lowstock' ), array_keys( wc_get_product_stock_status_options() ) ),
		);

		return $params;
	}
}
