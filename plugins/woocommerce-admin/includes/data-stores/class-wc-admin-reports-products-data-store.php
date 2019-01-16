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
class WC_Admin_Reports_Products_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

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
		'category_ids',
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
		add_action( 'save_post', array( __CLASS__, 'sync_order_products' ) );
		add_action( 'clean_post_cache', array( __CLASS__, 'sync_order_products' ) );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'sync_order_products' ) );
	}

	/**
	 * Fills ORDER BY clause of SQL request based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_order_by_sql_params( $query_args ) {
		global $wpdb;
		$order_product_lookup_table = $wpdb->prefix . self::TABLE_NAME;

		$sql_query['order_by_clause'] = '';
		if ( isset( $query_args['orderby'] ) ) {
			$sql_query['order_by_clause'] = $this->normalize_order_by( $query_args['orderby'] );
		}
		// Order by product name requires extra JOIN.
		if ( false !== strpos( $sql_query['order_by_clause'], '_products' ) ) {
			$sql_query['from_clause'] .= " JOIN {$wpdb->prefix}posts AS _products ON {$order_product_lookup_table}.product_id = _products.ID";
		}

		if ( 'postmeta.meta_value' === $sql_query['order_by_clause'] ) {
			$sql_query['from_clause'] .= " JOIN {$wpdb->prefix}postmeta AS postmeta ON {$order_product_lookup_table}.product_id = postmeta.post_id AND postmeta.meta_key = '_sku'";
		}

		if ( isset( $query_args['order'] ) ) {
			$sql_query['order_by_clause'] .= ' ' . $query_args['order'];
		} else {
			$sql_query['order_by_clause'] .= ' DESC';
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
			$sql_query_params['where_clause'] .= " AND {$order_product_lookup_table}.product_id IN ({$included_products})";
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
			return '_products.post_title';
		}
		if ( 'sku' === $order_by ) {
			return 'postmeta.meta_value';
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
				$product             = wc_get_product( $product_data['product_id'] );
				$extended_attributes = apply_filters( 'woocommerce_rest_reports_products_extended_attributes', $this->extended_attributes, $product_data );
				foreach ( $extended_attributes as $extended_attribute ) {
					$function = 'get_' . $extended_attribute;
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
		$now        = time();
		$week_back  = $now - WEEK_IN_SECONDS;

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults = array(
			'per_page'         => get_option( 'posts_per_page' ),
			'page'             => 1,
			'order'            => 'DESC',
			'orderby'          => 'date',
			'before'           => date( WC_Admin_Reports_Interval::$iso_datetime_format, $now ),
			'after'            => date( WC_Admin_Reports_Interval::$iso_datetime_format, $week_back ),
			'fields'           => '*',
			'categories'       => array(),
			'product_includes' => array(),
			'extended_info'    => false,
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
								{$sql_query_params['where_time_clause']}
								{$sql_query_params['where_clause']}
							GROUP BY
								product_id
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
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
					GROUP BY
						product_id
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

	/**
	 * Create or update an entry in the wc_admin_order_product_lookup table for an order.
	 *
	 * @since 3.5.0
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function sync_order_products( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );

		// This hook gets called on refunds as well, so return early to avoid errors.
		if ( ! $order || 'shop_order_refund' === $order->get_type() ) {
			return;
		}

		$refunds = self::get_order_refund_items( $order );

		foreach ( $order->get_items() as $order_item ) {
			$order_item_id     = $order_item->get_id();
			$quantity_refunded = isset( $refunds[ $order_item_id ] ) ? $refunds[ $order_item_id ]['quantity'] : 0;
			$amount_refunded   = isset( $refunds[ $order_item_id ] ) ? $refunds[ $order_item_id ]['subtotal'] : 0;
			$product_qty       = $order_item->get_quantity( 'edit' ) - $quantity_refunded;

			// Shipping amount based on woocommerce code in includes/admin/meta-boxes/views/html-order-item(s).php
			// distributed simply based on number of line items.
			$order_items = $order->get_item_count();
			$refunded    = $order->get_total_shipping_refunded();
			if ( $refunded > 0 ) {
				$total_shipping_amount = $order->get_shipping_total() - $refunded;
			} else {
				$total_shipping_amount = $order->get_shipping_total();
			}
			$shipping_amount = $total_shipping_amount / $order_items * $product_qty;

			// Shipping amount tax based on woocommerce code in includes/admin/meta-boxes/views/html-order-item(s).php
			// distribute simply based on number of line items.
			$shipping_tax_amount = 0;
			// TODO: if WC is currently not tax enabled, but it was before (or vice versa), would this work correctly?
			$order_taxes               = $order->get_taxes();
			$line_items_shipping       = $order->get_items( 'shipping' );
			$total_shipping_tax_amount = 0;
			foreach ( $line_items_shipping as $item_id => $item ) {
				$tax_data = $item->get_taxes();
				if ( $tax_data ) {
					foreach ( $order_taxes as $tax_item ) {
						$tax_item_id    = $tax_item->get_rate_id();
						$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
						$refunded       = $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'shipping' );
						if ( $refunded ) {
							$total_shipping_tax_amount += $tax_item_total - $refunded;
						} else {
							$total_shipping_tax_amount += $tax_item_total;
						}
					}
				}
			}
			$shipping_tax_amount = $total_shipping_tax_amount / $order_items * $product_qty;

			// Tax amount.
			// TODO: check if this calculates tax correctly with refunds.
			$tax_amount = 0;

			$order_taxes = $order->get_taxes();
			$tax_data    = $order_item->get_taxes();
			foreach ( $order_taxes as $tax_item ) {
				$tax_item_id = $tax_item->get_rate_id();
				$tax_amount += isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : 0;
			}

			// TODO: should net revenue be affected by refunds, as refunds are tracked separately?
			$net_revenue = $order_item->get_subtotal( 'edit' ) - $amount_refunded;

			// Coupon calculation based on woocommerce code in includes/admin/meta-boxes/views/html-order-item.php.
			$coupon_amount = $order_item->get_subtotal( 'edit' ) - $order_item->get_total( 'edit' );

			if ( $quantity_refunded >= $order_item->get_quantity( 'edit' ) ) {
				$wpdb->delete(
					$wpdb->prefix . self::TABLE_NAME,
					array( 'order_item_id' => $order_item_id ),
					array( '%d' )
				); // WPCS: cache ok, DB call ok.
			} else {
				$wpdb->replace(
					$wpdb->prefix . self::TABLE_NAME,
					array(
						'order_item_id'         => $order_item_id,
						'order_id'              => $order->get_id(),
						'product_id'            => $order_item->get_product_id( 'edit' ),
						'variation_id'          => $order_item->get_variation_id( 'edit' ),
						'customer_id'           => ( 0 < $order->get_customer_id( 'edit' ) ) ? $order->get_customer_id( 'edit' ) : null,
						'product_qty'           => $product_qty,
						'product_net_revenue'   => $net_revenue,
						'date_created'          => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
						'coupon_amount'         => $coupon_amount,
						'tax_amount'            => $tax_amount,
						'shipping_amount'       => $shipping_amount,
						'shipping_tax_amount'   => $shipping_tax_amount,
						// TODO: can this be incorrect if modified by filters?
						'product_gross_revenue' => $net_revenue + $tax_amount + $shipping_amount + $shipping_tax_amount,
						'refund_amount'         => $amount_refunded,
					),
					array(
						'%d',
						'%d',
						'%d',
						'%d',
						'%d',
						'%d',
						'%f',
						'%s',
						'%f',
						'%f',
						'%f',
						'%f',
						'%f',
						'%f',
						'%f',
					)
				); // WPCS: cache ok, DB call ok, unprepared SQL ok.
			}
		}
	}

	/**
	 * Get order refund items quantity and subtotal
	 *
	 * @param object $order WC Order object.
	 * @return array
	 */
	public static function get_order_refund_items( $order ) {
		$refunds             = $order->get_refunds();
		$refunded_line_items = array();
		foreach ( $refunds as $refund ) {
			foreach ( $refund->get_items() as $refunded_item ) {
				$line_item_id = wc_get_order_item_meta( $refunded_item->get_id(), '_refunded_item_id', true );
				if ( ! isset( $refunded_line_items[ $line_item_id ] ) ) {
					$refunded_line_items[ $line_item_id ]['quantity'] = 0;
					$refunded_line_items[ $line_item_id ]['subtotal'] = 0;
				}
				$refunded_line_items[ $line_item_id ]['quantity'] += absint( $refunded_item['quantity'] );
				$refunded_line_items[ $line_item_id ]['subtotal'] += abs( $refunded_item['subtotal'] );
			}
		}
		return $refunded_line_items;
	}

}
