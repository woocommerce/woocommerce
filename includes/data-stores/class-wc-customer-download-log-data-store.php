<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Customer Download Log Data Store.
 *
 * @version  3.3.0
 * @category Class
 * @author   WooThemes
 */
class WC_Customer_Download_Log_Data_Store implements WC_Customer_Download_Log_Data_Store_Interface {

	/**
	 * Create download log entry.
	 *
	 * @param WC_Customer_Download_Log $download_log
	 */
	public function create( &$download_log ) {
		global $wpdb;

		// Always set a timestamp
		if ( is_null( $download_log->get_timestamp( 'edit' ) ) ) {
			$download_log->set_timestamp( current_time( 'timestamp', true ) );
		}

		$data = array(
			'timestamp'           => date( 'Y-m-d H:i:s', $download_log->get_timestamp( 'edit' )->getTimestamp() ),
			'permission_id'       => $download_log->get_permission_id( 'edit' ),
			'user_id'             => $download_log->get_user_id( 'edit' ),
			'user_ip_address'     => $download_log->get_user_ip_address( 'edit' ),
		);

		$format = array(
			'%s',
			'%s',
			'%s',
			'%s',
		);

		$result = $wpdb->insert(
			$wpdb->prefix . 'woocommerce_downloadable_product_download_log',
			apply_filters( 'woocommerce_downloadable_product_download_log_data', $data ),
			apply_filters( 'woocommerce_downloadable_product_download_log_format', $format, $data )
		);

		do_action( 'woocommerce_downloadable_product_download_log', $data );

		if ( $result ) {
			$download_log->set_id( $wpdb->insert_id );
			$download_log->apply_changes();
		}
	}

	/**
	 * Method to read a download log from the database.
	 *
	 * @param $download_log
	 *
	 * @throws Exception
	 */
	public function read( &$download_log ) {
		global $wpdb;

		$download_log->set_defaults();

		if ( ! $download_log->get_id() || ! ( $raw_download_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_download_log WHERE download_log_id = %d", $download_log->get_id() ) ) ) ) {
			throw new Exception( __( 'Invalid download log.', 'woocommerce' ) );
		}

		$download_log->set_props( array(
			'timestamp'           => strtotime( $raw_download_log->timestamp ),
			'permission_id'       => $raw_download_log->permission_id,
			'user_id'             => $raw_download_log->user_id,
			'user_ip_address'     => $raw_download_log->user_ip_address,
		) );

		$download_log->set_object_read( true );
	}

	/**
	 * Method to update a download log in the database.
	 *
	 * @param WC_Customer_Download_Log $download_log
	 */
	public function update( &$download_log ) {
		global $wpdb;

		$data = array(
			'timestamp'           => date( 'Y-m-d H:i:s', $download_log->get_timestamp( 'edit' )->getTimestamp() ),
			'permission_id'       => $download_log->get_permission_id( 'edit' ),
			'user_id'             => $download_log->get_user_id( 'edit' ),
			'user_ip_address'     => $download_log->get_user_ip_address( 'edit' ),
		);

		$format = array(
			'%s',
			'%s',
			'%s',
			'%s',
		);

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_downloadable_product_download_log',
			$data,
			array(
				'download_log_id' => $download_log->get_id(),
			),
			$format
		);
		$download_log->apply_changes();
	}

	/**
	 * Get a download log object.
	 *
	 * @param  array $data From the DB.
	 * @return WC_Customer_Download_Log
	 */
	private function get_download_log( $data ) {
		return new WC_Customer_Download_Log( $data );
	}

	/**
	 * Get array of download log ids by specified args.
	 *
	 * @param  array $args
	 * @return array
	 */
	public function get_download_logs( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'permission_id'   => '',
			'user_id'         => '',
			'user_ip_address' => '',
			'orderby'     => 'download_log_id',
			'order'       => 'DESC',
			'limit'       => -1,
			'return'      => 'objects',
		) );

		$query   = array();
		$query[] = "SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_download_log WHERE 1=1";

		if ( $args['permission_id'] ) {
			$query[] = $wpdb->prepare( "AND permission_id = %d", $args['permission_id'] );
		}

		if ( $args['user_id'] ) {
			$query[] = $wpdb->prepare( "AND user_id = %d", $args['user_id'] );
		}

		if ( $args['user_ip_address'] ) {
			$query[] = $wpdb->prepare( "AND user_ip_address = %s", $args['user_ip_address'] );
		}

		$allowed_orders = array( 'download_log_id', 'timestamp', 'permission_id', 'user_id' );
		$order          = in_array( $args['order'], $allowed_orders ) ? $args['order'] : 'download_log_id';
		$orderby        = 'DESC' === strtoupper( $args['orderby'] ) ? 'DESC' : 'ASC';
		$orderby_sql    = sanitize_sql_orderby( "{$order} {$orderby}" );
		$query[]        = "ORDER BY {$orderby_sql}";

		if ( 0 < $args['limit'] ) {
			$query[] = $wpdb->prepare( "LIMIT %d", $args['limit'] );
		}

		$raw_download_logs = $wpdb->get_results( implode( ' ', $query ) );

		switch ( $args['return'] ) {
			case 'ids' :
				return wp_list_pluck( $raw_download_logs, 'download_log_id' );
			default :
				return array_map( array( $this, 'get_download_log' ), $raw_download_logs );
		}
	}

	/**
	 * Get download logs for a given download permission.
	 *
	 * @param  int $permission_id
	 * @return array
	 */
	public function get_download_logs_for_permission( $permission_id ) {
		// If no permission_id is passed, return an empty array
		if ( empty( $permission_id ) ) {
			return array();
		}

		return $this->get_download_logs( array(
			'permission_id'   => $permission_id
		) );
	}

}
