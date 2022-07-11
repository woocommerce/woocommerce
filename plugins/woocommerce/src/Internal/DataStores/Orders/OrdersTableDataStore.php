<?php
/**
 * OrdersTableDataStore class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

defined( 'ABSPATH' ) || exit;

/**
 * This class is the standard data store to be used when the custom orders table is in use.
 */
class OrdersTableDataStore extends \Abstract_WC_Order_Data_Store_CPT implements \WC_Object_Data_Store_Interface, \WC_Order_Data_Store_Interface {

	/**
	 * Handles custom metadata in the wc_orders_meta table.
	 *
	 * @var OrdersTableDataStoreMeta
	 */
	protected $data_store_meta;

	/**
	 * The database util object to use.
	 *
	 * @var DatabaseUtil
	 */
	protected $database_util;


	/**
	 * Initialize the object.
	 *
	 * @internal
	 * @param OrdersTableDataStoreMeta $data_store_meta Metadata instance.
	 * @param DatabaseUtil             $database_util   The database util instance to use.
	 * @return void
	 */
	final public function init( OrdersTableDataStoreMeta $data_store_meta, DatabaseUtil $database_util ) {
		$this->data_store_meta = $data_store_meta;
		$this->database_util   = $database_util;
	}

	/**
	 * Get the custom orders table name.
	 *
	 * @return string The custom orders table name.
	 */
	public static function get_orders_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'wc_orders';
	}

	/**
	 * Get the order addresses table name.
	 *
	 * @return string The order addresses table name.
	 */
	public static function get_addresses_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'wc_order_addresses';
	}

	/**
	 * Get the orders operational data table name.
	 *
	 * @return string The orders operational data table name.
	 */
	public static function get_operational_data_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'wc_order_operational_data';
	}

	/**
	 * Get the orders meta data table name.
	 *
	 * @return string Name of order meta data table.
	 */
	public static function get_meta_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'wc_orders_meta';
	}

	/**
	 * Get the names of all the tables involved in the custom orders table feature.
	 *
	 * @return string[]
	 */
	public function get_all_table_names() {
		return array(
			$this->get_orders_table_name(),
			$this->get_addresses_table_name(),
			$this->get_operational_data_table_name(),
			$this->get_meta_table_name(),
		);
	}

	/**
	 * Table column to WC_Order mapping for wc_orders table.
	 *
	 * @var \string[][]
	 */
	protected $order_column_mapping = array(
		'id'                   => array(
			'type' => 'int',
			'name' => 'id',
		),
		'status'               => array(
			'type' => 'string',
			'name' => 'status',
		),
		'currency'             => array(
			'type' => 'string',
			'name' => 'currency',
		),
		'tax_amount'           => array(
			'type' => 'decimal',
			'name' => 'cart_tax',
		),
		'total_amount'         => array(
			'type' => 'decimal',
			'name' => 'total',
		),
		'customer_id'          => array(
			'type' => 'int',
			'name' => 'customer_id',
		),
		'billing_email'        => array(
			'type' => 'string',
			'name' => 'billing_email',
		),
		'date_created_gmt'     => array(
			'type' => 'date',
			'name' => 'date_created',
		),
		'date_updated_gmt'     => array(
			'type' => 'date',
			'name' => 'date_modified',
		),
		'parent_order_id'      => array(
			'type' => 'int',
			'name' => 'parent_id',
		),
		'payment_method'       => array(
			'type' => 'string',
			'name' => 'payment_method',
		),
		'payment_method_title' => array(
			'type' => 'string',
			'name' => 'payment_method_title',
		),
		'ip_address'           => array(
			'type' => 'string',
			'name' => 'customer_ip_address',
		),
		'transaction_id'       => array(
			'type' => 'string',
			'name' => 'transaction_id',
		),
		'user_agent'           => array(
			'type' => 'string',
			'name' => 'customer_user_agent',
		),
	);

	/**
	 * Table column to WC_Order mapping for billing addresses in wc_address table.
	 *
	 * @var \string[][]
	 */
	protected $billing_address_column_mapping = array(
		'id'           => array( 'type' => 'int' ),
		'order_id'     => array( 'type' => 'int' ),
		'address_type' => array( 'type' => 'string' ),
		'first_name'   => array(
			'type' => 'string',
			'name' => 'billing_first_name',
		),
		'last_name'    => array(
			'type' => 'string',
			'name' => 'billing_last_name',
		),
		'company'      => array(
			'type' => 'string',
			'name' => 'billing_company',
		),
		'address_1'    => array(
			'type' => 'string',
			'name' => 'billing_address_1',
		),
		'address_2'    => array(
			'type' => 'string',
			'name' => 'billing_address_2',
		),
		'city'         => array(
			'type' => 'string',
			'name' => 'billing_city',
		),
		'state'        => array(
			'type' => 'string',
			'name' => 'billing_state',
		),
		'postcode'     => array(
			'type' => 'string',
			'name' => 'billing_postcode',
		),
		'country'      => array(
			'type' => 'string',
			'name' => 'billing_country',
		),
		'email'        => array(
			'type' => 'string',
			'name' => 'billing_email',
		),
		'phone'        => array(
			'type' => 'string',
			'name' => 'billing_phone',
		),
	);

	/**
	 * Table column to WC_Order mapping for shipping addresses in wc_address table.
	 *
	 * @var \string[][]
	 */
	protected $shipping_address_column_mapping = array(
		'id'           => array( 'type' => 'int' ),
		'order_id'     => array( 'type' => 'int' ),
		'address_type' => array( 'type' => 'string' ),
		'first_name'   => array(
			'type' => 'string',
			'name' => 'shipping_first_name',
		),
		'last_name'    => array(
			'type' => 'string',
			'name' => 'shipping_last_name',
		),
		'company'      => array(
			'type' => 'string',
			'name' => 'shipping_company',
		),
		'address_1'    => array(
			'type' => 'string',
			'name' => 'shipping_address_1',
		),
		'address_2'    => array(
			'type' => 'string',
			'name' => 'shipping_address_2',
		),
		'city'         => array(
			'type' => 'string',
			'name' => 'shipping_city',
		),
		'state'        => array(
			'type' => 'string',
			'name' => 'shipping_state',
		),
		'postcode'     => array(
			'type' => 'string',
			'name' => 'shipping_postcode',
		),
		'country'      => array(
			'type' => 'string',
			'name' => 'shipping_country',
		),
		'email'        => array( 'type' => 'string' ),
		'phone'        => array(
			'type' => 'string',
			'name' => 'shipping_phone',
		),
	);

	/**
	 * Table column to WC_Order mapping for wc_operational_data table.
	 *
	 * @var \string[][]
	 */
	protected $operational_data_column_mapping = array(
		'id'                          => array( 'type' => 'int' ),
		'order_id'                    => array( 'type' => 'int' ),
		'created_via'                 => array(
			'type' => 'string',
			'name' => 'created_via',
		),
		'woocommerce_version'         => array(
			'type' => 'string',
			'name' => 'version',
		),
		'prices_include_tax'          => array(
			'type' => 'bool',
			'name' => 'prices_include_tax',
		),
		'coupon_usages_are_counted'   => array(
			'type' => 'bool',
			'name' => 'recorded_coupon_usage_counts',
		),
		'download_permission_granted' => array(
			'type' => 'bool',
			'name' => 'download_permissions_granted',
		),
		'cart_hash'                   => array(
			'type' => 'string',
			'name' => 'cart_hash',
		),
		'new_order_email_sent'        => array(
			'type' => 'bool',
			'name' => 'new_order_email_sent',
		),
		'order_key'                   => array(
			'type' => 'string',
			'name' => 'order_key',
		),
		'order_stock_reduced'         => array(
			'type' => 'bool',
			'name' => 'order_stock_reduced',
		),
		'date_paid_gmt'               => array(
			'type' => 'date',
			'name' => 'date_paid',
		),
		'date_completed_gmt'          => array(
			'type' => 'date',
			'name' => 'date_completed',
		),
		'shipping_tax_amount'         => array(
			'type' => 'decimal',
			'name' => 'shipping_tax',
		),
		'shipping_total_amount'       => array(
			'type' => 'decimal',
			'name' => 'shipping_total',
		),
		'discount_tax_amount'         => array(
			'type' => 'decimal',
			'name' => 'discount_tax',
		),
		'discount_total_amount'       => array(
			'type' => 'decimal',
			'name' => 'discount_total',
		),
		'recorded_sales'              => array(
			'type' => 'bool',
			'name' => 'recorded_sales',
		),
	);

	/**
	 * Cache variable to store combined mapping.
	 *
	 * @var array[][][]
	 */
	private $all_order_column_mapping;

	/**
	 * Return combined mappings for all order tables.
	 *
	 * @return array|\array[][][] Return combined mapping.
	 */
	public function get_all_order_column_mappings() {
		if ( ! isset( $this->all_order_column_mapping ) ) {
			$this->all_order_column_mapping = array(
				'orders'           => $this->order_column_mapping,
				'billing_address'  => $this->billing_address_column_mapping,
				'shipping_address' => $this->shipping_address_column_mapping,
				'operational_data' => $this->operational_data_column_mapping,
			);
		}

		return $this->all_order_column_mapping;
	}

	/**
	 * Backfills order details in to WP_Post DB. Uses WC_Order_Data_store_CPT.
	 *
	 * @param \WC_Order $order Order object to backfill.
	 */
	public function backfill_post_record( $order ) {
		$cpt_data_store = new \WC_Order_Data_Store_CPT();
		$cpt_data_store->update_order_from_object( $order );
		foreach ( $cpt_data_store->get_internal_data_store_key_getters() as $key => $getter_name ) {
			if (
				is_callable( array( $cpt_data_store, "set_$getter_name" ) ) &&
				is_callable( array( $this, "get_$getter_name" ) )
			) {
				call_user_func_array(
					array(
						$cpt_data_store,
						"set_$getter_name",
					),
					array(
						$order,
						$this->{"get_$getter_name"}( $order ),
					)
				);
			}
		}
	}

	/**
	 * Get information about whether permissions are granted yet.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return bool Whether permissions are granted.
	 */
	public function get_download_permissions_granted( $order ) {
		return wc_string_to_bool( $order->get_meta( '_download_permissions_granted', true ) );
	}

	/**
	 * Stores information about whether permissions were generated yet.
	 *
	 * @param \WC_Order $order Order ID or order object.
	 * @param bool      $set True or false.
	 * @param bool      $save Whether to persist changes to db immediately or not.
	 */
	public function set_download_permissions_granted( $order, $set, $save = true ) {
		// XXX implement $save = true.
		return $order->update_meta_data( '_download_permissions_granted', wc_bool_to_string( $set ) );
	}

	/**
	 * Gets information about whether sales were recorded.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return bool Whether sales are recorded.
	 */
	public function get_recorded_sales( $order ) {
		return wc_string_to_bool( $order->get_meta( '_recorded_sales', true ) );
	}

	/**
	 * Stores information about whether sales were recorded.
	 *
	 * @param \WC_Order $order Order object.
	 * @param bool      $set True or false.
	 * @param bool      $save Whether to persist changes to db immediately or not.
	 */
	public function set_recorded_sales( $order, $set, $save = true ) {
		// XXX implement $save = true.
		return $order->update_meta_data( '_recorded_sales', wc_bool_to_string( $set ) );
	}

	/**
	 * Gets information about whether coupon counts were updated.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return bool Whether coupon counts were updated.
	 */
	public function get_recorded_coupon_usage_counts( $order ) {
		return wc_string_to_bool( $order->get_meta( '_recorded_coupon_usage_counts', true ) );
	}

	/**
	 * Stores information about whether coupon counts were updated.
	 *
	 * @param \WC_Order $order Order object.
	 * @param bool      $set True or false.
	 * @param bool      $save Whether to persist changes to db immediately or not.
	 */
	public function set_recorded_coupon_usage_counts( $order, $set, $save = true ) {
		return $order->update_meta_data( '_recorded_coupon_usage_counts', wc_bool_to_string( $set ) );
	}

	/**
	 * Whether email have been sent for this order.
	 *
	 * @param \WC_Order|int $order Order object.
	 *
	 * @return bool Whether email is sent.
	 */
	public function get_email_sent( $order ) {
		return wc_string_to_bool( $order->get_meta( '_new_order_email_sent', true ) );
	}

	/**
	 * Stores information about whether email was sent.
	 *
	 * @param \WC_Order $order Order object.
	 * @param bool      $set True or false.
	 * @param bool      $save Whether to persist changes to db immediately or not.
	 */
	public function set_email_sent( $order, $set, $save = true ) {
		// XXX implement $save = true.
		return $order->update_meta_data( '_new_order_email_sent', wc_bool_to_string( $set ) );
	}

	/**
	 * Helper setter for email_sent.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return bool Whether email was sent.
	 */
	private function get_new_order_email_sent( $order ) {
		return $this->get_email_sent( $order );
	}

	/**
	 * Helper setter for new order email sent.
	 *
	 * @param \WC_Order $order Order object.
	 * @param bool      $set True or false.
	 * @param bool      $save Whether to persist changes to db immediately or not.
	 * @return bool Whether email was sent.
	 */
	private function set_new_order_email_sent( $order, $set, $save = true ) {
		// XXX implement $save = true.
		return $this->set_email_sent( $order, $set );
	}

	/**
	 * Gets information about whether stock was reduced.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return bool Whether stock was reduced.
	 */
	public function get_stock_reduced( $order ) {
		$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;
		return wc_string_to_bool( $order->get_meta( '_order_stock_reduced', true ) );
	}

	/**
	 * Stores information about whether stock was reduced.
	 *
	 * @param \WC_Order $order Order ID or order object.
	 * @param bool      $set True or false.
	 * @param bool      $save Whether to persist changes to db immediately or not.
	 */
	public function set_stock_reduced( $order, $set, $save = true ) {
		// XXX implement $save = true.
		$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;
		return $order->update_meta_data( '_order_stock_reduced', wc_bool_to_string( $set ) );
	}

	/**
	 * Helper getter for `order_stock_reduced`.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool Whether stock was reduced.
	 */
	private function get_order_stock_reduced( $order ) {
		return $this->get_stock_reduced( $order );
	}

	/**
	 * Helper setter for `order_stock_reduced`.
	 *
	 * @param \WC_Order $order Order ID or order object.
	 * @param bool      $set Whether stock was reduced.
	 * @param bool      $save Whether to persist changes to db immediately or not.
	 */
	private function set_order_stock_reduced( $order, $set, $save = true ) {
		return $this->set_stock_reduced( $order, $set, $save );
	}

	//phpcs:disable Squiz.Commenting, Generic.Commenting

	// TODO: Add methods for other table names as appropriate.
	public function get_total_refunded( $order ) {
		// TODO: Implement get_total_refunded() method.
		return 0;
	}

	public function get_total_tax_refunded( $order ) {
		// TODO: Implement get_total_tax_refunded() method.
		return 0;
	}

	public function get_total_shipping_refunded( $order ) {
		// TODO: Implement get_total_shipping_refunded() method.
		return 0;
	}

	public function get_order_id_by_order_key( $order_key ) {
		// TODO: Implement get_order_id_by_order_key() method.
		return 0;
	}

	public function get_order_count( $status ) {
		// TODO: Implement get_order_count() method.
		return 0;
	}

	public function get_orders( $args = array() ) {
		// TODO: Implement get_orders() method.
		return array();
	}

	public function get_unpaid_orders( $date ) {
		// TODO: Implement get_unpaid_orders() method.
		return array();
	}

	public function search_orders( $term ) {
		// TODO: Implement search_orders() method.
		return array();
	}

	public function get_order_type( $order_id ) {
		// TODO: Implement get_order_type() method.
		return 'shop_order';
	}

	//phpcs:enable Squiz.Commenting, Generic.Commenting

	/**
	 * Method to read an order from custom tables.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @throws \Exception If passed order is invalid.
	 */
	public function read( &$order ) {
		$order->set_defaults();
		if ( ! $order->get_id() ) {
			throw new \Exception( __( 'ID must be set for an order to be read.', 'woocommerce' ) );
		}

		$order_data = $this->get_order_data_for_id( $order->get_id() );
		if ( ! $order_data ) {
			throw new \Exception( __( 'Invalid order.', 'woocommerce' ) );
		}

		$order->read_meta_data();

		foreach ( $this->get_all_order_column_mappings() as $table_name => $column_mapping ) {
			foreach ( $column_mapping as $column_name => $prop_details ) {
				if ( ! isset( $prop_details['name'] ) ) {
					continue;
				}
				$prop_setter_function_name = "set_{$prop_details['name']}";
				if ( is_callable( array( $order, $prop_setter_function_name ) ) ) {
					$order->{$prop_setter_function_name}( $order_data->{$prop_details['name']} );
				} elseif ( is_callable( array( $this, $prop_setter_function_name ) ) ) {
					$this->{$prop_setter_function_name}( $order, $order_data->{$prop_details['name']}, false );
				}
			}
		}

		$order->set_object_read( true );
	}

	/**
	 * Return order data for a single order ID.
	 *
	 * @param int $id Order ID.
	 *
	 * @return object|\WP_Error DB order object or WP_Error.
	 */
	private function get_order_data_for_id( $id ) {
		$results = $this->get_order_data_for_ids( array( $id ) );

		return is_array( $results ) && count( $results ) > 0 ? $results[0] : $results;
	}

	/**
	 * Return order data for multiple IDs.
	 *
	 * @param array $ids List of order IDs.
	 *
	 * @return array|object|null DB Order objects or error.
	 */
	private function get_order_data_for_ids( $ids ) {
		global $wpdb;
		$order_table_query = $this->get_order_table_select_statement();
		$id_placeholder    = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $order_table_query is autogenerated and should already be prepared.
		return $wpdb->get_results(
			$wpdb->prepare(
				"$order_table_query WHERE wc_order.id in ( $id_placeholder )",
				$ids
			)
		);
		// phpcs:enable
	}

	/**
	 * Helper method to generate combined select statement.
	 *
	 * @return string Select SQL statement to fetch order.
	 */
	private function get_order_table_select_statement() {
		$order_table                  = $this::get_orders_table_name();
		$order_table_alias            = 'wc_order';
		$select_clause                = $this->generate_select_clause_for_props( $order_table_alias, $this->order_column_mapping );
		$billing_address_table_alias  = 'address_billing';
		$shipping_address_table_alias = 'address_shipping';
		$op_data_table_alias          = 'order_operational_data';
		$billing_address_clauses      = $this->join_billing_address_table_to_order_query( $order_table_alias, $billing_address_table_alias );
		$shipping_address_clauses     = $this->join_shipping_address_table_to_order_query( $order_table_alias, $shipping_address_table_alias );
		$operational_data_clauses     = $this->join_operational_data_table_to_order_query( $order_table_alias, $op_data_table_alias );

		return "
SELECT $select_clause, {$billing_address_clauses['select']}, {$shipping_address_clauses['select']}, {$operational_data_clauses['select']}
FROM $order_table $order_table_alias
LEFT JOIN {$billing_address_clauses['join']}
LEFT JOIN {$shipping_address_clauses['join']}
LEFT JOIN {$operational_data_clauses['join']}
";
	}

	/**
	 * Helper method to generate join query for billing addresses in wc_address table.
	 *
	 * @param string $order_table_alias Alias for order table to use in join.
	 * @param string $address_table_alias Alias for address table to use in join.
	 *
	 * @return array Select and join statements for billing address table.
	 */
	private function join_billing_address_table_to_order_query( $order_table_alias, $address_table_alias ) {
		return $this->join_address_table_order_query( 'billing', $order_table_alias, $address_table_alias );
	}

	/**
	 * Helper method to generate join query for shipping addresses in wc_address table.
	 *
	 * @param string $order_table_alias Alias for order table to use in join.
	 * @param string $address_table_alias Alias for address table to use in join.
	 *
	 * @return array Select and join statements for shipping address table.
	 */
	private function join_shipping_address_table_to_order_query( $order_table_alias, $address_table_alias ) {
		return $this->join_address_table_order_query( 'shipping', $order_table_alias, $address_table_alias );
	}

	/**
	 * Helper method to generate join and select query for address table.
	 *
	 * @param string $address_type Type of address. Typically will be `billing` or `shipping`.
	 * @param string $order_table_alias Alias of order table to use.
	 * @param string $address_table_alias Alias for address table to use.
	 *
	 * @return array Select and join statements for address table.
	 */
	private function join_address_table_order_query( $address_type, $order_table_alias, $address_table_alias ) {
		global $wpdb;
		$address_table    = $this::get_addresses_table_name();
		$column_props_map = 'billing' === $address_type ? $this->billing_address_column_mapping : $this->shipping_address_column_mapping;
		$clauses          = $this->generate_select_and_join_clauses( $order_table_alias, $address_table, $address_table_alias, $column_props_map );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $clauses['join'] and $address_table_alias are hardcoded.
		$clauses['join'] = $wpdb->prepare(
			"{$clauses['join']} AND $address_table_alias.address_type = %s",
			$address_type
		);

		// phpcs:enable
		return array(
			'select' => $clauses['select'],
			'join'   => $clauses['join'],
		);
	}

	/**
	 * Helper method to join order operational data table.
	 *
	 * @param string $order_table_alias Alias to use for order table.
	 * @param string $operational_table_alias Alias to use for operational data table.
	 *
	 * @return array Select and join queries for operational data table.
	 */
	private function join_operational_data_table_to_order_query( $order_table_alias, $operational_table_alias ) {
		$operational_data_table = $this::get_operational_data_table_name();

		return $this->generate_select_and_join_clauses(
			$order_table_alias,
			$operational_data_table,
			$operational_table_alias,
			$this->operational_data_column_mapping
		);
	}

	/**
	 * Helper method to generate join and select clauses.
	 *
	 * @param string  $order_table_alias Alias for order table.
	 * @param string  $table Table to join.
	 * @param string  $table_alias Alias for table to join.
	 * @param array[] $column_props_map Column to prop map for table to join.
	 *
	 * @return array Select and join queries.
	 */
	private function generate_select_and_join_clauses( $order_table_alias, $table, $table_alias, $column_props_map ) {
		// Add aliases to column names so they will be unique when fetching.
		$select_clause = $this->generate_select_clause_for_props( $table_alias, $column_props_map );
		$join_clause   = "$table $table_alias ON $table_alias.order_id = $order_table_alias.id";

		return array(
			'select' => $select_clause,
			'join'   => $join_clause,
		);
	}

	/**
	 * Helper method to generate select clause for props.
	 *
	 * @param string  $table_alias Alias for table.
	 * @param array[] $props Props to column mapping for table.
	 *
	 * @return string Select clause.
	 */
	private function generate_select_clause_for_props( $table_alias, $props ) {
		$select_clauses = array();
		foreach ( $props as $column_name => $prop_details ) {
			$select_clauses[] = isset( $prop_details['name'] ) ? "$table_alias.$column_name as {$prop_details['name']}" : "$table_alias.$column_name as {$table_alias}_$column_name";
		}

		return implode( ', ', $select_clauses );
	}

	/**
	 * Persists order changes to the database.
	 *
	 * @param \WC_Order $order        The order.
	 * @throws \Exception If order data is not valid.
	 *
	 * @since 6.8.0
	 */
	protected function persist_order_to_db( &$order ) {
		global $wpdb;

		$context   = ( 0 === absint( $order->get_id() ) ) ? 'create' : 'update';
		$data_sync = wc_get_container()->get( DataSynchronizer::class );

		if ( 'create' === $context ) {
			// XXX: do we want to add some backwards compat for 'woocommerce_new_order_data'?
			$post_id = wp_insert_post(
				array(
					'post_type'   => $data_sync->data_sync_is_enabled() ? 'shop_order' : $data_sync::PLACEHOLDER_ORDER_POST_TYPE,
					'post_status' => 'draft',
				)
			);

			if ( ! $post_id ) {
				throw new \Exception( __( 'Could not create order in posts table.', 'woocommerce' ) );
			}

			$order->set_id( $post_id );
		}

		// Figure out what needs to be updated in the database.
		$db_updates = $this->get_db_rows_for_order( $order, $context, ( 'update' === $context ) );

		// Persist changes.
		foreach ( $db_updates as $update ) {
			// Make sure 'data' and 'format' entries match before passing to $wpdb.
			ksort( $update['data'] );
			ksort( $update['format'] );

			$result = empty( $update['where'] )
					? $wpdb->insert( $update['table'], $update['data'], array_values( $update['format'] ) )
					: $wpdb->update( $update['table'], $update['data'], $update['where'], array_values( $update['format'] ), $update['where_format'] );

			if ( false === $result ) {
				// translators: %s is a table name.
				throw new \Exception( sprintf( __( 'Could not persist order to database table "%s".', 'woocommerce' ), $update['table'] ) );
			}
		}

		// Backfill post record.
		if ( $data_sync->data_sync_is_enabled() ) {
			$this->backfill_post_record( $order );
		}
	}

	/**
	 * Generates an array of rows with all the details required to insert or update an order in the database.
	 *
	 * @param \WC_Order $order        The order.
	 * @param string    $context      The context: 'create' or 'update'.
	 * @param boolean   $only_changes Whether to consider only changes in the order for generating the rows.
	 * @return array
	 * @throws \Exception When invalid data is found for the given context.
	 *
	 * @since 6.8.0
	 */
	protected function get_db_rows_for_order( $order, $context = 'create', $only_changes = false ): array {
		$result = array();

		// wc_orders.
		$row = $this->get_db_row_from_order( $order, $this->order_column_mapping, $only_changes );
		if ( 'create' === $context && ! $row ) {
			throw new \Exception( 'No data for new record.' ); // This shouldn't occur.
		}

		if ( $row ) {
			$result[] = array(
				'table'        => self::get_orders_table_name(),
				'data'         => array_merge( $row['data'], array( 'id' => $order->get_id() ) ),
				'format'       => array_merge( $row['format'], array( 'id' => '%d' ) ),
				'where'        => 'update' === $context ? array( 'id' => $order->get_id() ) : null,
				'where_format' => 'update' === $context ? '%d' : null,
			);
		}

		// wc_order_operational_data.
		$row = $this->get_db_row_from_order( $order, $this->operational_data_column_mapping, $only_changes );
		if ( $row ) {
			$result[] = array(
				'table'        => self::get_operational_data_table_name(),
				'data'         => array_merge( $row['data'], array( 'order_id' => $order->get_id() ) ),
				'format'       => array_merge( $row['format'], array( 'order_id' => '%d' ) ),
				'where'        => 'update' === $context ? array( 'order_id' => $order->get_id() ) : null,
				'where_format' => 'update' === $context ? '%d' : null,
			);
		}

		// wc_order_addresses.
		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			$row = $this->get_db_row_from_order( $order, $this->{$address_type . '_address_column_mapping'}, $only_changes );

			if ( $row ) {
				$result[] = array(
					'table'        => self::get_addresses_table_name(),
					'data'         => array_merge(
						$row['data'],
						array(
							'order_id'     => $order->get_id(),
							'address_type' => $address_type,
						)
					),
					'format'       => array_merge(
						$row['format'],
						array(
							'order_id'     => '%d',
							'address_type' => '%s',
						)
					),
					'where'        => 'update' === $context
									? array(
										'order_id'     => $order->get_id(),
										'address_type' => $address_type,
									)
									: null,
					'where_format' => 'update' === $context ? array( '%d', '%s' ) : null,
				);
			}
		}

		/**
		 * Allow third parties to include rows that need to be inserted/updated in custom tables when persisting an order.
		 *
		 * @since 6.8.0
		 *
		 * @param array      Array of rows to be inserted/updated when persisting an order. Each entry should be an array with
		 *                   keys 'table', 'data' (the row), 'format' (row format), 'where' and 'where_format'.
		 * @param \WC_Order  The order object.
		 * @param string     The context of the operation: 'create' or 'update'.
		 */
		$ext_rows = apply_filters( 'woocommerce_orders_table_datastore_extra_db_rows_for_order', array(), $order, $context );

		return array_merge( $result, $ext_rows );
	}

	/**
	 * Produces an array with keys 'row' and 'format' that can be passed to `$wpdb->update()` as the `$data` and
	 * `$format` parameters. Values are taken from the order changes array and properly formatted for inclusion in the
	 * database.
	 *
	 * @param \WC_Order $order          Order.
	 * @param array     $column_mapping Table column mapping.
	 * @param bool      $only_changes   Whether to consider only changes in the order object or all fields.
	 * @return array
	 *
	 * @since 6.8.0
	 */
	protected function get_db_row_from_order( $order, $column_mapping, $only_changes = false ) {
		$changes = $only_changes ? $order->get_changes() : array_merge( $order->get_data(), $order->get_changes() );

		// XXX: manually persist some of the properties until the datastore/property design is finalized.
		foreach ( $this->get_internal_data_store_keys() as $key ) {
			$changes[ $key ] = $this->{"get_$key"}( $order );
		}

		$row        = array();
		$row_format = array();

		foreach ( $column_mapping as $column => $details ) {
			if ( ! isset( $details['name'] ) || ! array_key_exists( $details['name'], $changes ) ) {
				continue;
			}

			$row[ $column ]        = $this->database_util->format_object_value_for_db( $changes[ $details['name'] ], $details['type'] );
			$row_format[ $column ] = $this->database_util->get_wpdb_format_for_type( $details['type'] );
		}

		if ( ! $row ) {
			return false;
		}

		return array(
			'data'   => $row,
			'format' => $row_format,
		);
	}


	//phpcs:disable Squiz.Commenting, Generic.Commenting

	/**
	 * Method to delete an order from the database.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$order, $args = array() ) {
		$order_id  = $order->get_id();

		if ( ! $order_id ) {
			return;
		}

		if ( ! empty( $args['force_delete'] ) ) {
			$this->delete_order_data_from_custom_order_tables( $order_id );
			$order->set_id( 0 );

			// If this datastore method is called while the posts table is authoritative, refrain from deleting post data.
			if ( ! is_a( $order->get_data_store(), self::class ) ) {
				return;
			}

			// Delete the associated post, which in turn deletes order items, etc. through {@see WC_Post_Data}.
			// Once we stop creating posts for orders, we should do the cleanup here instead.
			wp_delete_post( $order_id );

			do_action( 'woocommerce_delete_order', $order_id ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		} else {
			$this->trash_order( $order );

			do_action( 'woocommerce_trash_order', $order_id ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		}
	}

	/**
	 * Trashes an order.
	 *
	 * @param \WC_Order $order The order object
	 * @return void
	 */
	public function trash_order( &$order ) {
		global $wpdb;

		if ( 'trash' === $order->get_status( 'edit' ) ) {
			return;
		}

		$trash_metadata = array(
			'_wp_trash_meta_status' => $order->get_status( 'edit' ),
			'_wp_trash_meta_time'   => time(),
		);

		foreach ( $trash_metadata as $meta_key => $meta_value ) {
			$this->add_meta(
				$order,
				(object) array(
					'key'   => $meta_key,
					'value' => $meta_value,
				)
			);
		}

		$wpdb->update(
			self::get_orders_table_name(),
			array( 'status' => 'trash' ),
			array( 'id' => $order->get_id() ),
			array( '%s' ),
			array( '%d' )
		);

		$order->set_status( 'trash' );
	}

	/**
	 * Deletes order data from custom order tables.
	 *
	 * @param int $order_id The order ID.
	 * @return void
	 */
	public function delete_order_data_from_custom_order_tables( $order_id ) {
		global $wpdb;

		// Delete COT-specific data.
		foreach ( $this->get_all_table_names() as $table ) {
			$wpdb->delete(
				$table,
				( self::get_orders_table_name() === $table )
					? array( 'id' => $order_id )
					: array( 'order_id' => $order_id ),
				array( '%d' )
			);
		}
	}

	/**
	 * Method to create an order in the database.
	 *
	 * @param \WC_Order $order
	 */
	public function create( &$order ) {
		if ( '' === $order->get_order_key() ) {
			$order->set_order_key( wc_generate_order_key() );
		}

		$order->set_version( Constants::get_constant( 'WC_VERSION' ) );
		$order->set_currency( $order->get_currency() ? $order->get_currency() : get_woocommerce_currency() );

		if ( ! $order->get_date_created( 'edit' ) ) {
			$order->set_date_created( time() );
		}

		$this->update_post_meta( $order );

		$this->persist_order_to_db( $order );

		$order->save_meta_data();
		$order->apply_changes();

		$this->clear_caches( $order );

		/**
		 * Fires when a new order is created.
		 *
		 * @since 2.7.0
		 *
		 * @param int       Order ID.
		 * @param \WC_Order Order object.
		 */
		do_action( 'woocommerce_new_order', $order->get_id(), $order );
	}

	/**
	 * Method to update an order in the database.
	 *
	 * @param \WC_Order $order
	 */
	public function update( &$order ) {
		// Before updating, ensure date paid is set if missing.
		if (
			! $order->get_date_paid( 'edit' )
			&& version_compare( $order->get_version( 'edit' ), '3.0', '<' )
			&& $order->has_status( apply_filters( 'woocommerce_payment_complete_order_status', $order->needs_processing() ? 'processing' : 'completed', $order->get_id(), $order ) ) // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		) {
			$order->set_date_paid( $order->get_date_created( 'edit' ) );
		}

		if ( null === $order->get_date_created( 'edit' ) ) {
			$order->set_date_created( time() );
		}

		$order->set_version( Constants::get_constant( 'WC_VERSION' ) );

		// Fetch changes.
		$changes = $order->get_changes();

		if ( ! isset( $changes['date_modified'] ) ) {
			$order->set_date_modified( gmdate( 'Y-m-d H:i:s' ) );
		}

		$this->update_post_meta( $order );

		// Update with latest changes.
		$changes = $order->get_changes();

		$this->persist_order_to_db( $order );

		// Update download permissions if necessary.
		if ( array_key_exists( 'billing_email', $changes ) || array_key_exists( 'customer_id', $changes ) ) {
			$data_store = \WC_Data_Store::load( 'customer-download' );
			$data_store->update_user_by_order_id( $order->get_id(), $order->get_customer_id(), $order->get_billing_email() );
		}

		// Mark user account as active.
		if ( array_key_exists( 'customer_id', $changes ) ) {
			wc_update_user_last_active( $order->get_customer_id() );
		}

		$order->save_meta_data();
		$order->apply_changes();
		$this->clear_caches( $order );

		do_action( 'woocommerce_update_order', $order->get_id(), $order ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
	}

	/**
	 * Helper method that updates post meta based on an order object.
	 * Mostly used for backwards compatibility purposes in this datastore.
	 *
	 * @param \WC_Order $order Order object.
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$order ) {
		$changes = $order->get_changes();

		// If address changed, store concatenated version to make searches faster.
		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			if ( isset( $changes[ $address_type ] ) ) {
				$order->update_meta_data( "_{$address_type}_address_index", implode( ' ', $order->get_address( $address_type ) ) );
			}
		}

		// Sync some COT fields to meta keys for backwards compatibility.
		foreach ( $this->get_internal_data_store_keys() as $key ) {
			$this->{"set_$key"}( $order, $this->{"get_$key"}( $order ), false );
		}
	}

	public function get_coupon_held_keys( $order, $coupon_id = null ) {
		return array();
	}

	public function get_coupon_held_keys_for_users( $order, $coupon_id = null ) {
		return array();
	}

	public function set_coupon_held_keys( $order, $held_keys, $held_keys_for_user ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function release_held_coupons( $order, $save = true ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function query( $query_vars ) {
		return array();
	}

	public function get_order_item_type( $order, $order_item_id ) {
		return 'line_item';
	}

	//phpcs:enable Squiz.Commenting, Generic.Commenting

	/**
	 * Get the SQL needed to create all the tables needed for the custom orders table feature.
	 *
	 * @return string
	 */
	public function get_database_schema() {
		global $wpdb;

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		$orders_table_name           = $this->get_orders_table_name();
		$addresses_table_name        = $this->get_addresses_table_name();
		$operational_data_table_name = $this->get_operational_data_table_name();
		$meta_table                  = $this->get_meta_table_name();

		$sql = "
CREATE TABLE $orders_table_name (
	id bigint(20) unsigned auto_increment,
	status varchar(20) null,
	currency varchar(10) null,
	tax_amount decimal(26,8) null,
	total_amount decimal(26,8) null,
	customer_id bigint(20) unsigned null,
	billing_email varchar(320) null,
	date_created_gmt datetime null,
	date_updated_gmt datetime null,
	parent_order_id bigint(20) unsigned null,
	payment_method varchar(100) null,
	payment_method_title text null,
	transaction_id varchar(100) null,
	ip_address varchar(100) null,
	user_agent text null,
	PRIMARY KEY (id),
	KEY status (status),
	KEY date_created (date_created_gmt),
	KEY customer_id_billing_email (customer_id, billing_email)
) $collate;
CREATE TABLE $addresses_table_name (
	id bigint(20) unsigned auto_increment primary key,
	order_id bigint(20) unsigned NOT NULL,
	address_type varchar(20) null,
	first_name text null,
	last_name text null,
	company text null,
	address_1 text null,
	address_2 text null,
	city text null,
	state text null,
	postcode text null,
	country text null,
	email varchar(320) null,
	phone varchar(100) null,
	KEY order_id (order_id),
	KEY address_type_order_id (address_type, order_id)
) $collate;
CREATE TABLE $operational_data_table_name (
	id bigint(20) unsigned auto_increment primary key,
	order_id bigint(20) unsigned NULL,
	created_via varchar(100) NULL,
	woocommerce_version varchar(20) NULL,
	prices_include_tax tinyint(1) NULL,
	coupon_usages_are_counted tinyint(1) NULL,
	download_permission_granted tinyint(1) NULL,
	cart_hash varchar(100) NULL,
	new_order_email_sent tinyint(1) NULL,
	order_key varchar(100) NULL,
	order_stock_reduced tinyint(1) NULL,
	date_paid_gmt datetime NULL,
	date_completed_gmt datetime NULL,
	shipping_tax_amount decimal(26, 8) NULL,
	shipping_total_amount decimal(26, 8) NULL,
	discount_tax_amount decimal(26, 8) NULL,
	discount_total_amount decimal(26, 8) NULL,
	recorded_sales tinyint(1) NULL,
	KEY order_id (order_id),
	KEY order_key (order_key)
) $collate;
CREATE TABLE $meta_table (
	id bigint(20) unsigned auto_increment primary key,
	order_id bigint(20) unsigned null,
	meta_key varchar(255),
	meta_value text null,
	KEY meta_key_value (meta_key, meta_value(100))
) $collate;
";

		return $sql;
	}

	/**
	 * Returns an array of meta for an object.
	 *
	 * @param  WC_Data $object WC_Data object.
	 * @return array
	 */
	public function read_meta( &$object ) {
		return $this->data_store_meta->read_meta( $object );
	}

	/**
	 * Deletes meta based on meta ID.
	 *
	 * @param  WC_Data  $object WC_Data object.
	 * @param  stdClass $meta (containing at least ->id).
	 */
	public function delete_meta( &$object, $meta ) {
		return $this->data_store_meta->delete_meta( $object, $meta );
	}

	/**
	 * Add new piece of meta.
	 *
	 * @param  WC_Data  $object WC_Data object.
	 * @param  stdClass $meta (containing ->key and ->value).
	 * @return int meta ID
	 */
	public function add_meta( &$object, $meta ) {
		return $this->data_store_meta->add_meta( $object, $meta );
	}

	/**
	 * Update meta.
	 *
	 * @param  WC_Data  $object WC_Data object.
	 * @param  stdClass $meta (containing ->id, ->key and ->value).
	 */
	public function update_meta( &$object, $meta ) {
		return $this->data_store_meta->update_meta( $object, $meta );
	}

	/**
	 * Returns list of metadata that is considered "internal".
	 *
	 * @return array
	 */
	public function get_internal_meta_keys() {
		// XXX: This is mostly just to trick `WC_Data_Store_WP` for the time being.
		return array(
			'_customer_user',
			'_order_key',
			'_order_currency',
			'_billing_first_name',
			'_billing_last_name',
			'_billing_company',
			'_billing_address_1',
			'_billing_address_2',
			'_billing_city',
			'_billing_state',
			'_billing_postcode',
			'_billing_country',
			'_billing_email',
			'_billing_phone',
			'_shipping_first_name',
			'_shipping_last_name',
			'_shipping_company',
			'_shipping_address_1',
			'_shipping_address_2',
			'_shipping_city',
			'_shipping_state',
			'_shipping_postcode',
			'_shipping_country',
			'_shipping_phone',
			'_completed_date',
			'_paid_date',
			'_edit_lock',
			'_edit_last',
			'_cart_discount',
			'_cart_discount_tax',
			'_order_shipping',
			'_order_shipping_tax',
			'_order_tax',
			'_order_total',
			'_payment_method',
			'_payment_method_title',
			'_transaction_id',
			'_customer_ip_address',
			'_customer_user_agent',
			'_created_via',
			'_order_version',
			'_prices_include_tax',
			'_date_completed',
			'_date_paid',
			'_payment_tokens',
			'_billing_address_index',
			'_shipping_address_index',
			'_recorded_sales',
			'_recorded_coupon_usage_counts',
			'_download_permissions_granted',
			'_order_stock_reduced',
		);
	}

	/**
	 * Returns keys currently handled by this datastore manually (not available through order properties).
	 *
	 * @return array List of keys.
	 */
	protected function get_internal_data_store_keys() {
		// XXX: Finalize design -- will these be turned into props?
		return array(
			'order_stock_reduced',
			'download_permissions_granted',
			'new_order_email_sent',
			'recorded_sales',
			'recorded_coupon_usage_counts',
		);
	}

}
