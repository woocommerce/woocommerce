<?php
/**
 * OrdersTableDataStore class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * This class is the standard data store to be used when the custom orders table is in use.
 */
class OrdersTableDataStore extends \Abstract_WC_Order_Data_Store_CPT implements \WC_Object_Data_Store_Interface, \WC_Order_Data_Store_Interface {

	/**
	 * Get the custom orders table name.
	 *
	 * @return string The custom orders table name.
	 */
	public function get_orders_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wc_orders';
	}

	/**
	 * Get the order addresses table name.
	 *
	 * @return string The order addresses table name.
	 */
	public function get_addresses_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wc_order_addresses';
	}

	/**
	 * Get the orders operational data table name.
	 *
	 * @return string The orders operational data table name.
	 */
	public function get_operational_data_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wc_order_operational_data';
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
		);
	}

	// TODO: Add methods for other table names as appropriate.

	//phpcs:disable Squiz.Commenting, Generic.Commenting

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

	public function get_download_permissions_granted( $order ) {
		// TODO: Implement get_download_permissions_granted() method.
		false;
	}

	public function set_download_permissions_granted( $order, $set ) {
		// TODO: Implement set_download_permissions_granted() method.
	}

	public function get_recorded_sales( $order ) {
		// TODO: Implement get_recorded_sales() method.
		return false;
	}

	public function set_recorded_sales( $order, $set ) {
		// TODO: Implement set_recorded_sales() method.
	}

	public function get_recorded_coupon_usage_counts( $order ) {
		// TODO: Implement get_recorded_coupon_usage_counts() method.
		return false;
	}

	public function set_recorded_coupon_usage_counts( $order, $set ) {
		// TODO: Implement set_recorded_coupon_usage_counts() method.
	}

	public function get_order_type( $order_id ) {
		// TODO: Implement get_order_type() method.
		return 'shop_order';
	}

	/**
	 * @param \WC_Order $order
	 */
	public function create( &$order ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function update( &$order ) {
		throw new \Exception( 'Unimplemented' );
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

	public function get_stock_reduced( $order ) {
		return false;
	}

	public function set_stock_reduced( $order, $set ) {
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
		$orders_table_name           = $this->get_orders_table_name();
		$addresses_table_name        = $this->get_addresses_table_name();
		$operational_data_table_name = $this->get_operational_data_table_name();

		$sql = "
CREATE TABLE $orders_table_name (
	id bigint(20) unsigned auto_increment,
	post_id bigint(20) unsigned null,
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
	KEY post_id (post_id),
	KEY status (status),
	KEY date_created (date_created_gmt),
	KEY customer_id_billing_email (customer_id, billing_email)
);
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
	KEY order_id (order_id)
);
CREATE TABLE $operational_data_table_name (
	id bigint(20) unsigned auto_increment primary key,
	order_id bigint(20) unsigned NULL,
	created_via varchar(100) NULL,
	woocommerce_version varchar(20) NULL,
	prices_include_tax tinyint(1) NULL,
	coupon_usages_are_counted tinyint(1) NULL,
	download_permissionis_granted tinyint(1) NULL,
	cart_hash varchar(100) NULL,
	new_order_email_sent tinyint(1) NULL,
	order_key varchar(100) NULL,
	order_stock_reduced tinyint(1) NULL,
	date_paid_gmt datetime NULL,
	date_completed_gmt datetime NULL,
	shipping_tax_amount decimal(26, 8) NULL,
	shopping_total_amount decimal(26, 8) NULL,
	discount_tax_amount decimal(26, 8) NULL,
	discount_total_amount decimal(26, 8) NULL,
	KEY order_id (order_id),
	KEY order_key (order_key)
);";
		return $sql;
	}
}
