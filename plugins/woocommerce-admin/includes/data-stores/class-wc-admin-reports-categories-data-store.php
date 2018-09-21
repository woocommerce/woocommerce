<?php
/**
 * WC_Admin_Reports_Categories_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;


/**
 * WC_Admin_Reports_Categories_Data_Store.
 *
 */
class WC_Admin_Reports_Categories_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_product_lookup';

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
		'gross_revenue'  => 'floatval',
		'orders_count'   => 'intval',
		'products_count' => 'intval',
	);

	/**
	 * SQL columns to select in the db query.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'items_sold'    => 'SUM(product_qty) as items_sold',
		'gross_revenue' => 'SUM(product_gross_revenue) AS gross_revenue',
		'orders_count'  => 'COUNT(DISTINCT order_id) as orders_count',
		// 'products_count' is not a SQL column at the moment, see below.
	);

	/**
	 * Return the database query with parameters used for Categories report: time span and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;

		$sql_query_params = $this->get_time_period_sql_params( $query_args );
		// Limit is left out here so that the grouping in code by PHP can be applied correctly.
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$allowed_products = $this->get_allowed_products( $query_args );

		if ( count( $allowed_products ) > 0 ) {
			$allowed_products_str              = implode( ',', $allowed_products );
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.product_id IN ({$allowed_products_str})";
		}

		if ( is_array( $query_args['order_status'] ) && count( $query_args['order_status'] ) > 0 ) {
			$statuses = array_map( array( $this, 'normalize_order_status' ), $query_args['order_status'] );

			$sql_query_params['from_clause']  .= " JOIN {$wpdb->prefix}posts ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}posts.ID";
			$sql_query_params['where_clause'] .= " AND {$wpdb->prefix}posts.post_status IN ( '" . implode( "','", $statuses ) . "' ) ";
		}

		return $sql_query_params;
	}

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'date' === $order_by ) {
			return 'date_created';
		}

		return $order_by;
	}

	/**
	 * Compares values in 2 arrays based on criterion specified when called via sort_records.
	 *
	 * @param array $a Array 1 to compare.
	 * @param array $b Array 2 to compare.
	 * @return integer|WP_Error
	 */
	private function sort_callback( $a, $b ) {
		if ( '' === $this->order_by || '' === $this->order ) {
			return new WP_Error( 'woocommerce_reports_categories_sort_failed', __( 'Sorry, fetching categories data failed.', 'woocommerce' ) );
		}
		if ( $a[ $this->order_by ] === $b[ $this->order_by ] ) {
			return 0;
		} elseif ( $a[ $this->order_by ] > $b[ $this->order_by ] ) {
			return strtolower( $this->order ) === 'desc' ? -1 : 1;
		} elseif ( $a[ $this->order_by ] < $b[ $this->order_by ] ) {
			return strtolower( $this->order ) === 'desc' ? 1 : -1;
		}
	}

	/**
	 * Sorts the data based on given sorting criterion and order.
	 *
	 * @param array  $data      Data to sort.
	 * @param string $sort_by   Sorting criterion, any of the column_types keys.
	 * @param string $direction Sorting direction: asc/desc.
	 */
	protected function sort_records( &$data, $sort_by, $direction ) {
		$this->order_by = $this->normalize_order_by( $sort_by );
		$this->order    = $direction;
		usort( $data, array( $this, 'sort_callback' ) );
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
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$now        = time();
		$week_back  = $now - WEEK_IN_SECONDS;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page'     => get_option( 'posts_per_page' ),
			'page'         => 1,
			'order'        => 'DESC',
			'orderby'      => 'date',
			'before'       => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'        => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'       => '*',
			'categories'   => array(),
			// This is not a parameter for products reports per se, but maybe we should restricts order statuses here, too?
			'order_status' => parent::get_report_order_statuses(),

		);
		$query_args = wp_parse_args( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$data = (object) array(
				'data'    => array(),
				'total'   => 0,
				'pages'   => 0,
				'page_no' => 0,
			);

			$selections       = $this->selected_columns( $query_args );
			$sql_query_params = $this->get_sql_query_params( $query_args );

			$products_data = $wpdb->get_results(
				"SELECT
						product_id,
						date_created,
						{$selections}
					FROM
						{$table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_clause']}
					GROUP BY
						product_id
					", ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $products_data ) {
				return new WP_Error( 'woocommerce_reports_categories_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce' ), array( 'status' => 500 ) );
			}

			// Group by category without a helper table, worst case we add it and change the SQL afterwards.
			// Other option would be a join with wp_post and taxonomies, but a) performance might be bad, b) how to handle custom product tables?
			$categories_data = array();
			foreach ( $products_data as $product_data ) {
				$categories = get_the_terms( $product_data['product_id'], 'product_cat' );
				foreach ( $categories as $category ) {
					$cat_id = $category->term_id;
					if ( ! key_exists( $cat_id, $categories_data ) ) {
						$categories_data[ $cat_id ] = array(
							'category_id'    => 0,
							'items_sold'     => 0,
							'gross_revenue'  => 0.0,
							'orders_count'   => 0,
							'products_count' => 0,
						);
					}

					$categories_data[ $cat_id ]['category_id']    = $cat_id;
					$categories_data[ $cat_id ]['items_sold']    += $product_data['items_sold'];
					$categories_data[ $cat_id ]['gross_revenue'] += $product_data['gross_revenue'];
					$categories_data[ $cat_id ]['orders_count']  += $product_data['orders_count'];
					$categories_data[ $cat_id ]['products_count'] ++;
				}
			}
			$record_count = count( $categories_data );
			$total_pages  = (int) ceil( $record_count / $query_args['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$this->sort_records( $categories_data, $query_args['orderby'], $query_args['order'] );
			$categories_data = $this->page_records( $categories_data, $query_args['page'], $query_args['per_page'] );

			$categories_data = array_map( array( $this, 'cast_numbers' ), $categories_data );
			$data            = (object) array(
				'data'    => $categories_data,
				'total'   => $record_count,
				'pages'   => $total_pages,
				'page_no' => (int) $query_args['page'],
			);

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

}
