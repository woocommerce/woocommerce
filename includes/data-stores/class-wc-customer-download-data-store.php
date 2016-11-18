<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Customer Download Data Store.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Customer_Download_Data_Store implements WC_Customer_Download_Data_Store_Interface {

	/**
	 * Create dowload permission for a user.
	 *
	 * @param  string $download_id file identifier
	 * @param  WC_Product $product
	 * @param  WC_Order $order
	 * @param  int $qty purchased
	 * @return int|bool insert id or false on failure
	 */
	public function create( $args ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'download_id' => 0,
			'product'     => false,
			'order'       => false,
			'qty'         => 1,
		) );

		extract( $args );

		if ( ! $download_id || ! $product || ! $order ) {
			return false;
		}

		$data = array(
			'download_id'         => $download_id,
			'product_id'          => $product->get_id(),
			'user_id'             => $order->get_customer_id(),
			'user_email'          => $order->get_billing_email(),
			'order_id'            => $order->get_id(),
			'order_key'           => $order->get_order_key(),
			'downloads_remaining' => 0 > $product->get_download_limit() ? '' : $product->get_download_limit() * $qty,
			'access_granted'      => current_time( 'mysql' ),
			'download_count'      => 0,
		);

		$format = array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
		);

		$expiry = $product->get_download_expiry();

		if ( $expiry > 0 ) {
			$order_completed_date   = date_i18n( "Y-m-d", $order->get_date_completed() );
			$expiry                 = date_i18n( "Y-m-d", strtotime( $order_completed_date . ' + ' . $expiry . ' DAY' ) );
			$data['access_expires'] = $expiry;
			$format[]               = '%s';
		}

		$result = $wpdb->insert(
			$wpdb->prefix . 'woocommerce_downloadable_product_permissions',
			apply_filters( 'woocommerce_downloadable_file_permission_data', $data ),
			apply_filters( 'woocommerce_downloadable_file_permission_format', $format, $data )
		);

		do_action( 'woocommerce_grant_product_download_access', $data );

		return $result ? $wpdb->insert_id : false;
	}
}
