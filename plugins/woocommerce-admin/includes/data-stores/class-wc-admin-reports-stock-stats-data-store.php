<?php
/**
 * WC_Admin_Reports_Stock_Stats_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Reports_Stock_Stats_Data_Store.
 */
class WC_Admin_Reports_Stock_Stats_Data_Store extends WC_Admin_Reports_Data_Store implements WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Get stock counts for the whole store.
	 *
	 * @param array $query Not used for the stock stats data store, but needed for the interface.
	 * @return array Array of counts.
	 */
	public function get_data( $query ) {
		$report_data              = array();
		$cache_expire             = DAY_IN_SECONDS * 30;
		$low_stock_transient_name = 'wc_admin_stock_count_lowstock';
		$low_stock_count          = get_transient( $low_stock_transient_name );
		if ( false === $low_stock_count ) {
			$low_stock_count = $this->get_low_stock_count();
			set_transient( $low_stock_transient_name, $low_stock_count, $cache_expire );
		}
		$report_data['lowstock'] = $low_stock_count;

		$status_options = wc_get_product_stock_status_options();
		foreach ( $status_options as $status => $label ) {
			$transient_name = 'wc_admin_stock_count_' . $status;
			$count          = get_transient( $transient_name );
			if ( false === $count ) {
				$count = $this->get_count( $status );
				set_transient( $transient_name, $count, $cache_expire );
			}
			$report_data[ $status ] = $count;
		}

		$product_count_transient_name = 'wc_admin_product_count';
		$product_count                = get_transient( $product_count_transient_name );
		if ( false === $product_count ) {
			$product_count = $this->get_product_count();
			set_transient( $product_count_transient_name, $product_count, $cache_expire );
		}
		$report_data['products'] = $product_count;
		return $report_data;
	}

	/**
	 * Get low stock count.
	 *
	 * @return int Low stock count.
	 */
	private function get_low_stock_count() {
		$query_args               = array();
		$query_args['post_type']  = array( 'product', 'product_variation' );
		$low_stock                = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
		$no_stock                 = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );
		$query_args['meta_query'] = array( // WPCS: slow query ok.
			array(
				'key'   => '_manage_stock',
				'value' => 'yes',
			),
			array(
				'key'     => '_stock',
				'value'   => array( $no_stock, $low_stock ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			),
			array(
				'key'   => '_stock_status',
				'value' => 'instock',
			),
		);

		$query = new WP_Query();
		$query->query( $query_args );
		return intval( $query->found_posts );
	}

	/**
	 * Get count for the passed in stock status.
	 *
	 * @param  string $status Status slug.
	 * @return int Count.
	 */
	private function get_count( $status ) {
		$query_args               = array();
		$query_args['post_type']  = array( 'product', 'product_variation' );
		$query_args['meta_query'] = array( // WPCS: slow query ok.
			array(
				'key'   => '_stock_status',
				'value' => $status,
			),
		);

		$query = new WP_Query();
		$query->query( $query_args );
		return intval( $query->found_posts );
	}

	/**
	 * Get product count for the store.
	 *
	 * @return int Product count.
	 */
	private function get_product_count() {
		$query_args              = array();
		$query_args['post_type'] = array( 'product', 'product_variation' );
		$query                   = new WP_Query();
		$query->query( $query_args );
		return intval( $query->found_posts );
	}
}

/**
 * Clear the count cache when products are added or updated, or when
 * the no/low stock options are changed.
 *
 * @param int $id Post/product ID.
 */
function wc_admin_clear_stock_count_cache( $id ) {
	delete_transient( 'wc_admin_stock_count_lowstock' );
	delete_transient( 'wc_admin_product_count' );
	$status_options = wc_get_product_stock_status_options();
	foreach ( $status_options as $status => $label ) {
		delete_transient( 'wc_admin_stock_count_' . $status );
	}
}

add_action( 'woocommerce_update_product', 'wc_admin_clear_stock_count_cache' );
add_action( 'woocommerce_new_product', 'wc_admin_clear_stock_count_cache' );
add_action( 'update_option_woocommerce_notify_low_stock_amount', 'wc_admin_clear_stock_count_cache' );
add_action( 'update_option_woocommerce_notify_no_stock_amount', 'wc_admin_clear_stock_count_cache' );
