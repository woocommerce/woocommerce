<?php
/**
 * API\Reports\Products\DataStore class file.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Products;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use \Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use \Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * API\Reports\Products\DataStore.
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
		'date_start'       => 'strval',
		'date_end'         => 'strval',
		'product_id'       => 'intval',
		'items_sold'       => 'intval',
		'net_revenue'      => 'floatval',
		'orders_count'     => 'intval',
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
	);

	/**
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'product_id'   => 'product_id',
		'items_sold'   => 'SUM(product_qty) as items_sold',
		'net_revenue'  => 'SUM(product_net_revenue) AS net_revenue',
		'orders_count' => 'COUNT( DISTINCT ( CASE WHEN product_gross_revenue >= 0 THEN order_id END ) ) as orders_count',
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
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		add_action( 'woocommerce_reports_delete_order_stats', array( __CLASS__, 'sync_on_order_delete' ), 10 );
	}

	/**
	 * Fills FROM clause of SQL request based on user supplied parameters.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $arg_name   Name of the FROM sql param.
	 * @param string $id_cell    ID cell identifier, like `table_name.id_column_name`.
	 * @return array
	 */
	protected function get_from_sql_params( $query_args, $arg_name, $id_cell ) {
		global $wpdb;
		$sql_query['outer_from_clause'] = '';

		// Order by product name requires extra JOIN.
		if ( 'product_name' === $query_args['orderby'] ) {
			$sql_query[ $arg_name ] .= " JOIN {$wpdb->prefix}posts AS _products ON {$id_cell} = _products.ID";
		}
		if ( 'sku' === $query_args['orderby'] ) {
			$sql_query[ $arg_name ] .= " JOIN {$wpdb->prefix}postmeta AS postmeta ON {$id_cell} = postmeta.post_id AND postmeta.meta_key = '_sku'";
		}
		if ( 'variations' === $query_args['orderby'] ) {
			$sql_query[ $arg_name ] .= " LEFT JOIN ( SELECT post_parent, COUNT(*) AS variations FROM {$wpdb->prefix}posts WHERE post_type = 'product_variation' GROUP BY post_parent ) AS _variations ON {$id_cell} = _variations.post_parent";
		}

		return $sql_query;
	}

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
			$sql_query_params                  = array_merge( $sql_query_params, $this->get_from_sql_params( $query_args, 'outer_from_clause', 'default_results.product_id' ) );
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.product_id IN ({$included_products})";
		} else {
			$sql_query_params = array_merge( $sql_query_params, $this->get_from_sql_params( $query_args, 'from_clause', "{$order_product_lookup_table}.product_id" ) );
		}

		$included_variations = $this->get_included_variations( $query_args );
		if ( $included_variations ) {
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.variation_id IN ({$included_variations})";
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$sql_query_params['from_clause']  .= " JOIN {$wpdb->prefix}wc_order_stats ON {$order_product_lookup_table}.order_id = {$wpdb->prefix}wc_order_stats.order_id";
			$sql_query_params['where_clause'] .= " AND ( {$order_status_filter} )";
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
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		if ( 'date' === $order_by ) {
			return $order_product_lookup_table . '.date_created';
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
								FROM {$wpdb->prefix}woocommerce_order_items i, {$wpdb->prefix}woocommerce_order_itemmeta m
								WHERE i.order_item_id = m.order_item_id
								AND m.meta_key = '_product_id'
								AND m.meta_value = %s
								ORDER BY i.order_item_id DESC
								LIMIT 1",
								$product_id
							)
						);
					}

					/* translators: %s is product name */
					$products_data[ $key ]['extended_info']['name'] = $product_names[ $product_id ] ? sprintf( __( '%s (Deleted)', 'woocommerce-admin' ), $product_names[ $product_id ] ) : __( '(Deleted)', 'woocommerce-admin' );
					continue;
				}

				$extended_attributes = apply_filters( 'woocommerce_rest_reports_products_extended_attributes', $this->extended_attributes, $product_data );
				foreach ( $extended_attributes as $extended_attribute ) {
					if ( 'variations' === $extended_attribute ) {
						if ( ! $product->is_type( 'variable' ) ) {
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
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page'         => get_option( 'posts_per_page' ),
			'page'             => 1,
			'order'            => 'DESC',
			'orderby'          => 'date',
			'before'           => TimeInterval::default_before(),
			'after'            => TimeInterval::default_after(),
			'fields'           => '*',
			'categories'       => array(),
			'product_includes' => array(),
			'extended_info'    => false,
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
			$sql_query_params  = $this->get_sql_query_params( $query_args );
			$included_products = $this->get_included_products_array( $query_args );

			if ( count( $included_products ) > 0 ) {
				$total_results = count( $included_products );
				$total_pages   = (int) ceil( $total_results / $sql_query_params['per_page'] );

				if ( 'date' === $query_args['orderby'] ) {
					$selections .= ", {$table_name}.date_created";
				}

				$fields          = $this->get_fields( $query_args );
				$join_selections = $this->format_join_selections( $fields, array( 'product_id' ) );
				$ids_table       = $this->get_ids_table( $included_products, 'product_id' );
				$prefix          = "SELECT {$join_selections} FROM (";
				$suffix          = ") AS {$table_name}";
				$right_join      = "RIGHT JOIN ( {$ids_table} ) AS default_results
					ON default_results.product_id = {$table_name}.product_id";
			} else {
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
									product_id
									) AS tt"
				); // WPCS: cache ok, DB call ok, unprepared SQL ok.

				$total_results = $db_records_count;
				$total_pages   = (int) ceil( $db_records_count / $sql_query_params['per_page'] );

				if ( ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) ) {
					return $data;
				}

				$prefix     = '';
				$suffix     = '';
				$right_join = '';
			}

			$product_data = $wpdb->get_results(
				"${prefix}
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
						product_id
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
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return 'woocommerce_' . self::TABLE_NAME . '_' . md5( wp_json_encode( $params ) );
	}

	/**
	 * Create or update an entry in the wc_admin_order_product_lookup table for an order.
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

		$order_items = $order->get_items();
		$num_updated = 0;

		foreach ( $order_items as $order_item ) {
			$order_item_id       = $order_item->get_id();
			$product_qty         = $order_item->get_quantity( 'edit' );
			$shipping_amount     = $order->get_item_shipping_amount( $order_item );
			$shipping_tax_amount = $order->get_item_shipping_tax_amount( $order_item );
			$coupon_amount       = $order->get_item_coupon_amount( $order_item );

			// Skip line items without changes to product quantity.
			if ( ! $product_qty ) {
				$num_updated++;
				continue;
			}

			// Tax amount.
			$tax_amount  = 0;
			$order_taxes = $order->get_taxes();
			$tax_data    = $order_item->get_taxes();
			foreach ( $order_taxes as $tax_item ) {
				$tax_item_id = $tax_item->get_rate_id();
				$tax_amount += isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : 0;
			}

			$net_revenue = $order_item->get_subtotal( 'edit' );

			$result = $wpdb->replace(
				$wpdb->prefix . self::TABLE_NAME,
				array(
					'order_item_id'         => $order_item_id,
					'order_id'              => $order->get_id(),
					'product_id'            => wc_get_order_item_meta( $order_item_id, '_product_id' ),
					'variation_id'          => wc_get_order_item_meta( $order_item_id, '_variation_id' ),
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
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			/**
			 * Fires when product's reports are updated.
			 *
			 * @param int $order_item_id Order Item ID.
			 * @param int $order_id      Order ID.
			 */
			do_action( 'woocommerce_reports_update_product', $order_item_id, $order->get_id() );

			// Sum the rows affected. Using REPLACE can affect 2 rows if the row already exists.
			$num_updated += 2 === intval( $result ) ? 1 : intval( $result );
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

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM ${table_name} WHERE order_id = %d",
				$order_id
			)
		);

		/**
		 * Fires when product's reports are removed from database.
		 *
		 * @param int $product_id Product ID.
		 * @param int $order_id   Order ID.
		 */
		do_action( 'woocommerce_reports_delete_product', 0, $order_id );
	}
}
