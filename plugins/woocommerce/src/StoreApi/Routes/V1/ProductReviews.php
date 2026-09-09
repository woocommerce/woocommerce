<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use WP_Comment_Query;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\StoreApi\Utilities\Pagination;

/**
 * ProductReviews class.
 */
class ProductReviews extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product-reviews';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'product-review';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/products/reviews';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => $this->get_collection_params(),
				'allow_batch'         => [ 'v1' => true ],
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get a collection of reviews.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$prepared_args = array(
			'type'          => 'review',
			'status'        => 'approve',
			'no_found_rows' => false,
			'offset'        => $request['offset'],
			'order'         => $request['order'],
			'number'        => $request['per_page'],
			'post__in'      => $request['product_id'],
			// Exclude reviews of non-published products.
			'post_status'   => ProductStatus::PUBLISH,
		);

		/**
		 * Map category id to list of product ids.
		 */
		if ( ! empty( $request['category_id'] ) ) {
			$category_ids = $request['category_id'];
			$child_ids    = [];
			foreach ( $category_ids as $category_id ) {
				$child_ids = array_merge( $child_ids, get_term_children( $category_id, 'product_cat' ) );
			}
			$category_ids              = array_unique( array_merge( $category_ids, $child_ids ) );
			$product_ids               = get_objects_in_term( $category_ids, 'product_cat' );
			$prepared_args['post__in'] = isset( $prepared_args['post__in'] ) ? array_merge( $prepared_args['post__in'], $product_ids ) : $product_ids;
		}

		if ( 'rating' === $request['orderby'] ) {
			$prepared_args['meta_query'] = array( // phpcs:ignore
				'relation' => 'OR',
				array(
					'key'     => 'rating',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'rating',
					'compare' => 'NOT EXISTS',
				),
			);
		}
		$prepared_args['orderby'] = $this->normalize_query_param( $request['orderby'] );

		if ( empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $prepared_args['number'] * ( absint( $request['page'] ) - 1 );
		}

		$unlocked_product_ids               = $this->get_unlocked_password_protected_product_ids( $prepared_args['post__in'] ?? array() );
		$exclude_password_protected_reviews = function ( $clauses ) use ( $unlocked_product_ids ) {
			return $this->exclude_password_protected_product_reviews( $clauses, $unlocked_product_ids );
		};

		$query_result  = array();
		$total_reviews = 0;
		$max_pages     = 0;

		add_filter( 'comments_clauses', $exclude_password_protected_reviews );
		try {
			$query        = new WP_Comment_Query();
			$query_result = $query->query( $prepared_args );

			$total_reviews = (int) $query->found_comments;
			$max_pages     = (int) $query->max_num_pages;

			if ( $total_reviews < 1 ) {
				// Out-of-bounds, run the query again without LIMIT for total count.
				unset( $prepared_args['number'], $prepared_args['offset'] );

				$query                  = new WP_Comment_Query();
				$prepared_args['count'] = true;

				$total_reviews = $query->query( $prepared_args );
				$max_pages     = $request['per_page'] ? ceil( $total_reviews / $request['per_page'] ) : 1;
			}
		} finally {
			remove_filter( 'comments_clauses', $exclude_password_protected_reviews );
		}

		$response_objects = array();
		foreach ( $query_result as $review ) {
			$data               = $this->prepare_item_for_response( $review, $request );
			$response_objects[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $response_objects );
		$response = ( new Pagination() )->add_headers( $response, $request, $total_reviews, $max_pages );

		return $response;
	}

	/**
	 * Restrict the comment query to products the visitor can access.
	 *
	 * WP_Comment_Query already joins posts because of post_status. Filter on that
	 * join instead of building a post__not_in list of every protected product.
	 *
	 * @param array|mixed $clauses              Comment query clauses from comments_clauses.
	 * @param int[]       $unlocked_product_ids Password-protected product IDs the visitor has unlocked.
	 * @return array|mixed
	 */
	private function exclude_password_protected_product_reviews( $clauses, $unlocked_product_ids ) {
		global $wpdb;

		if ( ! is_array( $clauses ) ) {
			return $clauses;
		}

		$where = " {$wpdb->posts}.post_password = '' ";
		if ( ! empty( $unlocked_product_ids ) ) {
			$ids   = implode( ',', array_map( 'absint', $unlocked_product_ids ) );
			$where = " ( {$wpdb->posts}.post_password = '' OR {$wpdb->posts}.ID IN ({$ids}) ) ";
		}

		$clauses['where']  = is_string( $clauses['where'] ?? null ) ? $clauses['where'] : '';
		$clauses['where'] .= ( trim( $clauses['where'] ) ? ' AND ' : '' ) . $where;

		return $clauses;
	}

	/**
	 * Password-protected product IDs the visitor has unlocked.
	 *
	 * @param int[] $candidate_product_ids Product IDs already limiting the review query, if any.
	 * @return int[]
	 */
	private function get_unlocked_password_protected_product_ids( $candidate_product_ids = array() ) {
		// Return early if the visitor has not submitted the password form and there is no filter in `post_password_required`.
		if ( ( ! defined( 'COOKIEHASH' ) || ! isset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] ) ) && ! has_filter( 'post_password_required' ) ) {
			return array();
		}

		$query_args = array(
			'post_type'              => 'product',
			'post_status'            => ProductStatus::PUBLISH,
			'has_password'           => true,
			'comment_count'          => array(
				'value'   => 0,
				'compare' => '!=',
			),
			'posts_per_page'         => -1,
			'orderby'                => 'none',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $candidate_product_ids ) ) {
			$query_args['post__in'] = $candidate_product_ids;
		}

		$unlocked_ids = array();
		foreach ( get_posts( $query_args ) as $product ) {
			if ( $product instanceof \WP_Post && ! post_password_required( $product ) ) {
				$unlocked_ids[] = (int) $product->ID;
			}
		}

		return $unlocked_ids;
	}

	/**
	 * Prepends internal property prefix to query parameters to match our response fields.
	 *
	 * @param string $query_param Query parameter.
	 * @return string
	 */
	protected function normalize_query_param( $query_param ) {
		$prefix = 'comment_';

		switch ( $query_param ) {
			case 'id':
				$normalized = $prefix . 'ID';
				break;
			case 'product':
				$normalized = $prefix . 'post_ID';
				break;
			case 'rating':
				$normalized = 'meta_value_num';
				break;
			default:
				$normalized = $prefix . $query_param;
				break;
		}

		return $normalized;
	}

	/**
	 * Get the query params for collections of products.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['page'] = array(
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

		$params['offset'] = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort collection by object attribute.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => array(
				'date',
				'date_gmt',
				'id',
				'rating',
				'product',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['category_id'] = array(
			'description'       => __( 'Limit result set to reviews from specific category IDs.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['product_id'] = array(
			'description'       => __( 'Limit result set to reviews from specific product IDs.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
