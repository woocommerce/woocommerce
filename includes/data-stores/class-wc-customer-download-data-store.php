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
	 * @param WC_Customer_Download $download
	 */
	public function create( &$download ) {
		global $wpdb;

		$data = array(
			'download_id'         => $download->get_download_id(),
			'product_id'          => $download->get_product_id(),
			'user_id'             => $download->get_user_id(),
			'user_email'          => $download->get_user_email(),
			'order_id'            => $download->get_order_id(),
			'order_key'           => $download->get_order_key(),
			'downloads_remaining' => $download->get_downloads_remaining(),
			'access_granted'      => date( 'Y-m-d', $download->get_access_granted() ),
			'download_count'      => $download->get_download_count(),
			'access_expires'      => $download->get_access_expires() ? date( 'Y-m-d', $download->get_access_expires() ) : null,
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
			'%s',
		);

		$result = $wpdb->insert(
			$wpdb->prefix . 'woocommerce_downloadable_product_permissions',
			apply_filters( 'woocommerce_downloadable_file_permission_data', $data ),
			apply_filters( 'woocommerce_downloadable_file_permission_format', $format, $data )
		);

		do_action( 'woocommerce_grant_product_download_access', $data );

		if ( $result ) {
			$download->set_id( $wpdb->insert_id );
			$download->apply_changes();
		}
	}

	/**
	 * Method to read a download permission from the database.
	 *
	 * @param WC_Customer_Download
	 */
	public function read( &$download ) {
		global $wpdb;

		$download->set_defaults();

		if ( ! $download->get_id() || ! ( $raw_download = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE permission_id = %d", $download->get_id() ) ) ) ) {
			throw new Exception( __( 'Invalid download.', 'woocommerce' ) );
		}

		$download->set_props( $raw_download );
		$download->set_object_read( true );
	}

	/**
	 * Method to update a download in the database.
	 *
	 * @param WC_Customer_Download $download
	 */
	public function update( &$download ) {
		global $wpdb;

		$data = array(
			'download_id'         => $download->get_download_id(),
			'product_id'          => $download->get_product_id(),
			'user_id'             => $download->get_user_id(),
			'user_email'          => $download->get_user_email(),
			'order_id'            => $download->get_order_id(),
			'order_key'           => $download->get_order_key(),
			'downloads_remaining' => $download->get_downloads_remaining(),
			'access_granted'      => date( 'Y-m-d', $download->get_access_granted() ),
			'download_count'      => $download->get_download_count(),
			'access_expires'      => $download->get_access_expires() ? date( 'Y-m-d', $download->get_access_expires() ) : null,
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
			'%s',
		);

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_downloadable_product_permissions',
			$data,
			array(
				'permission_id' => $download->get_id(),
			),
			$format
		);
		$download->apply_changes();
	}

	/**
	 * Method to delete a download permission from the database.
	 *
	 * @param WC_Customer_Download $download
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$download, $args = array() ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE permission_id = %d
		", $download->get_id() ) );

		$download->set_id( 0 );
	}

	/**
	 * Method to delete a download permission from the database by ID.
	 *
	 * @param int $id
	 */
	public function delete_by_id( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE permission_id = %d
		", $id ) );
	}

	/**
	 * Method to delete a download permission from the database by order ID.
	 *
	 * @param int $id
	 */
	public function delete_by_order_id( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE order_id = %d
		", $id ) );
	}

	/**
	 * Method to delete a download permission from the database by download ID.
	 *
	 * @param int $id
	 */
	public function delete_by_download_id( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE download_id = %s
		", $id ) );
	}

	/**
	 * Get a download object.
	 *
	 * @param  array $data From the DB.
	 * @return WC_Customer_Download
	 */
	private function get_download( $data ) {
		return new WC_Customer_Download( $data );
	}

	/**
	 * Get array of download ids by specified args.
	 *
	 * @param  array $args
	 * @return array
	 */
	public function get_downloads( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'user_email' => '',
			'order_id'   => '',
			'order_key'  => '',
			'product_id' => '',
			'orderby'    => 'permission_id',
			'order'      => 'DESC',
			'limit'      => -1,
			'return'     => 'objects',
		) );

		extract( $args );

		$query   = array();
		$query[] = "SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE 1=1";

		if ( $user_email ) {
			$query[] = $wpdb->prepare( "AND user_email = %s", $user_email );
		}

		if ( $order_id ) {
			$query[] = $wpdb->prepare( "AND order_id = %d", $order_id );
		}

		if ( $order_key ) {
			$query[] = $wpdb->prepare( "AND order_key = %s", $order_key );
		}

		if ( $product_id ) {
			$query[] = $wpdb->prepare( "AND product_id = %d", $product_id );
		}

		$orderby = esc_sql( $orderby );
		$order   = esc_sql( $order );
		$query[] = "ORDER BY {$orderby} {$order}";

		if ( 0 < $limit ) {
			$query[] = $wpdb->prepare( "LIMIT %d", $limit );
		}

		$raw_downloads = $wpdb->get_results( implode( ' ', $query ) );

		switch ( $return ) {
			case 'ids' :
				return wp_list_pluck( $raw_downloads, 'permission_id' );
			default :
				return array_map( array( $this, 'get_download' ), $raw_downloads );
		}
	}

	/**
	 * Update download ids if the hash changes.
	 *
	 * @param  int $product_id
	 * @param  string $old_id
	 * @param  string $new_id
	 */
	public function update_download_id( $product_id, $old_id, $new_id ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_downloadable_product_permissions',
			array(
				'download_id' => $new_id,
			),
			array(
				'download_id' => $old_id,
				'product_id'  => $product_id,
			)
		);
	}

	/**
	 * Get a customers downloads.
	 *
	 * @param  int $customer_id
	 * @return array
	 */
	public function get_downloads_for_customer( $customer_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare( "
				SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions as permissions
				WHERE user_id = %d
				AND permissions.order_id > 0
				AND
					(
						permissions.downloads_remaining > 0
						OR permissions.downloads_remaining = ''
					)
				AND
					(
						permissions.access_expires IS NULL
						OR permissions.access_expires >= %s
						OR permissions.access_expires = '0000-00-00 00:00:00'
					)
				ORDER BY permissions.order_id, permissions.product_id, permissions.permission_id;
				",
				$customer_id,
				date( 'Y-m-d', current_time( 'timestamp' ) )
			)
		);
	}

	/**
	 * Update user prop for downloads based on order id.
	 *
	 * @param  int $order_id
	 * @param  int $customer_id
	 * @param  string $email
	 */
	public function update_user_by_order_id( $order_id, $customer_id, $email ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
			array(
				'user_id'    => $customer_id,
				'user_email' => $email,
			),
			array(
				'order_id'   => $order_id,
			),
			array(
				'%d',
				'%s',
			),
			array(
				'%d',
			)
		);
	}
}
