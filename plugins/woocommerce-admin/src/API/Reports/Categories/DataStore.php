<?php
/**
 * API\Reports\Categories\DataStore class file.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Categories;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use \Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use \Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * API\Reports\Categories\DataStore.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_product_lookup';

	/**
	 * Cache identifier.
	 *
	 * @var string
	 */
	protected $cache_key = 'categories';

	/**
	 * Order by setting used for sorting categories data.
	 *
	 * @var string
	 */
	private $order_by = '';

	/**
	 * Order setting used for sorting categories data.
	 *
	 * @var string
	 */
	private $order = '';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'category_id'    => 'intval',
		'items_sold'     => 'intval',
		'net_revenue'    => 'floatval',
		'orders_count'   => 'intval',
		'products_count' => 'intval',
	);

	/**
	 * SQL columns to select in the db query.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'items_sold'     => 'SUM(product_qty) as items_sold',
		'net_revenue'    => 'SUM(product_net_revenue) AS net_revenue',
		'orders_count'   => 'COUNT(DISTINCT order_id) as orders_count',
		'products_count' => 'COUNT(DISTINCT product_id) as products_count',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// Avoid ambigious column order_id in SQL query.
		$this->report_columns['products_count'] = str_replace( 'product_id', $table_name . '.product_id', $this->report_columns['products_count'] );
		$this->report_columns['orders_count']   = str_replace( 'order_id', $table_name . '.order_id', $this->report_columns['orders_count'] );
	}

	/**
	 * Return the database query with parameters used for Categories report: time span and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $order_product_lookup_table );

		// join wp_order_product_lookup_table with relationships and taxonomies.
		$sql_query_params['from_clause'] .= " LEFT JOIN {$wpdb->term_relationships} ON {$order_product_lookup_table}.product_id = {$wpdb->term_relationships}.object_id";
		$sql_query_params['from_clause'] .= " LEFT JOIN {$wpdb->wc_category_lookup} ON {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->wc_category_lookup}.category_id";

		$included_categories = $this->get_included_categories( $query_args );
		if ( $included_categories ) {
			$sql_query_params['where_clause'] .= " AND {$wpdb->wc_category_lookup}.category_tree_id IN ({$included_categories})";

			// Limit is left out here so that the grouping in code by PHP can be applied correctly.
			// This also needs to be put after the term_taxonomy JOIN so that we can match the correct term name.
			$sql_query_params = $this->get_order_by_params( $query_args, $sql_query_params, 'outer_from_clause', 'default_results.category_id' );
		} else {
			$sql_query_params = $this->get_order_by_params( $query_args, $sql_query_params, 'from_clause', "{$wpdb->wc_category_lookup}.category_tree_id" );
		}

		// @todo Only products in the category C or orders with products from category C (and, possibly others?).
		$included_products = $this->get_included_products( $query_args );
		if ( $included_products ) {
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.product_id IN ({$included_products})";
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$sql_query_params['from_clause']  .= " JOIN {$wpdb->prefix}wc_order_stats ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}wc_order_stats.order_id";
			$sql_query_params['where_clause'] .= " AND ( {$order_status_filter} )";
		}

		$sql_query_params['where_clause'] .= " AND {$wpdb->wc_category_lookup}.category_tree_id IS NOT NULL";

		return $sql_query_params;
	}

	/**
	 * Fills ORDER BY clause of SQL request based on user supplied parameters.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param array  $sql_query  Current SQL query array.
	 * @param string $from_arg   Name of the FROM sql param.
	 * @param string $id_cell    ID cell identifier, like `table_name.id_column_name`.
	 * @return array
	 */
	protected function get_order_by_params( $query_args, $sql_query, $from_arg, $id_cell ) {
		global $wpdb;
		$lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query['order_by_clause'] = '';
		if ( isset( $query_args['orderby'] ) ) {
			$sql_query['order_by_clause'] = $this->normalize_order_by( $query_args['orderby'] );
		}

		$sql_query['outer_from_clause'] = '';
		if ( false !== strpos( $sql_query['order_by_clause'], '_terms' ) ) {
			$sql_query[ $from_arg ] .= " JOIN {$wpdb->prefix}terms AS _terms ON {$id_cell} = _terms.term_id";
		}

		if ( isset( $query_args['order'] ) ) {
			$sql_query['order_by_clause'] .= ' ' . $query_args['order'];
		} else {
			$sql_query['order_by_clause'] .= ' DESC';
		}

		return $sql_query;
	}

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'date' === $order_by ) {
			return 'time_interval';
		}
		if ( 'category' === $order_by ) {
			return '_terms.name';
		}
		return $order_by;
	}

	/**
	 * Returns an array of ids of included categories, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_included_categories_array( $query_args ) {
		if ( isset( $query_args['categories'] ) && is_array( $query_args['categories'] ) && count( $query_args['categories'] ) > 0 ) {
			return $query_args['categories'];
		}
		return array();
	}

	/**
	 * Returns comma separated ids of included categories, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_included_categories( $query_args ) {
		$included_categories = $this->get_included_categories_array( $query_args );
		return implode( ',', $included_categories );
	}

	/**
	 * Returns the page of data according to page number and items per page.
	 *
	 * @param array   $data           Data to paginate.
	 * @param integer $page_no        Page number.
	 * @param integer $items_per_page Number of items per page.
	 * @return array
	 */
	protected function page_records( $data, $page_no, $items_per_page ) {
		$offset = ( $page_no - 1 ) * $items_per_page;
		return array_slice( $data, $offset, $items_per_page );
	}

	/**
	 * Enriches the category data.
	 *
	 * @param array $categories_data Categories data.
	 * @param array $query_args  Query parameters.
	 */
	protected function include_extended_info( &$categories_data, $query_args ) {
		foreach ( $categories_data as $key => $category_data ) {
			$extended_info = new \ArrayObject();
			if ( $query_args['extended_info'] ) {
				$extended_info['name'] = get_the_category_by_ID( $category_data['category_id'] );
			}
			$categories_data[ $key ]['extended_info'] = $extended_info;
		}
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page'      => get_option( 'posts_per_page' ),
			'page'          => 1,
			'order'         => 'DESC',
			'orderby'       => 'date',
			'before'        => TimeInterval::default_before(),
			'after'         => TimeInterval::default_after(),
			'fields'        => '*',
			'categories'    => array(),
			'extended_info' => false,
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->normalize_timezones( $query_args, $defaults );

		/*
		 * We need to get the cache key here because
		 * parent::update_intervals_sql_params() modifies $query_args.
		 */
		$cache_key = $this->get_cache_key( $query_args );
		$data      = $this->get_cached_data( $cache_key );

		if ( false === $data ) {
			$data = (object) array(
				'data'    => array(),
				'total'   => 0,
				'pages'   => 0,
				'page_no' => 0,
			);

			$selections          = $this->selected_columns( $query_args );
			$sql_query_params    = $this->get_sql_query_params( $query_args );
			$included_categories = $this->get_included_categories_array( $query_args );

			if ( count( $included_categories ) > 0 ) {
				$fields          = $this->get_fields( $query_args );
				$join_selections = $this->format_join_selections( array_merge( array( 'category_id' ), $fields ), array( 'category_id' ) );
				$ids_table       = $this->get_ids_table( $included_categories, 'category_id' );

				$prefix     = "SELECT {$join_selections} FROM (";
				$suffix     = ") AS {$table_name}";
				$right_join = "RIGHT JOIN ( {$ids_table} ) AS default_results
					ON default_results.category_id = {$table_name}.category_id";
			} else {
				$prefix     = '';
				$suffix     = '';
				$right_join = '';
			}

			$categories_data = $wpdb->get_results(
				"${prefix}
					SELECT
						{$wpdb->wc_category_lookup}.category_tree_id as category_id,
						{$selections}
					FROM
						{$table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
					GROUP BY
						{$wpdb->wc_category_lookup}.category_tree_id
				{$suffix}
					{$right_join}
					{$sql_query_params['outer_from_clause']}
					ORDER BY
						{$sql_query_params['order_by_clause']}
					",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $categories_data ) {
				return new \WP_Error( 'woocommerce_reports_categories_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce-admin' ), array( 'status' => 500 ) );
			}

			$record_count = count( $categories_data );
			$total_pages  = (int) ceil( $record_count / $query_args['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$categories_data = $this->page_records( $categories_data, $query_args['page'], $query_args['per_page'] );
			$this->include_extended_info( $categories_data, $query_args );
			$categories_data = array_map( array( $this, 'cast_numbers' ), $categories_data );
			$data            = (object) array(
				'data'    => $categories_data,
				'total'   => $record_count,
				'pages'   => $total_pages,
				'page_no' => (int) $query_args['page'],
			);

			$this->set_cached_data( $cache_key, $data );
		}

		return $data;
	}
}
