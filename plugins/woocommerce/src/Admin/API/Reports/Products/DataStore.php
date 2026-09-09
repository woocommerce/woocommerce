<?php
/**
 * API\Reports\Products\DataStore class file.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Products;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Internal\Admin\Reports\ProductSearchQuery;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * API\Reports\Products\DataStore.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {

	/**
	 * Table used to get the data.
	 *
	 * @override ReportsDataStore::$table_name
	 *
	 * @var string
	 */
	protected static $table_name = 'wc_order_product_lookup';

	/**
	 * Cache identifier.
	 *
	 * @override ReportsDataStore::$cache_key
	 *
	 * @var string
	 */
	protected $cache_key = 'products';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override ReportsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'       => 'strval',
		'date_end'         => 'strval',
		'product_id'       => 'intval',
		'items_sold'       => 'intval',
		'net_revenue'      => 'floatval',
		'orders_count'     => 'intval',
		'last_sold'        => 'strval',
		// Extended attributes.
		'name'             => 'strval',
		'price'            => 'floatval',
		'image'            => 'strval',
		'permalink'        => 'strval',
		'stock_status'     => 'strval',
		'stock_quantity'   => 'intval',
		'low_stock_amount' => 'intval',
		'category_ids'     => 'array_values',
		'variations'       => 'array_values',
		'sku'              => 'strval',
		'total_sales'      => 'intval',
	);

	/**
	 * Extended product attributes to include in the data.
	 *
	 * @var array
	 */
	protected $extended_attributes = array(
		'name',
		'price',
		'image',
		'permalink',
		'stock_status',
		'stock_quantity',
		'manage_stock',
		'low_stock_amount',
		'category_ids',
		'variations',
		'sku',
		'total_sales',
	);

	/**
	 * Whether the query currently being served carries an `unsold` argument.
	 *
	 * @var bool
	 */
	private $is_unsold = false;

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override ReportsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'products';

	/**
	 * Whether the query currently being served carries a `search` argument.
	 *
	 * @var bool
	 */
	private $is_search = false;

	/**
	 * Last search statement built, with the arguments it was built from.
	 *
	 * @var array|null
	 */
	private $search_subquery = null;

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * @override ReportsDataStore::assign_report_columns()
	 */
	protected function assign_report_columns() {
		$table_name           = self::get_db_table_name();
		$this->report_columns = array(
			'product_id'   => 'product_id',
			'items_sold'   => 'SUM(product_qty) as items_sold',
			'net_revenue'  => 'SUM(product_net_revenue) AS net_revenue',
			'orders_count' => "COUNT( DISTINCT ( CASE WHEN product_gross_revenue >= 0 THEN {$table_name}.order_id END ) ) as orders_count",
			'last_sold'    => "MAX(CASE WHEN {$table_name}.product_qty > 0 THEN {$table_name}.date_created END) AS last_sold",
		);
	}

	/**
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		add_action( 'woocommerce_analytics_delete_order_stats', array( __CLASS__, 'sync_on_order_delete' ), 10 );
		add_action( 'woocommerce_order_partially_refunded', array( __CLASS__, 'add_partial_refund_type_meta' ), 10, 2 );
		add_action( 'woocommerce_order_fully_refunded', array( __CLASS__, 'add_full_refund_type_meta' ), 10, 2 );
	}

	/**
	 * Add a partial refund type meta to the order.
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 */
	public static function add_partial_refund_type_meta( $order_id, $refund_id ) {
		self::add_refund_type_meta( $refund_id, 'partial' );
	}

	/**
	 * Add a full refund type meta to the order.
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 */
	public static function add_full_refund_type_meta( $order_id, $refund_id ) {
		self::add_refund_type_meta( $refund_id, 'full' );
	}

	/**
	 * Add a refund type meta to the order.
	 *
	 * @param int    $refund_id Refund ID.
	 * @param string $type      Refund type.
	 */
	public static function add_refund_type_meta( $refund_id, $type ) {
		$order = wc_get_order( $refund_id );
		$order->update_meta_data( '_refund_type', $type );
		$order->save_meta_data();
	}

	/**
	 * Fills FROM clause of SQL request based on user supplied parameters.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $arg_name   Target of the JOIN sql param.
	 * @param string $id_cell    ID cell identifier, like `table_name.id_column_name`.
	 */
	protected function add_from_sql_params( $query_args, $arg_name, $id_cell ) {
		global $wpdb;

		$type = 'join';
		// Order by product name requires extra JOIN.
		switch ( $query_args['orderby'] ) {
			case 'product_name':
				$join = " JOIN {$wpdb->posts} AS _products ON {$id_cell} = _products.ID";
				break;
			case 'sku':
				$join = " LEFT JOIN {$wpdb->postmeta} AS postmeta ON {$id_cell} = postmeta.post_id AND postmeta.meta_key = '_sku'";
				break;
			case 'variations':
				$type = 'left_join';
				$join = "LEFT JOIN ( SELECT post_parent, COUNT(*) AS variations FROM {$wpdb->posts} WHERE post_type = 'product_variation' GROUP BY post_parent ) AS _variations ON {$id_cell} = _variations.post_parent";
				break;
			default:
				$join = '';
				break;
		}
		if ( $join ) {
			if ( 'inner' === $arg_name ) {
				$this->subquery->add_sql_clause( $type, $join );
			} else {
				$this->add_sql_clause( $type, $join );
			}
		}
	}

	/**
	 * Updates the database query with parameters used for Products report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 */
	protected function add_sql_query_params( $query_args ) {
		global $wpdb;
		$order_product_lookup_table = self::get_db_table_name();

		$this->add_time_period_sql_params( $query_args, $order_product_lookup_table );
		$this->get_limit_sql_params( $query_args );
		$this->add_order_by_sql_params( $query_args );

		$included_products = $this->get_included_products_array( $query_args );
		$product_id_filter = ProductSearchQuery::get_id_condition(
			"{$order_product_lookup_table}.product_id",
			$this->get_search_subquery( $query_args, $included_products ),
			$included_products
		);
		if ( $product_id_filter ) {
			$this->add_from_sql_params( $query_args, 'outer', 'default_results.product_id' );
			$this->subquery->add_sql_clause( 'where', "AND {$product_id_filter}" );
		} else {
			$this->add_from_sql_params( $query_args, 'inner', "{$order_product_lookup_table}.product_id" );
		}

		$included_variations = $this->get_included_variations( $query_args );
		if ( $included_variations ) {
			$this->subquery->add_sql_clause( 'where', "AND {$order_product_lookup_table}.variation_id IN ({$included_variations})" );
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$this->subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}wc_order_stats ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}wc_order_stats.order_id" );
			$this->subquery->add_sql_clause( 'where', "AND ( {$order_status_filter} )" );
		}
	}

	/**
	 * Returns the statement the query's `search` argument resolves to.
	 *
	 * Serving one report needs it twice, once for the restriction and once for the row count, and
	 * building it runs a WP_Query, so the last one is kept for as long as the arguments match.
	 *
	 * @since 11.2.0
	 *
	 * @param array $query_args        Query parameters.
	 * @param array $included_products Product IDs the `categories` and `products` filters resolve to.
	 * @return string SQL statement, or an empty string when the query carries no search.
	 */
	private function get_search_subquery( $query_args, array $included_products ): string {
		$terms = $query_args['search'] ?? array();

		if ( null === $this->search_subquery
			|| $this->search_subquery['terms'] !== $terms
			|| $this->search_subquery['included_products'] !== $included_products
		) {
			$this->search_subquery = array(
				'terms'             => $terms,
				'included_products' => $included_products,
				'subquery'          => ProductSearchQuery::get_ids_subquery( $terms, $included_products ),
			);
		}

		return $this->search_subquery['subquery'];
	}

	/**
	 * Returns the cache key for a query, and records whether it carries a search.
	 *
	 * `should_use_cache()` decides whether the response is cached but only receives the key, so the
	 * search has to be noted here, where the query arguments are still around.
	 *
	 * @override ReportsDataStore::get_cache_key()
	 *
	 * @since 11.2.0
	 *
	 * @param array $params Query parameters.
	 * @return string Cache key.
	 */
	protected function get_cache_key( $params ) {
		$this->is_search = ! empty( $params['search'] );
		$this->is_unsold = ! empty( $params['unsold'] );

		return parent::get_cache_key( $params );
	}

	/**
	 * Whether the report should be read from and written to the report cache.
	 *
	 * @override ReportsDataStore::should_use_cache()
	 *
	 * @since 11.2.0
	 *
	 * @return bool
	 */
	protected function should_use_cache() {
		// Run the parent first, since it applies the filter plugins opt out of the cache through.
		$use_cache = parent::should_use_cache();

		// A search is resolved against product titles and SKUs while the report runs, and nothing
		// invalidates the report cache when one is renamed, so a cached response would keep
		// answering with the old matches for up to a week.
		if ( $this->is_search ) {
			return false;
		}

		// The unsold list depends on which products exist, and nothing invalidates the report
		// cache when a product is created or deleted, so a cached response would keep leaving
		// new products out for up to a week.
		if ( $this->is_unsold ) {
			return false;
		}

		return $use_cache;
	}

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @override ReportsDataStore::normalize_order_by()
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'date' === $order_by ) {
			return self::get_db_table_name() . '.date_created';
		}
		if ( 'product_name' === $order_by ) {
			return 'post_title';
		}
		if ( 'sku' === $order_by ) {
			return 'meta_value';
		}

		return $order_by;
	}

	/**
	 * Enriches the product data with attributes specified by the extended_attributes.
	 *
	 * @param array $products_data Product data.
	 * @param array $query_args  Query parameters.
	 */
	protected function include_extended_info( &$products_data, $query_args ) {
		global $wpdb;
		$product_names = array();

		if ( $query_args['extended_info'] ) {
			self::prime_object_caches( array_column( $products_data, 'product_id' ) );
		}

		foreach ( $products_data as $key => $product_data ) {
			$extended_info = new \ArrayObject();
			if ( $query_args['extended_info'] ) {
				$product_id = $product_data['product_id'];
				$product    = wc_get_product( $product_id );
				// Product was deleted.
				if ( ! $product ) {
					if ( ! isset( $product_names[ $product_id ] ) ) {
						$product_names[ $product_id ] = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT i.order_item_name
								FROM {$wpdb->prefix}wc_order_product_lookup l
								JOIN {$wpdb->prefix}woocommerce_order_items i ON i.order_item_id = l.order_item_id
								WHERE l.product_id = %d
								ORDER BY l.order_item_id DESC
								LIMIT 1",
								$product_id
							)
						);
					}

					/* translators: %s is product name */
					$products_data[ $key ]['extended_info']['name'] = $product_names[ $product_id ] ? sprintf( __( '%s (Deleted)', 'woocommerce' ), $product_names[ $product_id ] ) : __( '(Deleted)', 'woocommerce' );
					continue;
				}

				$extended_attributes = apply_filters( 'woocommerce_rest_reports_products_extended_attributes', $this->extended_attributes, $product_data );
				foreach ( $extended_attributes as $extended_attribute ) {
					if ( 'variations' === $extended_attribute ) {
						if ( ! $product->is_type( ProductType::VARIABLE ) ) {
							continue;
						}
						$function = 'get_children';
					} else {
						$function = 'get_' . $extended_attribute;
					}
					if ( is_callable( array( $product, $function ) ) ) {
						$value                                = $product->{$function}();
						$extended_info[ $extended_attribute ] = $value;
					}
				}
				// If there is no set low_stock_amount, use the one in user settings.
				if ( '' === $extended_info['low_stock_amount'] ) {
					$extended_info['low_stock_amount'] = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
				}
				$extended_info = $this->cast_numbers( $extended_info );
			}
			$products_data[ $key ]['extended_info'] = $extended_info;
		}
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @override ReportsDataStore::get_data()
	 *
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		$data = parent::get_data( $query_args );

		/*
		 * Do not cache extended info -- this is required to get the latest stock data.
		 * `include_extended_info` checks only `extended_info` key,
		 * so we don't need to bother about normalizing timestamps.
		 */
		$defaults   = $this->get_default_query_vars();
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->include_extended_info( $data->data, $query_args );

		return $data;
	}

	/**
	 * Get the default query arguments to be used by get_data().
	 * These defaults are only partially applied when used via REST API, as that has its own defaults.
	 *
	 * @override ReportsDataStore::get_default_query_vars()
	 *
	 * @return array Query parameters.
	 */
	public function get_default_query_vars() {
		$defaults                      = parent::get_default_query_vars();
		$defaults['category_includes'] = array();
		$defaults['product_includes']  = array();
		$defaults['search']            = array();
		$defaults['extended_info']     = false;
		$defaults['unsold']            = false;

		return $defaults;
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * @override ReportsDataStore::get_noncached_data()
	 *
	 * @see get_data
	 * @param array $query_args Query parameters.
	 * @return \stdClass|\WP_Error Data object `{ totals: *, intervals: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_noncached_data( $query_args ) {
		global $wpdb;

		$table_name = self::get_db_table_name();

		$this->initialize_queries();

		$data = (object) array(
			'data'    => array(),
			'total'   => 0,
			'pages'   => 0,
			'page_no' => 0,
		);

		if ( ! empty( $query_args['unsold'] ) ) {
			return $this->get_unsold_data( $query_args, $data );
		}

		$selections        = $this->selected_columns( $query_args );
		$included_products = $this->get_included_products_array( $query_args );
		$search_subquery   = $this->get_search_subquery( $query_args, $included_products );
		$params            = $this->get_limit_params( $query_args );
		$this->add_sql_query_params( $query_args );

		if ( $search_subquery || count( $included_products ) > 0 ) {
			if ( $search_subquery ) {
				// The set of matching products is only known to the database, so count it there too.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $search_subquery is built from prepared fragments.
				$total_results = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ( {$search_subquery} ) AS search_results" );
				$ids_table     = $search_subquery;
			} else {
				$filtered_products = array_diff( $included_products, array( '-1' ) );
				$total_results     = count( $filtered_products );
				$ids_table         = $this->get_ids_table( $included_products, 'product_id' );
			}

			$total_pages = (int) ceil( $total_results / $params['per_page'] );

			if ( 'date' === $query_args['orderby'] ) {
				$selections .= ", {$table_name}.date_created";
			}

			$fields          = $this->get_fields( $query_args );
			$join_selections = $this->format_join_selections( $fields, array( 'product_id' ) );

			$this->subquery->clear_sql_clause( 'select' );
			$this->subquery->add_sql_clause( 'select', $selections );
			$this->add_sql_clause( 'select', $join_selections );
			$this->add_sql_clause( 'from', '(' );
			$this->add_sql_clause( 'from', $this->subquery->get_query_statement() );
			$this->add_sql_clause( 'from', ") AS {$table_name}" );
			$this->add_sql_clause(
				'right_join',
				"RIGHT JOIN ( {$ids_table} ) AS default_results
				ON default_results.product_id = {$table_name}.product_id"
			);
			$this->add_sql_clause( 'where', 'AND default_results.product_id != -1' );

			// The database is free to resolve a tie differently for each page, so a product comes
			// back on two of them while another is never reached. A product without sales ties on
			// every column the report can order by, and a filtered report is mostly those.
			// `get_ids_table()` types its column as text, so cast it or 100 would sort before 99.
			$order_by = $this->get_sql_clause( 'order_by' );
			$this->clear_sql_clause( 'order_by' );
			$this->add_sql_clause( 'order_by', "{$order_by}, CAST( default_results.product_id AS SIGNED )" );

			$products_query = $this->get_query_statement();
		} else {
			$count_query      = "SELECT COUNT(*) FROM (
					{$this->subquery->get_query_statement()}
				) AS tt";
			$db_records_count = (int) $wpdb->get_var(
				$count_query // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			);

			$total_results = $db_records_count;
			$total_pages   = (int) ceil( $db_records_count / $params['per_page'] );

			if ( ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) ) {
				return $data;
			}

			$this->subquery->clear_sql_clause( 'select' );
			$this->subquery->add_sql_clause( 'select', $selections );
			if ( in_array( $query_args['orderby'], array( 'items_sold', 'net_revenue', 'orders_count', 'variations' ), true ) ) {
				$this->subquery->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) . ', product_id' );
			} else {
				$this->subquery->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
			}
			$this->subquery->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
			$products_query = $this->subquery->get_query_statement();
		}

		$product_data = $wpdb->get_results(
			$products_query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		if ( null === $product_data ) {
			return $data;
		}

		$product_data = array_map( array( $this, 'cast_numbers' ), $product_data );

		// cast_numbers stringifies the null a padded row carries for its columns, but a product
		// with no sales in the period has no last sale to report, so the field stays null as the
		// schema declares it.
		foreach ( $product_data as $key => $row ) {
			if ( isset( $row['last_sold'] ) && '' === $row['last_sold'] ) {
				$product_data[ $key ]['last_sold'] = null;
			}
		}

		$data = (object) array(
			'data'    => $product_data,
			'total'   => $total_results,
			'pages'   => $total_pages,
			'page_no' => (int) $query_args['page'],
		);

		return $data;
	}

	/**
	 * Returns the report rows for products with no sales in the requested period.
	 *
	 * The report table is grouped by the sales lookup, so products that sold nothing in the period
	 * are absent from it. This builds the opposite set: every published product LEFT JOINed to the
	 * period's per-product sales, keeping only the products the join misses. All the metrics come
	 * back as zero and `last_sold` as null, since the products have nothing to report for the period.
	 *
	 * @since 11.2.0
	 *
	 * @param array     $query_args Query parameters.
	 * @param \stdClass $data       Empty report data object to fill in.
	 * @return \stdClass Data object `{ data: array, total: int, pages: int, page_no: int }`.
	 */
	private function get_unsold_data( $query_args, $data ) {
		global $wpdb;

		$table_name = self::get_db_table_name();
		$params     = $this->get_limit_params( $query_args );

		// Products that sold in the period, aggregated per product. A product missing from this
		// set is an unsold one. The order status and variation filters go in here: they decide
		// whether a sale counts, not which products exist.
		$sold_subquery = new SqlQuery( $this->context . '_subquery' );
		$sold_subquery->add_sql_clause( 'select', 'product_id' );
		$sold_subquery->add_sql_clause( 'from', $table_name );

		foreach ( array(
			'after'  => '>=',
			'before' => '<=',
		) as $bound => $comparator ) {
			if ( empty( $query_args[ $bound ] ) || ! $query_args[ $bound ] instanceof \DateTimeInterface ) {
				continue;
			}
			$datetime = $query_args[ $bound ];
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- matches add_time_period_sql_params; the value comes from a formatted datetime object.
			$datetime_str = $datetime instanceof \WC_DateTime
				? $datetime->date( TimeInterval::$sql_datetime_format )
				: $datetime->format( TimeInterval::$sql_datetime_format );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is a table name and $datetime_str a formatted date.
			$sold_subquery->add_sql_clause( 'where_time', "AND {$table_name}.date_created {$comparator} '{$datetime_str}'" );
		}

		$included_variations = $this->get_included_variations( $query_args );
		if ( $included_variations ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is a table name, $included_variations is prepared.
			$sold_subquery->add_sql_clause( 'where', "AND {$table_name}.variation_id IN ({$included_variations})" );
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$sold_subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}wc_order_stats ON {$table_name}.order_id = {$wpdb->prefix}wc_order_stats.order_id" );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $order_status_filter is built from escaped status slugs.
			$sold_subquery->add_sql_clause( 'where', "AND ( {$order_status_filter} )" );
		}

		$sold_subquery->add_sql_clause( 'group_by', 'product_id' );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- built from prepared fragments above.
		$sold_statement = $sold_subquery->get_query_statement();

		$this->clear_sql_clause( 'select' );
		$this->add_sql_clause( 'select', 'posts.ID AS product_id, 0 AS items_sold, 0 AS net_revenue, 0 AS orders_count, NULL AS last_sold' );
		$this->add_sql_clause( 'from', "{$wpdb->posts} AS posts" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->posts is a table name, $sold_statement is built from prepared fragments.
		$this->add_sql_clause( 'left_join', "LEFT JOIN ( {$sold_statement} ) AS sold ON sold.product_id = posts.ID" );
		$this->add_sql_clause( 'where', "AND posts.post_type = 'product'" );
		$this->add_sql_clause( 'where', "AND posts.post_status = 'publish'" );
		$this->add_sql_clause( 'where', 'AND sold.product_id IS NULL' );

		$included_products = $this->get_included_products_array( $query_args );
		$search_subquery   = $this->get_search_subquery( $query_args, $included_products );
		$product_id_filter = ProductSearchQuery::get_id_condition( 'posts.ID', $search_subquery, $included_products );
		if ( $product_id_filter ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $product_id_filter is built from prepared fragments.
			$this->add_sql_clause( 'where', "AND {$product_id_filter}" );
		}

		// A zero metric cannot be sorted on, so only the product attributes stay sortable, and
		// anything else falls back to the title.
		$this->clear_sql_clause( 'order_by' );
		switch ( $query_args['orderby'] ?? '' ) {
			case 'sku':
				$this->add_from_sql_params( array( 'orderby' => 'sku' ), 'outer', 'posts.ID' );
				$this->add_sql_clause( 'order_by', 'meta_value' );
				break;
			case 'variations':
				$this->add_from_sql_params( array( 'orderby' => 'variations' ), 'outer', 'posts.ID' );
				$this->add_sql_clause( 'order_by', 'variations' );
				break;
			case 'product_id':
				$this->add_sql_clause( 'order_by', 'posts.ID' );
				break;
			default:
				$this->add_sql_clause( 'order_by', 'posts.post_title' );
		}
		$this->add_orderby_order_clause( $query_args, $this );
		// Without a tiebreak the database is free to resolve equal titles differently per page,
		// so a product can come back on two of them while another is never reached.
		$order_by = $this->get_sql_clause( 'order_by' );
		$this->clear_sql_clause( 'order_by' );
		$this->add_sql_clause( 'order_by', "{$order_by}, posts.ID" );

		$count_query = "SELECT COUNT(*) FROM (
				{$this->get_query_statement()}
			) AS tt";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- built from prepared fragments.
		$total_results = (int) $wpdb->get_var( $count_query );
		$total_pages   = (int) ceil( $total_results / $params['per_page'] );

		if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
			return $data;
		}

		$this->get_limit_sql_params( $query_args );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- built from prepared fragments.
		$product_data = $wpdb->get_results( $this->get_query_statement(), ARRAY_A );

		if ( null === $product_data ) {
			return $data;
		}

		$product_data = array_map( array( $this, 'cast_numbers' ), $product_data );

		// cast_numbers stringifies the null the SQL returns, but an unsold product has no
		// last sale to report, so the field stays null as the schema declares it.
		foreach ( $product_data as $key => $row ) {
			if ( isset( $row['last_sold'] ) && '' === $row['last_sold'] ) {
				$product_data[ $key ]['last_sold'] = null;
			}
		}

		return (object) array(
			'data'    => $product_data,
			'total'   => $total_results,
			'pages'   => $total_pages,
			'page_no' => (int) $query_args['page'],
		);
	}

	/**
	 * Create or update an entry in the wc_order_product_lookup table for an order.
	 *
	 * @since 3.5.0
	 * @param int $order_id Order ID.
	 * @return int|bool Returns -1 if order won't be processed, or a boolean indicating processing success.
	 */
	public static function sync_order_products( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return -1;
		}

		$table_name     = self::get_db_table_name();
		$existing_items = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT order_item_id FROM {$table_name} WHERE order_id = %d",
				$order_id
			)
		);
		$existing_items = array_flip( $existing_items );
		$order_items    = $order->get_items();
		$num_updated    = 0;
		$decimals       = wc_get_price_decimals();
		$round_tax      = 'no' === get_option( 'woocommerce_tax_round_at_subtotal' );

		$is_full_refund_without_line_items = false;
		$partial_refund_product_revenue    = array();
		$refund_type                       = $order->get_meta( '_refund_type' );
		$uses_new_full_refund_data         = OrderUtil::uses_new_full_refund_data();

		$parent_order = null;

		// When changing the order status to "Refunded", the refund order's type will be full refund, and the order items will be empty.
		// We need to get the parent order items, and exclude the items that are already being partially refunded.
		if (
			'shop_order_refund' === $order->get_type() &&
			'full' === $refund_type &&
			empty( $order_items ) &&
			$uses_new_full_refund_data
		) {
			$is_full_refund_without_line_items = true;

			$parent_order_id = $order->get_parent_id();
			$parent_order    = wc_get_order( $parent_order_id );
			$order_items     = $parent_order->get_items();

			// Get the partially refunded product and variation IDs along with their sum of product_net_revenue from the parent order.
			$partial_refund_products = $wpdb->get_results(
				$wpdb->prepare(
					"
						SELECT
							product_lookup.product_id,
							product_lookup.variation_id,
							SUM( product_lookup.product_net_revenue ) AS product_net_revenue
						FROM %i AS product_lookup
						INNER JOIN {$wpdb->prefix}wc_order_stats AS order_stats
							ON order_stats.order_id = product_lookup.order_id
						WHERE 1 = 1
							AND order_stats.parent_id = %d
							AND product_lookup.product_net_revenue < 0
						GROUP BY product_lookup.product_id, product_lookup.variation_id
					",
					$table_name,
					$parent_order_id
				)
			);

			/**
			 * Create a lookup table for partially refunded products.
			 * E.g. [
			 *   '1' => -20,
			 *   '2' => -40,
			 *   '51' => -10,
			 *   '52' => -30,
			 * ]
			 */
			foreach ( $partial_refund_products as $product ) {
				$id                                    = $product->variation_id ? $product->variation_id : $product->product_id;
				$partial_refund_product_revenue[ $id ] = (float) $product->product_net_revenue;
			}
		}

		foreach ( $order_items as $order_item ) {
			$order_item_id = $order_item->get_id();
			unset( $existing_items[ $order_item_id ] );
			$product_qty         = $order_item->get_quantity( 'edit' );
			$product_id          = $order_item->get_product_id( 'edit' );
			$variation_id        = $order_item->get_variation_id( 'edit' );
			$shipping_amount     = $order->get_item_shipping_amount( $order_item );
			$shipping_tax_amount = $order->get_item_shipping_tax_amount( $order_item );
			$coupon_amount       = $order->get_item_coupon_amount( $order_item );
			$tax_amount          = $order->get_item_cart_tax_amount( $order_item );
			$net_revenue         = round( $order_item->get_total( 'edit' ), $decimals );

			// If the order is a full refund and there is no order items. The order item here is the parent order item.
			if ( $is_full_refund_without_line_items ) {
				$id             = $variation_id ? $variation_id : $product_id;
				$partial_refund = $partial_refund_product_revenue[ $id ] ?? 0;
				// If a single line item was refunded 60% then fully refunded after, we need store the difference in the product lookup table.
				// E.g. A product costs $100, it was previously partially refunded $60, then fully refunded $40.
				// So it will be -abs( 100 + (-60) ) = -40.
				$net_revenue = -abs( $net_revenue + $partial_refund );

				// Skip items that have already been fully refunded (single or multiple partial refunds).
				if ( 0.0 === $net_revenue ) {
					continue;
				}

				$product_qty = -abs( $product_qty );

				// Set coupon amount to 0 for full refunds without line items.
				$coupon_amount = 0;

				if ( $parent_order ) {
					$remaining_refund_items = $parent_order->get_remaining_refund_items();

					// Calculate the shipping amount to refund from the parent order.
					$total_shipping_refunded  = $parent_order->get_total_shipping_refunded();
					$shipping_total           = (float) $parent_order->get_shipping_total();
					$total_shipping_to_refund = $shipping_total - $total_shipping_refunded;

					if ( $total_shipping_to_refund > 0 ) {
						$shipping_amount = -abs( $parent_order->get_item_shipping_amount( $order_item, $remaining_refund_items, $total_shipping_to_refund ) );
					}

					// Calculate the shipping tax amount to refund from the parent order.
					$shipping_tax                 = (float) $parent_order->get_shipping_tax();
					$total_shipping_tax_refunded  = $parent_order->get_total_shipping_tax_refunded();
					$total_shipping_tax_to_refund = $shipping_tax - $total_shipping_tax_refunded;

					if ( $total_shipping_tax_to_refund > 0 ) {
						$shipping_tax_amount = -abs( $parent_order->get_item_shipping_tax_amount( $order_item, $remaining_refund_items, $total_shipping_tax_to_refund ) );
					}

					// Calculate cart tax amount of the item from the parent order.
					$tax_amount = -abs( $parent_order->get_item_cart_tax_amount( $order_item ) );
				}
			}

			$is_refund = $net_revenue < 0;

			// Skip line items without changes to product quantity.
			if ( ! $product_qty && ! $is_refund ) {
				++$num_updated;
				continue;
			}

			if ( $round_tax ) {
				$tax_amount = round( $tax_amount, $decimals );
			}

			$result = $wpdb->replace(
				self::get_db_table_name(),
				array(
					'order_item_id'         => $order_item_id,
					'order_id'              => $order->get_id(),
					'product_id'            => $product_id,
					'variation_id'          => $variation_id,
					'customer_id'           => $order->get_report_customer_id(),
					'product_qty'           => $product_qty,
					'product_net_revenue'   => $net_revenue,
					'date_created'          => $order->get_date_created( 'edit' )->date( TimeInterval::$sql_datetime_format ),
					'coupon_amount'         => $coupon_amount,
					'tax_amount'            => $tax_amount,
					'shipping_amount'       => $shipping_amount,
					'shipping_tax_amount'   => $shipping_tax_amount,
					// @todo Can this be incorrect if modified by filters?
					'product_gross_revenue' => $net_revenue + $tax_amount + $shipping_amount + $shipping_tax_amount,
				),
				array(
					'%d', // order_item_id.
					'%d', // order_id.
					'%d', // product_id.
					'%d', // variation_id.
					'%d', // customer_id.
					'%d', // product_qty.
					'%f', // product_net_revenue.
					'%s', // date_created.
					'%f', // coupon_amount.
					'%f', // tax_amount.
					'%f', // shipping_amount.
					'%f', // shipping_tax_amount.
					'%f', // product_gross_revenue.
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This data store owns the formatted analytics write; caching a write is not applicable.

			/**
			 * Fires when product's reports are updated.
			 *
			 * @param int $order_item_id Order Item ID.
			 * @param int $order_id      Order ID.
			 */
			do_action( 'woocommerce_analytics_update_product', $order_item_id, $order->get_id() );

			// Sum the rows affected. Using REPLACE can affect 2 rows if the row already exists.
			$num_updated += 2 === intval( $result ) ? 1 : intval( $result );
		}

		if ( ! empty( $existing_items ) ) {
			$existing_items = array_flip( $existing_items );
			$format         = array_fill( 0, count( $existing_items ), '%d' );
			$format         = implode( ',', $format );
			array_unshift( $existing_items, $order_id );
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"DELETE FROM {$table_name} WHERE order_id = %d AND order_item_id in ({$format})",
					$existing_items
				)
			);
		}

		return ( count( $order_items ) === $num_updated );
	}

	/**
	 * Clean products data when an order is deleted.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function sync_on_order_delete( $order_id ) {
		global $wpdb;

		$wpdb->delete( self::get_db_table_name(), array( 'order_id' => $order_id ) );

		/**
		 * Fires when product's reports are removed from database.
		 *
		 * @param int $product_id Product ID.
		 * @param int $order_id   Order ID.
		 */
		do_action( 'woocommerce_analytics_delete_product', 0, $order_id );

		ReportsCache::invalidate();
	}

	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		$this->clear_all_clauses();
		$this->subquery = new SqlQuery( $this->context . '_subquery' );
		$this->subquery->add_sql_clause( 'select', 'product_id' );
		$this->subquery->add_sql_clause( 'from', self::get_db_table_name() );
		$this->subquery->add_sql_clause( 'group_by', 'product_id' );
	}
}
