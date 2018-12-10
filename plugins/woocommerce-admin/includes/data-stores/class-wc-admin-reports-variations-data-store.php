<?php
/**
 * WC_Admin_Reports_Products_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Products_Data_Store.
 */
class WC_Admin_Reports_Variations_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_order_product_lookup';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'date_start'    => 'strval',
		'date_end'      => 'strval',
		'product_id'    => 'intval',
		'variation_id'  => 'intval',
		'items_sold'    => 'intval',
		'gross_revenue' => 'floatval',
		'orders_count'  => 'intval',
		'name'          => 'strval',
		'price'         => 'floatval',
		'image'         => 'strval',
		'permalink'     => 'strval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'product_id'    => 'product_id',
		'variation_id'  => 'variation_id',
		'items_sold'    => 'SUM(product_qty) as items_sold',
		'gross_revenue' => 'SUM(product_gross_revenue) AS gross_revenue',
		'orders_count'  => 'COUNT(DISTINCT order_id) as orders_count',
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
		'low_stock_amount',
	);


	/**
	 * Updates the database query with parameters used for Products report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $order_product_lookup_table );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		$included_products = $this->get_included_products( $query_args );
		if ( $included_products ) {
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.product_id IN ({$included_products})";
		}

		if ( count( $query_args['variations'] ) > 0 ) {
			$allowed_variations_str            = implode( ',', $query_args['variations'] );
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.variation_id IN ({$allowed_variations_str})";
		}

		if ( is_array( $query_args['order_status'] ) && count( $query_args['order_status'] ) > 0 ) {
			$statuses = array_map( array( $this, 'normalize_order_status' ), $query_args['order_status'] );

			$sql_query_params['from_clause']  .= " JOIN {$wpdb->prefix}posts ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}posts.ID";
			$sql_query_params['where_clause'] .= " AND {$wpdb->prefix}posts.post_status IN ( '" . implode( "','", $statuses ) . "' ) ";
			$sql_query_params['where_clause'] .= ' AND variation_id > 0';
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
	 * Enriches the product data with attributes specified by the extended_attributes.
	 *
	 * @param array $products_data Product data.
	 * @param array $query_args  Query parameters.
	 */
	protected function include_extended_info( &$products_data, $query_args ) {
		foreach ( $products_data as $key => $product_data ) {
			$extended_info = new ArrayObject();
			if ( $query_args['extended_info'] ) {
				$extended_attributes = apply_filters( 'woocommerce_rest_reports_variations_extended_attributes', $this->extended_attributes, $product_data );
				$product             = wc_get_product( $product_data['product_id'] );
				$variations          = array();
				if ( method_exists( $product, 'get_available_variations' ) ) {
					$variations = $product->get_available_variations();
				}
				foreach ( $variations as $variation ) {
					if ( (int) $variation['variation_id'] === (int) $product_data['variation_id'] ) {
						$attributes        = array();
						$variation_product = wc_get_product( $variation['variation_id'] );
						foreach ( $extended_attributes as $extended_attribute ) {
							$function = 'get_' . $extended_attribute;
							if ( is_callable( array( $variation_product, $function ) ) ) {
								$value                                = $variation_product->{$function}();
								$extended_info[ $extended_attribute ] = $value;
							}
						}
						foreach ( $variation['attributes'] as $attribute_name => $attribute ) {
							$name         = str_replace( 'attribute_', '', $attribute_name );
							$option_term  = get_term_by( 'slug', $attribute, $name );
							$attributes[] = array(
								'id'     => wc_attribute_taxonomy_id_by_name( $name ),
								'name'   => str_replace( 'pa_', '', $name ),
								'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
							);
						}
						$extended_info['attributes'] = $attributes;
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
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$now        = time();
		$week_back  = $now - WEEK_IN_SECONDS;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults = array(
			'per_page'      => get_option( 'posts_per_page' ),
			'page'          => 1,
			'order'         => 'DESC',
			'orderby'       => 'date',
			'before'        => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'         => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'        => '*',
			'products'      => array(),
			'variations'    => array(),
			'extended_info' => false,
			// This is not a parameter for products reports per se, but we want to only take into account selected order types.
			'order_status'  => parent::get_report_order_statuses(),

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

			$db_records_count = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM (
							SELECT
								product_id
							FROM
								{$table_name}
								{$sql_query_params['from_clause']}
							WHERE
								1=1
								{$sql_query_params['where_clause']}
							GROUP BY
								variation_id
					  		) AS tt"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$total_pages = (int) ceil( $db_records_count / $sql_query_params['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}

			$product_data = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_clause']}
					GROUP BY
						variation_id
					ORDER BY
						{$sql_query_params['order_by_clause']}
					{$sql_query_params['limit']}
					",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			if ( null === $product_data ) {
				return $data;
			}

			$this->include_extended_info( $product_data, $query_args );

			$product_data = array_map( array( $this, 'cast_numbers' ), $product_data );
			$data         = (object) array(
				'data'    => $product_data,
				'total'   => $db_records_count,
				'pages'   => $total_pages,
				'page_no' => (int) $query_args['page'],
			);

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
	}

	/**
	 * Returns string to be used as cache key for the data.
	 *
	 * @param array $params Query parameters.
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return 'woocommerce_' . self::TABLE_NAME . '_' . md5( wp_json_encode( $params ) );
	}

}
