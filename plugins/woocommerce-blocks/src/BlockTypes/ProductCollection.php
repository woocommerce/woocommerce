<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Query;

/**
 * ProductCollection class.
 */
class ProductCollection extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-collection';

	/**
	 * All query args from WP_Query.
	 *
	 * @var array
	 */
	protected $valid_query_vars;

	/**
	 * Orderby options not natively supported by WordPress REST API
	 *
	 * @var array
	 */
	protected $custom_order_opts = array( 'popularity', 'rating' );

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 * - Hook into pre_render_block to update the query.
	 */
	protected function initialize() {
		parent::initialize();
		// Update query for frontend rendering.
		add_filter(
			'query_loop_block_query_vars',
			array( $this, 'build_query' ),
			10,
			2
		);

		// Update the query for Editor.
		add_filter( 'rest_product_query', array( $this, 'update_rest_query' ), 10, 2 );

		// Extend allowed `collection_params` for the REST API.
		add_filter( 'rest_product_collection_params', array( $this, 'extend_rest_query_allowed_params' ), 10, 1 );
	}

	/**
	 * Update the query for the product query block in Editor.
	 *
	 * @param array           $args    Query args.
	 * @param WP_REST_Request $request Request.
	 */
	public function update_rest_query( $args, $request ): array {
		// Only update the query if this is a product collection block.
		$is_product_collection_block = $request->get_param( 'isProductCollectionBlock' );
		if ( ! $is_product_collection_block ) {
			return $args;
		}

		$orderby = $request->get_param( 'orderby' );
		$on_sale = $request->get_param( 'woocommerceOnSale' ) === 'true';

		$orderby_query = $orderby ? $this->get_custom_orderby_query( $orderby ) : [];
		$on_sale_query = $this->get_on_sale_products_query( $on_sale );

		return array_merge( $args, $orderby_query, $on_sale_query );
	}

	/**
	 * Extends allowed `collection_params` for the REST API
	 *
	 * By itself, the REST API doesn't accept custom `orderby` values,
	 * even if they are supported by a custom post type.
	 *
	 * @param array $params  A list of allowed `orderby` values.
	 *
	 * @return array
	 */
	public function extend_rest_query_allowed_params( $params ) {
		$original_enum             = isset( $params['orderby']['enum'] ) ? $params['orderby']['enum'] : array();
		$params['orderby']['enum'] = array_unique( array_merge( $original_enum, $this->custom_order_opts ) );
		return $params;
	}

	/**
	 * Return a custom query based on attributes, filters and global WP_Query.
	 *
	 * @param WP_Query $query The WordPress Query.
	 * @param WP_Block $block The block being rendered.
	 *
	 * @return array
	 */
	public function build_query( $query, $block ) {
		// If not in context of product collection block, return the query as is.
		$is_product_collection_block = $block->context['query']['isProductCollectionBlock'] ?? false;
		if ( ! $is_product_collection_block ) {
			return $query;
		}

		$common_query_values = array(
			'meta_query'     => array(),
			'posts_per_page' => $query['posts_per_page'],
			'orderby'        => $query['orderby'],
			'order'          => $query['order'],
			'offset'         => $query['offset'],
			'post__in'       => array(),
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'tax_query'      => array(),
		);

		$is_on_sale = $block->context['query']['woocommerceOnSale'] ?? false;

		$merged_query = $this->merge_queries(
			$common_query_values,
			$this->get_custom_orderby_query( $query['orderby'] ),
			$this->get_on_sale_products_query( $is_on_sale )
		);

		return $merged_query;
	}

	/**
	 * Merge in the first parameter the keys "post_in", "meta_query" and "tax_query" of the second parameter.
	 *
	 * @param array[] ...$queries Query arrays to be merged.
	 * @return array
	 */
	private function merge_queries( ...$queries ) {
		$merged_query = array_reduce(
			$queries,
			function( $acc, $query ) {
				if ( ! is_array( $query ) ) {
					return $acc;
				}
				// If the $query doesn't contain any valid query keys, we unpack/spread it then merge.
				if ( empty( array_intersect( $this->get_valid_query_vars(), array_keys( $query ) ) ) ) {
					return $this->merge_queries( $acc, ...array_values( $query ) );
				}
				return $this->array_merge_recursive_replace_non_array_properties( $acc, $query );
			},
			array()
		);

		/**
		 * If there are duplicated items in post__in, it means that we need to
		 * use the intersection of the results, which in this case, are the
		 * duplicated items.
		 */
		if (
			! empty( $merged_query['post__in'] ) &&
			count( $merged_query['post__in'] ) > count( array_unique( $merged_query['post__in'] ) )
		) {
			$merged_query['post__in'] = array_unique(
				array_diff(
					$merged_query['post__in'],
					array_unique( $merged_query['post__in'] )
				)
			);
		}

		return $merged_query;
	}

	/**
	 * Return query params to support custom sort values
	 *
	 * @param string $orderby  Sort order option.
	 *
	 * @return array
	 */
	private function get_custom_orderby_query( $orderby ) {
		if ( ! in_array( $orderby, $this->custom_order_opts, true ) ) {
			return array( 'orderby' => $orderby );
		}

		$meta_keys = array(
			'popularity' => 'total_sales',
			'rating'     => '_wc_average_rating',
		);

		return array(
			'meta_key' => $meta_keys[ $orderby ],
			'orderby'  => 'meta_value_num',
		);
	}

	/**
	 * Return a query for on sale products.
	 *
	 * @param bool $is_on_sale Whether to query for on sale products.
	 *
	 * @return array
	 */
	private function get_on_sale_products_query( $is_on_sale ) {
		if ( ! $is_on_sale ) {
			return array();
		}

		return array(
			'post__in' => wc_get_product_ids_on_sale(),
		);
	}

	/**
	 * Return or initialize $valid_query_vars.
	 *
	 * @return array
	 */
	private function get_valid_query_vars() {
		if ( ! empty( $this->valid_query_vars ) ) {
			return $this->valid_query_vars;
		}

		$valid_query_vars       = array_keys( ( new WP_Query() )->fill_query_vars( array() ) );
		$this->valid_query_vars = array_merge(
			$valid_query_vars,
			// fill_query_vars doesn't include these vars so we need to add them manually.
			array(
				'date_query',
				'exact',
				'ignore_sticky_posts',
				'lazy_load_term_meta',
				'meta_compare_key',
				'meta_compare',
				'meta_query',
				'meta_type_key',
				'meta_type',
				'nopaging',
				'offset',
				'order',
				'orderby',
				'page',
				'post_type',
				'posts_per_page',
				'suppress_filters',
				'tax_query',
			)
		);

		return $this->valid_query_vars;
	}

	/**
	 * Merge two array recursively but replace the non-array values instead of
	 * merging them. The merging strategy:
	 *
	 * - If keys from merge array doesn't exist in the base array, create them.
	 * - For array items with numeric keys, we merge them as normal.
	 * - For array items with string keys:
	 *
	 *   - If the value isn't array, we'll use the value comming from the merge array.
	 *     $base = ['orderby' => 'date']
	 *     $new  = ['orderby' => 'meta_value_num']
	 *     Result: ['orderby' => 'meta_value_num']
	 *
	 *   - If the value is array, we'll use recursion to merge each key.
	 *     $base = ['meta_query' => [
	 *       [
	 *         'key'     => '_stock_status',
	 *         'compare' => 'IN'
	 *         'value'   =>  ['instock', 'onbackorder']
	 *       ]
	 *     ]]
	 *     $new  = ['meta_query' => [
	 *       [
	 *         'relation' => 'AND',
	 *         [...<max_price_query>],
	 *         [...<min_price_query>],
	 *       ]
	 *     ]]
	 *     Result: ['meta_query' => [
	 *       [
	 *         'key'     => '_stock_status',
	 *         'compare' => 'IN'
	 *         'value'   =>  ['instock', 'onbackorder']
	 *       ],
	 *       [
	 *         'relation' => 'AND',
	 *         [...<max_price_query>],
	 *         [...<min_price_query>],
	 *       ]
	 *     ]]
	 *
	 *     $base = ['post__in' => [1, 2, 3, 4, 5]]
	 *     $new  = ['post__in' => [3, 4, 5, 6, 7]]
	 *     Result: ['post__in' => [1, 2, 3, 4, 5, 3, 4, 5, 6, 7]]
	 *
	 * @param array $base First array.
	 * @param array $new  Second array.
	 */
	private function array_merge_recursive_replace_non_array_properties( $base, $new ) {
		foreach ( $new as $key => $value ) {
			if ( is_numeric( $key ) ) {
				$base[] = $value;
			} else {
				if ( is_array( $value ) ) {
					if ( ! isset( $base[ $key ] ) ) {
						$base[ $key ] = array();
					}
					$base[ $key ] = $this->array_merge_recursive_replace_non_array_properties( $base[ $key ], $value );
				} else {
					$base[ $key ] = $value;
				}
			}
		}

		return $base;
	}
}
