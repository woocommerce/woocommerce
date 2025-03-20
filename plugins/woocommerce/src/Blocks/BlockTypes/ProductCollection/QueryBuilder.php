<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\AttributeFilter;
use Automattic\WooCommerce\Blocks\BlockTypes\PriceFilter;
use Automattic\WooCommerce\Blocks\BlockTypes\RatingFilter;
use Automattic\WooCommerce\Blocks\BlockTypes\StockFilter;
use WP_Query;
use WC_Tax;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * QueryBuilder class.
 * Responsible for constructing and modifying product queries.
 */
class QueryBuilder {

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
	protected $custom_order_opts = array( 'popularity', 'rating', 'post__in', 'price', 'sales', 'menu_order', 'random' );

	/**
	 * All the query args related to the filter by attributes block.
	 *
	 * @var array
	 */
	protected $attributes_filter_query_args = array();

	/**
	 * Collection handler store.
	 *
	 * @var array
	 */
	protected $collection_handler_store = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->valid_query_vars = $this->get_valid_query_vars();
		add_filter( 'posts_clauses', array( $this, 'add_price_range_filter_posts_clauses' ), 10, 2 );
	}

	/**
	 * Set the collection handler store.
	 *
	 * @param array $collection_handler_store The collection handler store containing registered collection handlers.
	 */
	public function set_collection_handler_store( $collection_handler_store ) {
		$this->collection_handler_store = $collection_handler_store;
	}

	/**
	 * Set collection handler.
	 *
	 * @param string $collection_name The name of the custom collection.
	 * @param array  $handlers        Collection handlers.
	 */
	public function set_collection_handler( $collection_name, $handlers ) {
		$this->collection_handler_store[ $collection_name ] = $handlers;
	}

	/**
	 * Set attributes filter query args.
	 *
	 * @param array $args The attributes filter query arguments.
	 */
	public function set_attributes_filter_query_args( $args ) {
		$this->attributes_filter_query_args = $args;
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
				'isProductCollection',
				'priceRange',
			)
		);

		return $this->valid_query_vars;
	}

	/**
	 * Get custom order options.
	 *
	 * @return array
	 */
	public function get_custom_order_opts() {
		return $this->custom_order_opts;
	}

	/**
	 * Get the final query arguments for the frontend.
	 *
	 * @param array $collection_args            Any special arguments that should change the behavior of the query.
	 * @param array $query                      The query arguments.
	 * @param int   $page                       The page number.
	 * @param bool  $is_exclude_applied_filters Whether to exclude the applied filters or not.
	 */
	public function get_final_frontend_query( $collection_args, $query, $page = 1, $is_exclude_applied_filters = false ) {
		$product_ids = $query['post__in'] ?? array();
		$offset      = $query['offset'] ?? 0;
		$per_page    = $query['perPage'] ?? 9;
		$order       = $query['order'] ?? 'asc';
		$search      = $query['search'] ?? '';

		$common_query_values = array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(),
			'posts_per_page' => $per_page,
			'order'          => $order,
			'offset'         => ( $per_page * ( $page - 1 ) ) + $offset,
			'post__in'       => $product_ids,
			'post_status'    => 'publish',
			'post_type'      => 'product',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(),
			'paged'          => $page,
			's'              => $search,
		);

		$is_on_sale          = $query['woocommerceOnSale'] ?? false;
		$order_by            = $query['orderBy'] ?? '';
		$stock_status        = $query['woocommerceStockStatus'] ?? array_keys( wc_get_product_stock_status_options() );
		$product_attributes  = $query['woocommerceAttributes'] ?? array();
		$taxonomies_query    = $this->get_filter_by_taxonomies_query( $query['tax_query'] ?? array() );
		$handpicked_products = $query['woocommerceHandPickedProducts'] ?? array();
		$time_frame          = $query['timeFrame'] ?? null;
		$price_range         = $query['priceRange'] ?? null;
		$featured            = $query['featured'] ?? false;

		// Allow collections to modify the collection arguments passed to the query builder.
		$handlers = $this->collection_handler_store[ $collection_args['name'] ] ?? null;
		if ( isset( $handlers['frontend_args'] ) ) {
			$collection_args = call_user_func( $handlers['frontend_args'], $collection_args, $query );
		}

		$final_query = $this->get_final_query_args(
			$collection_args,
			$common_query_values,
			array(
				'on_sale'             => $is_on_sale,
				'stock_status'        => $stock_status,
				'orderby'             => $order_by,
				'product_attributes'  => $product_attributes,
				'taxonomies_query'    => $taxonomies_query,
				'handpicked_products' => $handpicked_products,
				'featured'            => $featured,
				'timeFrame'           => $time_frame,
				'priceRange'          => $price_range,
			),
			$is_exclude_applied_filters
		);

		return $final_query;
	}

	/**
	 * Return a query to filter products by taxonomies (product categories, product tags, etc.)
	 *
	 * For example:
	 * User could provide "Product Categories" using "Filters" ToolsPanel available in Inspector Controls.
	 * We use this function to extract its query from $tax_query.
	 *
	 * For example, this is how the query for product categories will look like in $tax_query array:
	 * Array
	 *    (
	 *        [taxonomy] => product_cat
	 *        [terms] => Array
	 *            (
	 *                [0] => 36
	 *            )
	 *    )
	 *
	 * For product tags, taxonomy would be "product_tag"
	 *
	 * @param array $tax_query Query to filter products by taxonomies.
	 * @return array Query to filter products by taxonomies.
	 */
	private function get_filter_by_taxonomies_query( $tax_query ): array {
		if ( ! is_array( $tax_query ) ) {
			return array();
		}

		/**
		 * Get an array of taxonomy names associated with the "product" post type because
		 * we also want to include custom taxonomies associated with the "product" post type.
		 */
		$product_taxonomies = array_diff( get_object_taxonomies( 'product', 'names' ), array( 'product_visibility', 'product_shipping_class' ) );
		$result             = array_filter(
			$tax_query,
			function ( $item ) use ( $product_taxonomies ) {
				return isset( $item['taxonomy'] ) && in_array( $item['taxonomy'], $product_taxonomies, true );
			}
		);

		// phpcs:ignore WordPress.DB.SlowDBQuery
		return ! empty( $result ) ? array( 'tax_query' => $result ) : array();
	}

	/**
	 * Get final query args based on provided values
	 *
	 * @param array $collection_args            Any special arguments that should change the behavior of the query.
	 * @param array $common_query_values        Common query values.
	 * @param array $query                      Query from block context.
	 * @param bool  $is_exclude_applied_filters Whether to exclude the applied filters or not.
	 */
	public function get_final_query_args(
		$collection_args,
		$common_query_values,
		$query,
		$is_exclude_applied_filters = false
	) {
		$orderby_query    = $query['orderby'] ? $this->get_custom_orderby_query( $query['orderby'] ) : array();
		$on_sale_query    = $this->get_on_sale_products_query( $query['on_sale'] );
		$stock_query      = $this->get_stock_status_query( $query['stock_status'] );
		$visibility_query = is_array( $query['stock_status'] ) ? $this->get_product_visibility_query( $stock_query, $query['stock_status'] ) : array();
		$featured_query   = $this->get_featured_query( $query['featured'] ?? false );
		$attributes_query = $this->get_product_attributes_query( $query['product_attributes'] );
		$taxonomies_query = $query['taxonomies_query'] ?? array();
		$tax_query        = $this->merge_tax_queries( $visibility_query, $attributes_query, $taxonomies_query, $featured_query );
		$date_query       = $this->get_date_query( $query['timeFrame'] ?? array() );
		$price_query_args = $this->get_price_range_query_args( $query['priceRange'] ?? array() );
		$handpicked_query = $this->get_handpicked_query( $query['handpicked_products'] ?? false );

		// We exclude applied filters to generate product ids for the filter blocks.
		$applied_filters_query = $is_exclude_applied_filters ? array() : $this->get_queries_by_applied_filters();

		// Allow collections to provide their own query parameters.
		$handlers = $this->collection_handler_store[ $collection_args['name'] ] ?? null;
		if ( isset( $handlers['build_query'] ) ) {
			$collection_query = call_user_func(
				$handlers['build_query'],
				$collection_args,
				$common_query_values,
				$query,
				$is_exclude_applied_filters
			);
		} else {
			$collection_query = array();
		}

		return $this->merge_queries(
			$common_query_values,
			$orderby_query,
			$on_sale_query,
			$stock_query,
			$tax_query,
			$applied_filters_query,
			$date_query,
			$price_query_args,
			$handpicked_query,
			$collection_query
		);
	}

	/**
	 * Get query args for preview mode. These query args will be used with WP_Query to fetch the products.
	 *
	 * @param array           $collection_args Any collection-specific arguments.
	 * @param array           $args            Query args.
	 * @param WP_REST_Request $request         Request.
	 */
	public function get_preview_query_args( $collection_args, $args, $request ) {
		$collection_query = array();

		// Allow collections to override the preview mode behavior.
		$handlers = $this->collection_handler_store[ $collection_args['name'] ] ?? null;
		if ( isset( $handlers['preview_query'] ) ) {
			$collection_query = call_user_func( $handlers['preview_query'], $collection_args, $args, $request );
		}
		$orderby_query = $args['orderby'] ? $this->get_custom_orderby_query( $args['orderby'] ) : array();

		$args = $this->merge_queries( $args, $orderby_query, $collection_query );
		return $args;
	}

	/**
	 * Return a query for products depending on their stock status.
	 *
	 * @param array $stock_statuses An array of acceptable stock statuses.
	 * @return array
	 */
	private function get_stock_status_query( $stock_statuses ) {
		if ( ! is_array( $stock_statuses ) ) {
			return array();
		}

		$stock_status_options = array_keys( wc_get_product_stock_status_options() );

		/**
		 * If all available stock status are selected, we don't need to add the
		 * meta query for stock status.
		 */
		if (
			count( $stock_statuses ) === count( $stock_status_options ) &&
			array_diff( $stock_statuses, $stock_status_options ) === array_diff( $stock_status_options, $stock_statuses )
		) {
			return array();
		}

		/**
		 * If all stock statuses are selected except 'outofstock', we use the
		 * product visibility query to filter out out of stock products.
		 *
		 * @see get_product_visibility_query()
		 */
		$diff = array_diff( $stock_status_options, $stock_statuses );
		if ( count( $diff ) === 1 && in_array( ProductStockStatus::OUT_OF_STOCK, $diff, true ) ) {
			return array();
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				array(
					'key'     => '_stock_status',
					'value'   => (array) $stock_statuses,
					'compare' => 'IN',
				),
			),
		);
	}

	/**
	 * Merge tax_queries from various queries.
	 *
	 * @param array ...$queries Query arrays to be merged.
	 * @return array
	 */
	private function merge_tax_queries( ...$queries ) {
		$tax_query = array();
		foreach ( $queries as $query ) {
			if ( ! empty( $query['tax_query'] ) ) {
				$tax_query = array_merge( $tax_query, $query['tax_query'] );
			}
		}
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		return array( 'tax_query' => $tax_query );
	}

	/**
	 * Return the `tax_query` for the requested attributes
	 *
	 * @param array $attributes  Attributes and their terms.
	 *
	 * @return array
	 */
	private function get_product_attributes_query( $attributes = array() ) {
		if ( empty( $attributes ) ) {
			return array();
		}

		$grouped_attributes = array_reduce(
			$attributes,
			function ( $carry, $item ) {
				$taxonomy = sanitize_title( $item['taxonomy'] );

				if ( ! key_exists( $taxonomy, $carry ) ) {
					$carry[ $taxonomy ] = array(
						'field'    => 'term_id',
						'operator' => 'IN',
						'taxonomy' => $taxonomy,
						'terms'    => array( $item['termId'] ),
					);
				} else {
					$carry[ $taxonomy ]['terms'][] = $item['termId'];
				}

				return $carry;
			},
			array()
		);

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query' => array_values( $grouped_attributes ),
		);
	}

	/**
	 * Generates a tax query to filter products based on their "featured" status.
	 * If the `$featured` parameter is true, the function will return a tax query
	 * that filters products to only those marked as featured.
	 * If `$featured` is false, an empty array is returned, meaning no filtering will be applied.
	 *
	 * @param bool $featured A flag indicating whether to filter products based on featured status.
	 *
	 * @return array A tax query for fetching featured products if `$featured` is true; otherwise, an empty array.
	 */
	private function get_featured_query( $featured ) {
		if ( true !== $featured && 'true' !== $featured ) {
			return array();
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query' => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
					'operator' => 'IN',
				),
			),
		);
	}

	/**
	 * Return a query that filters products by price.
	 *
	 * @return array
	 */
	private function get_filter_by_price_query() {
		$min_price = get_query_var( PriceFilter::MIN_PRICE_QUERY_VAR );
		$max_price = get_query_var( PriceFilter::MAX_PRICE_QUERY_VAR );

		$max_price_query = empty( $max_price ) ? array() : array(
			'key'     => '_price',
			'value'   => $max_price,
			'compare' => '<=',
			'type'    => 'numeric',
		);

		$min_price_query = empty( $min_price ) ? array() : array(
			'key'     => '_price',
			'value'   => $min_price,
			'compare' => '>=',
			'type'    => 'numeric',
		);

		if ( empty( $min_price_query ) && empty( $max_price_query ) ) {
			return array();
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				array(
					'relation' => 'AND',
					$max_price_query,
					$min_price_query,
				),
			),
		);
	}

	/**
	 * Return a query that filters products by attributes.
	 *
	 * @return array
	 */
	private function get_filter_by_attributes_query() {
		$attributes_filter_query_args = $this->get_filter_by_attributes_query_vars();

		$queries = array_reduce(
			$attributes_filter_query_args,
			function ( $acc, $query_args ) {
				$attribute_name       = $query_args['filter'];
				$attribute_query_type = $query_args['query_type'];

				$attribute_value = get_query_var( $attribute_name );
				$attribute_query = get_query_var( $attribute_query_type );

				if ( empty( $attribute_value ) ) {
					return $acc;
				}

				// It is necessary explode the value because $attribute_value can be a string with multiple values (e.g. "red,blue").
				$attribute_value = explode( ',', $attribute_value );

				$acc[] = array(
					'taxonomy' => str_replace( AttributeFilter::FILTER_QUERY_VAR_PREFIX, 'pa_', $attribute_name ),
					'field'    => 'slug',
					'terms'    => $attribute_value,
					'operator' => 'and' === $attribute_query ? 'AND' : 'IN',
				);

				return $acc;
			},
			array()
		);

		if ( empty( $queries ) ) {
			return array();
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery
			'tax_query' => array(
				array(
					'relation' => 'AND',
					$queries,
				),
			),
		);
	}

	/**
	 * Get all the query args related to the filter by attributes block.
	 *
	 * @return array
	 * [color] => Array
	 *   (
	 *        [filter] => filter_color
	 *        [query_type] => query_type_color
	 *    )
	 *
	 * [size] => Array
	 *    (
	 *        [filter] => filter_size
	 *        [query_type] => query_type_size
	 *    )
	 * )
	 */
	private function get_filter_by_attributes_query_vars() {
		if ( ! empty( $this->attributes_filter_query_args ) ) {
			return $this->attributes_filter_query_args;
		}

		$this->attributes_filter_query_args = array_reduce(
			wc_get_attribute_taxonomies(),
			function ( $acc, $attribute ) {
				$acc[ $attribute->attribute_name ] = array(
					'filter'     => AttributeFilter::FILTER_QUERY_VAR_PREFIX . $attribute->attribute_name,
					'query_type' => AttributeFilter::QUERY_TYPE_QUERY_VAR_PREFIX . $attribute->attribute_name,
				);
				return $acc;
			},
			array()
		);

		return $this->attributes_filter_query_args;
	}

	/**
	 * Return a query that filters products by stock status.
	 *
	 * @return array
	 */
	private function get_filter_by_stock_status_query() {
		$filter_stock_status_values = get_query_var( StockFilter::STOCK_STATUS_QUERY_VAR );

		if ( empty( $filter_stock_status_values ) ) {
			return array();
		}

		$filtered_stock_status_values = array_filter(
			explode( ',', $filter_stock_status_values ),
			function ( $stock_status ) {
				return in_array( $stock_status, StockFilter::get_stock_status_query_var_values(), true );
			}
		);

		if ( empty( $filtered_stock_status_values ) ) {
			return array();
		}

		return array(
			// Ignoring the warning of not using meta queries.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				array(
					'key'      => '_stock_status',
					'value'    => $filtered_stock_status_values,
					'operator' => 'IN',
				),
			),
		);
	}

	/**
	 * Return a query that filters products by rating.
	 *
	 * @return array
	 */
	private function get_filter_by_rating_query() {
		$filter_rating_values = get_query_var( RatingFilter::RATING_QUERY_VAR );
		if ( empty( $filter_rating_values ) ) {
			return array();
		}

		$parsed_filter_rating_values = explode( ',', $filter_rating_values );
		$product_visibility_terms    = wc_get_product_visibility_term_ids();

		if ( empty( $parsed_filter_rating_values ) || empty( $product_visibility_terms ) ) {
			return array();
		}

		$rating_terms = array_map(
			function ( $rating ) use ( $product_visibility_terms ) {
				return $product_visibility_terms[ 'rated-' . $rating ];
			},
			$parsed_filter_rating_values
		);

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery
			'tax_query' => array(
				array(
					'field'         => 'term_taxonomy_id',
					'taxonomy'      => 'product_visibility',
					'terms'         => $rating_terms,
					'operator'      => 'IN',
					'rating_filter' => true,
				),
			),
		);
	}

	/**
	 * Merge two array recursively but replace the non-array values instead of
	 * merging them. The merging strategy:
	 *
	 * - If keys from merge array doesn't exist in the base array, create them.
	 * - For array items with numeric keys, we merge them as normal.
	 * - For array items with string keys:
	 *
	 *   - If the value isn't array, we'll use the value coming from the merge array.
	 *     $base       = ['orderby' => 'date']
	 *     $new_array  = ['orderby' => 'meta_value_num']
	 *     Result: ['orderby' => 'meta_value_num']
	 *
	 *   - If the value is array, we'll use recursion to merge each key.
	 *     $base       = ['meta_query' => [
	 *       [
	 *         'key'     => '_stock_status',
	 *         'compare' => 'IN'
	 *         'value'   =>  ['instock', 'onbackorder']
	 *       ]
	 *     ]]
	 *     $new_array  = ['meta_query' => [
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
	 *     $base       = ['post__in' => [1, 2, 3, 4, 5]]
	 *     $new_array  = ['post__in' => [3, 4, 5, 6, 7]]
	 *     Result: ['post__in' => [1, 2, 3, 4, 5, 3, 4, 5, 6, 7]]
	 *
	 * @param array $base First array.
	 * @param array $new_array  Second array.
	 */
	private function array_merge_recursive_replace_non_array_properties( $base, $new_array ) {
		foreach ( $new_array as $key => $value ) {
			if ( is_numeric( $key ) ) {
				$base[] = $value;
			} elseif ( is_array( $value ) ) {
				if ( ! isset( $base[ $key ] ) ) {
					$base[ $key ] = array();
				}
				$base[ $key ] = $this->array_merge_recursive_replace_non_array_properties( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}

		return $base;
	}

	/**
	 * Return queries that are generated by query args.
	 *
	 * @return array
	 */
	private function get_queries_by_applied_filters() {
		return array(
			'price_filter'        => $this->get_filter_by_price_query(),
			'attributes_filter'   => $this->get_filter_by_attributes_query(),
			'stock_status_filter' => $this->get_filter_by_stock_status_query(),
			'rating_filter'       => $this->get_filter_by_rating_query(),
		);
	}

	/**
	 * Return a query for product visibility depending on their stock status.
	 *
	 * @param array $stock_query  Stock status query.
	 * @param array $stock_status Selected stock status.
	 *
	 * @return array Tax query for product visibility.
	 */
	private function get_product_visibility_query( $stock_query, $stock_status ) {
		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( is_search() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

		// Hide out of stock products.
		if ( empty( $stock_query ) && ! in_array( ProductStockStatus::OUT_OF_STOCK, $stock_status, true ) ) {
			$product_visibility_not_in[] = $product_visibility_terms[ ProductStockStatus::OUT_OF_STOCK ];
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query' => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_not_in,
					'operator' => 'NOT IN',
				),
			),
		);
	}

	/**
	 * Constructs a date query for product filtering based on a specified time frame.
	 *
	 * @param array $time_frame {
	 *     Associative array with 'operator' (in or not-in) and 'value' (date string).
	 *
	 *     @type string $operator Determines the inclusion or exclusion of the date range.
	 *     @type string $value    The date around which the range is applied.
	 * }
	 * @return array Date query array; empty if parameters are invalid.
	 */
	private function get_date_query( array $time_frame ): array {
		// Validate time_frame elements.
		if ( empty( $time_frame['operator'] ) || empty( $time_frame['value'] ) ) {
			return array();
		}

		// Determine the query operator based on the 'operator' value.
		$query_operator = 'in' === $time_frame['operator'] ? 'after' : 'before';

		// Construct and return the date query.
		return array(
			'date_query' => array(
				array(
					'column'        => 'post_date_gmt',
					$query_operator => $time_frame['value'],
					'inclusive'     => true,
				),
			),
		);
	}

	/**
	 * Get query arguments for price range filter.
	 * We are adding these extra query arguments to be used in `posts_clauses`
	 * because there are 2 special edge cases we wanna handle for Price range filter:
	 * Case 1: Prices excluding tax are displayed including tax
	 * Case 2: Prices including tax are displayed excluding tax
	 *
	 * Both of these cases require us to modify SQL query to get the correct results.
	 *
	 * See add_price_range_filter_posts_clauses function in this file for more details.
	 *
	 * @param array $price_range Price range with min and max values.
	 * @return array Query arguments.
	 */
	public function get_price_range_query_args( $price_range ) {
		if ( empty( $price_range ) ) {
			return array();
		}

		return array(
			'isProductCollection' => true,
			'priceRange'          => $price_range,
		);
	}

	/**
	 * Add the `posts_clauses` filter to the main query.
	 *
	 * @param array    $clauses The query clauses.
	 * @param WP_Query $query   The WP_Query instance.
	 */
	public function add_price_range_filter_posts_clauses( $clauses, $query ) {
		$query_vars                  = $query->query_vars;
		$is_product_collection_block = $query_vars['isProductCollection'] ?? false;
		if ( ! $is_product_collection_block ) {
			return $clauses;
		}

		$price_range = $query_vars['priceRange'] ?? null;
		if ( empty( $price_range ) ) {
			return $clauses;
		}

		global $wpdb;
		$adjust_for_taxes = $this->should_adjust_price_range_for_taxes();
		$clauses['join']  = $this->append_product_sorting_table_join( $clauses['join'] );

		$min_price = $price_range['min'] ?? null;
		if ( $min_price ) {
			if ( $adjust_for_taxes ) {
				$clauses['where'] .= $this->get_price_filter_query_for_displayed_taxes( $min_price, 'max_price', '>=' );
			} else {
				$clauses['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.max_price >= %f ', $min_price );
			}
		}

		$max_price = $price_range['max'] ?? null;
		if ( $max_price ) {
			if ( $adjust_for_taxes ) {
				$clauses['where'] .= $this->get_price_filter_query_for_displayed_taxes( $max_price, 'min_price', '<=' );
			} else {
				$clauses['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.min_price <= %f ', $max_price );
			}
		}

		return $clauses;
	}

	/**
	 * Get query for price filters when dealing with displayed taxes.
	 *
	 * @param float  $price_filter Price filter to apply.
	 * @param string $column Price being filtered (min or max).
	 * @param string $operator Comparison operator for column.
	 * @return string Constructed query.
	 */
	protected function get_price_filter_query_for_displayed_taxes( $price_filter, $column = 'min_price', $operator = '>=' ) {
		global $wpdb;

		// Select only used tax classes to avoid unwanted calculations.
		$product_tax_classes = $wpdb->get_col( "SELECT DISTINCT tax_class FROM {$wpdb->wc_product_meta_lookup};" );

		if ( empty( $product_tax_classes ) ) {
			return '';
		}

		$or_queries = array();

		// We need to adjust the filter for each possible tax class and combine the queries into one.
		foreach ( $product_tax_classes as $tax_class ) {
			$adjusted_price_filter = $this->adjust_price_filter_for_tax_class( $price_filter, $tax_class );
			$or_queries[]          = $wpdb->prepare(
				'( wc_product_meta_lookup.tax_class = %s AND wc_product_meta_lookup.`' . esc_sql( $column ) . '` ' . esc_sql( $operator ) . ' %f )',
				$tax_class,
				$adjusted_price_filter
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->prepare(
			' AND (
				wc_product_meta_lookup.tax_status = "taxable" AND ( 0=1 OR ' . implode( ' OR ', $or_queries ) . ')
				OR ( wc_product_meta_lookup.tax_status != "taxable" AND wc_product_meta_lookup.`' . esc_sql( $column ) . '` ' . esc_sql( $operator ) . ' %f )
			) ',
			$price_filter
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Adjusts a price filter based on a tax class and whether or not the amount includes or excludes taxes.
	 *
	 * This calculation logic is based on `wc_get_price_excluding_tax` and `wc_get_price_including_tax` in core.
	 *
	 * @param float  $price_filter Price filter amount as entered.
	 * @param string $tax_class Tax class for adjustment.
	 * @return float
	 */
	protected function adjust_price_filter_for_tax_class( $price_filter, $tax_class ) {
		$tax_display    = get_option( 'woocommerce_tax_display_shop' );
		$tax_rates      = WC_Tax::get_rates( $tax_class );
		$base_tax_rates = WC_Tax::get_base_tax_rates( $tax_class );

		// If prices are shown incl. tax, we want to remove the taxes from the filter amount to match prices stored excl. tax.
		if ( 'incl' === $tax_display ) {
			/**
			 * Filters if taxes should be removed from locations outside the store base location.
			 *
			 * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing
			 * with out of base locations. e.g. If a product costs 10 including tax, all users will pay 10
			 * regardless of location and taxes.
			 *
			 * @since 2.6.0
			 *
			 * @internal Matches filter name in WooCommerce core.
			 *
			 * @param boolean $adjust_non_base_location_prices True by default.
			 * @return boolean
			 */
			$taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $price_filter, $base_tax_rates, true ) : WC_Tax::calc_tax( $price_filter, $tax_rates, true );
			return $price_filter - array_sum( $taxes );
		}

		// If prices are shown excl. tax, add taxes to match the prices stored in the DB.
		$taxes = WC_Tax::calc_tax( $price_filter, $tax_rates, false );

		return $price_filter + array_sum( $taxes );
	}

	/**
	 * Determines if price filters need adjustment based on the tax display settings.
	 *
	 * This function checks if there's a discrepancy between how prices are stored in the database
	 * and how they are displayed to the user, specifically with respect to tax inclusion or exclusion.
	 * It returns true if an adjustment is needed, indicating that the price filters should account for this
	 * discrepancy to display accurate prices.
	 *
	 * @return bool True if the price filters need to be adjusted for tax display settings, false otherwise.
	 */
	private function should_adjust_price_range_for_taxes() {
		$display_setting      = get_option( 'woocommerce_tax_display_shop' ); // Tax display setting ('incl' or 'excl').
		$price_storage_method = wc_prices_include_tax() ? 'incl' : 'excl';

		return $display_setting !== $price_storage_method;
	}

	/**
	 * Generates a post__in query to filter products to the set of provided IDs.
	 *
	 * @param int[]|false $handpicked_products The products to filter.
	 *
	 * @return array The post__in query.
	 */
	private function get_handpicked_query( $handpicked_products ) {
		if ( false === $handpicked_products ) {
			return array();
		}

		return array(
			'post__in' => $handpicked_products,
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
	 * Merge in the first parameter the keys "post_in", "meta_query" and "tax_query" of the second parameter.
	 *
	 * @param array[] ...$queries Query arrays to be merged.
	 * @return array
	 */
	private function merge_queries( ...$queries ) {
		// Rather than a simple merge, some query vars should be held aside and merged differently.
		$special_query_vars = array(
			'post__in' => array(),
		);
		$special_query_keys = array_keys( $special_query_vars );

		$merged_query = array_reduce(
			$queries,
			function ( $acc, $query ) use ( $special_query_keys, &$special_query_vars ) {
				if ( ! is_array( $query ) ) {
					return $acc;
				}

				// When the $query has keys but doesn't contain any valid query keys, we unpack/spread it then merge.
				if ( ! empty( $query ) && empty( array_intersect( $this->get_valid_query_vars(), array_keys( $query ) ) ) ) {
					return $this->merge_queries( $acc, ...array_values( $query ) );
				}

				// Pull out the special query vars so we can merge them separately.
				foreach ( $special_query_keys as $query_var ) {
					if ( isset( $query[ $query_var ] ) ) {
						$special_query_vars[ $query_var ][] = $query[ $query_var ];
						unset( $query[ $query_var ] );
					}
				}

				return $this->array_merge_recursive_replace_non_array_properties( $acc, $query );
			},
			array()
		);

		// Perform any necessary special merges.
		$merged_query['post__in'] = $this->merge_post__in( ...$special_query_vars['post__in'] );

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
		if ( ! in_array( $orderby, $this->custom_order_opts, true ) || 'post__in' === $orderby ) {
			return array( 'orderby' => $orderby );
		}

		if ( 'price' === $orderby ) {
			add_filter( 'posts_clauses', array( $this, 'add_price_sorting_posts_clauses' ), 10, 2 );
			return array(
				'isProductCollection' => true,
				'orderby'             => $orderby,
			);
		}

		// The popularity orderby value here is for backwards compatibility as we have since removed the filter option.
		if ( 'sales' === $orderby || 'popularity' === $orderby ) {
			add_filter( 'posts_clauses', array( $this, 'add_sales_sorting_posts_clauses' ), 10, 2 );
			return array(
				'isProductCollection' => true,
				'orderby'             => $orderby,
			);
		}

		if ( 'menu_order' === $orderby ) {
			return array(
				'orderby' => 'menu_order',
				'order'   => 'ASC',
			);
		}

		if ( 'random' === $orderby ) {
			return array(
				'orderby' => 'rand',
			);
		}

		$meta_keys = array(
			'rating' => '_wc_average_rating',
		);

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key' => $meta_keys[ $orderby ],
			'orderby'  => 'meta_value_num',
		);
	}

	/**
	 * Add the `posts_clauses` filter to add price-based sorting
	 *
	 * @param array    $clauses The list of clauses for the query.
	 * @param WP_Query $query   The WP_Query instance.
	 * @return array   Modified list of clauses.
	 */
	public function add_price_sorting_posts_clauses( $clauses, $query ) {
		$query_vars                  = $query->query_vars;
		$is_product_collection_block = $query_vars['isProductCollection'] ?? false;

		if ( ! $is_product_collection_block ) {
			return $clauses;
		}

		$orderby = $query_vars['orderby'] ?? null;
		if ( 'price' !== $orderby ) {
			return $clauses;
		}

		$clauses['join']    = $this->append_product_sorting_table_join( $clauses['join'] );
		$is_ascending_order = 'asc' === strtolower( $query_vars['order'] ?? 'desc' );

		$clauses['orderby'] = $is_ascending_order ?
			'wc_product_meta_lookup.min_price ASC, wc_product_meta_lookup.product_id ASC' :
			'wc_product_meta_lookup.max_price DESC, wc_product_meta_lookup.product_id DESC';

		return $clauses;
	}

	/**
	 * Add the `posts_clauses` filter to add sales-based sorting
	 *
	 * @param array    $clauses The list of clauses for the query.
	 * @param WP_Query $query   The WP_Query instance.
	 * @return array   Modified list of clauses.
	 */
	public function add_sales_sorting_posts_clauses( $clauses, $query ) {
		$query_vars                  = $query->query_vars;
		$is_product_collection_block = $query_vars['isProductCollection'] ?? false;

		if ( ! $is_product_collection_block ) {
			return $clauses;
		}

		$orderby = $query_vars['orderby'] ?? null;

		// The popularity orderby value here is for backwards compatibility as we have since removed the filter option.
		if ( 'sales' !== $orderby && 'popularity' !== $orderby ) {
			return $clauses;
		}

		$clauses['join']    = $this->append_product_sorting_table_join( $clauses['join'] );
		$is_ascending_order = 'asc' === strtolower( $query_vars['order'] ?? 'desc' );

		$clauses['orderby'] = $is_ascending_order ?
			'wc_product_meta_lookup.total_sales ASC, wc_product_meta_lookup.product_id ASC' :
			'wc_product_meta_lookup.total_sales DESC, wc_product_meta_lookup.product_id DESC';

		return $clauses;
	}

	/**
	 * Join wc_product_meta_lookup to posts if not already joined.
	 *
	 * @param string $sql SQL join.
	 * @return string
	 */
	protected function append_product_sorting_table_join( $sql ) {
		global $wpdb;

		if ( ! strstr( $sql, 'wc_product_meta_lookup' ) ) {
			$sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}
		return $sql;
	}

	/**
	 * Merge all of the 'post__in' values and return an array containing only values that are present in all arrays.
	 *
	 * @param int[][] ...$post__in The 'post__in' values to be merged.
	 *
	 * @return int[] The merged 'post__in' values.
	 */
	private function merge_post__in( ...$post__in ) {
		if ( empty( $post__in ) ) {
			return array();
		}

		// Since we're using array_intersect, any array that is empty will result
		// in an empty output array. To avoid this we need to make sure every
		// argument is a non-empty array.
		$post__in = array_filter(
			$post__in,
			function ( $val ) {
				return is_array( $val ) && ! empty( $val );
			}
		);
		if ( empty( $post__in ) ) {
			return array();
		}

		// Since the 'post__in' filter is exclusionary we need to use an intersection of
		// all of the arrays. This ensures one query doesn't add options that another
		// has otherwise excluded from the results.
		if ( count( $post__in ) > 1 ) {
			$post__in = array_intersect( ...$post__in );
			// An empty array means that there was no overlap between the filters and so
			// the query should return no results.
			if ( empty( $post__in ) ) {
				return array( -1 );
			}
		} else {
			$post__in = reset( $post__in );
		}

		return array_values( array_unique( $post__in, SORT_NUMERIC ) );
	}
}
