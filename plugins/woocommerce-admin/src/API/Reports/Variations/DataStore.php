<?php
/**
 * API\Reports\Variations\DataStore class file.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Variations;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use \Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use \Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * API\Reports\Variations\DataStore.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {

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
		'date_start'   => 'strval',
		'date_end'     => 'strval',
		'product_id'   => 'intval',
		'variation_id' => 'intval',
		'items_sold'   => 'intval',
		'net_revenue'  => 'floatval',
		'orders_count' => 'intval',
		'name'         => 'strval',
		'price'        => 'floatval',
		'image'        => 'strval',
		'permalink'    => 'strval',
		'sku'          => 'strval',
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'product_id'   => 'product_id',
		'variation_id' => 'variation_id',
		'items_sold'   => 'SUM(product_qty) as items_sold',
		'net_revenue'  => 'SUM(product_net_revenue) AS net_revenue',
		'orders_count' => 'COUNT(DISTINCT order_id) as orders_count',
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
		'sku',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// Avoid ambigious column order_id in SQL query.
		$this->report_columns['orders_count'] = str_replace( 'order_id', $table_name . '.order_id', $this->report_columns['orders_count'] );
	}

	/**
	 * Fills FROM clause of SQL request based on user supplied parameters.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $arg_name   Name of the FROM sql param.
	 * @return array
	 */
	protected function get_from_sql_params( $query_args, $arg_name ) {
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query['from_clause']       = '';
		$sql_query['outer_from_clause'] = '';
		if ( 'sku' === $query_args['orderby'] ) {
			$sql_query[ $arg_name ] .= " JOIN {$wpdb->prefix}postmeta AS postmeta ON {$order_product_lookup_table}.variation_id = postmeta.post_id AND postmeta.meta_key = '_sku'";
		}

		return $sql_query;
	}

	/**
	 * Updates the database query with parameters used for Products report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query_params = $this->get_time_period_sql_params( $query_args, $order_product_lookup_table );
		$sql_query_params = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );

		if ( count( $query_args['variations'] ) > 0 ) {
			$sql_query_params = array_merge( $sql_query_params, $this->get_from_sql_params( $query_args, 'outer_from_clause' ) );
		} else {
			$sql_query_params = array_merge( $sql_query_params, $this->get_from_sql_params( $query_args, 'from_clause' ) );
		}

		$included_products = $this->get_included_products( $query_args );
		if ( $included_products ) {
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.product_id IN ({$included_products})";
		}

		if ( count( $query_args['variations'] ) > 0 ) {
			$allowed_variations_str            = implode( ',', $query_args['variations'] );
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.variation_id IN ({$allowed_variations_str})";
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$sql_query_params['from_clause']  .= " JOIN {$wpdb->prefix}wc_order_stats ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}wc_order_stats.order_id";
			$sql_query_params['where_clause'] .= " AND ( {$order_status_filter} )";
			$sql_query_params['where_clause'] .= ' AND variation_id > 0';
		}

		return $sql_query_params;
	}

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @param string $order_by Sorting criterion.
	 *
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		if ( 'date' === $order_by ) {
			return $table_name . '.date_created';
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
	 * @param array $query_args Query parameters.
	 */
	protected function include_extended_info( &$products_data, $query_args ) {
		foreach ( $products_data as $key => $product_data ) {
			$extended_info = new \ArrayObject();
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
	 * @param array $query_args Query parameters.
	 *
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
			'products'      => array(),
			'variations'    => array(),
			'extended_info' => false,
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->normalize_timezones( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$data = (object) array(
				'data'    => array(),
				'total'   => 0,
				'pages'   => 0,
				'page_no' => 0,
			);

			$selections        = $this->selected_columns( $query_args );
			$included_products = $this->get_included_products_array( $query_args );

			if ( count( $included_products ) > 0 && count( $query_args['variations'] ) > 0 ) {
				$sql_query_params = $this->get_sql_query_params( $query_args );

				if ( 'date' === $query_args['orderby'] ) {
					$selections .= ", {$table_name}.date_created";
				}

				$total_results = count( $query_args['variations'] );
				$total_pages   = (int) ceil( $total_results / $sql_query_params['per_page'] );

				$fields          = $this->get_fields( $query_args );
				$join_selections = $this->format_join_selections( $fields, array( 'product_id', 'variation_id' ) );
				$ids_table       = $this->get_ids_table( $query_args['variations'], 'variation_id', array( 'product_id' => $included_products[0] ) );

				$prefix     = "SELECT {$join_selections} FROM (";
				$suffix     = ") AS {$table_name}";
				$right_join = "RIGHT JOIN ( {$ids_table} ) AS default_results
					ON default_results.variation_id = {$table_name}.variation_id";
			} else {
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
									{$sql_query_params['where_time_clause']}
									{$sql_query_params['where_clause']}
								GROUP BY
									variation_id
								) AS tt"
				); // WPCS: cache ok, DB call ok, unprepared SQL ok.

				$total_results = $db_records_count;
				$total_pages   = (int) ceil( $db_records_count / $sql_query_params['per_page'] );

				if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
					return $data;
				}

				$prefix     = '';
				$suffix     = '';
				$right_join = '';
			}

			$product_data = $wpdb->get_results(
				"{$prefix}
					SELECT
						{$selections}
					FROM
						{$table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
					GROUP BY
						variation_id
				{$suffix}
					{$right_join}
					{$sql_query_params['outer_from_clause']}
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
				'total'   => $total_results,
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
	 *
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return 'woocommerce_' . self::TABLE_NAME . '_' . md5( wp_json_encode( $params ) );
	}

}
