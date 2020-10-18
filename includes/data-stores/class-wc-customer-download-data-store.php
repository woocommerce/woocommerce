<?php
/**
 * WC_Customer_Download_Data_Store class file.
 *
 * @package WooCommerce\Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Customer Download Data Store.
 *
 * @version  3.0.0
 */
class WC_Customer_Download_Data_Store implements WC_Customer_Download_Data_Store_Interface {

	/**
	 * Create download permission for a user.
	 *
	 * @param WC_Customer_Download $download WC_Customer_Download object.
	 */
	public function create( &$download ) {
		global $wpdb;

		// Always set a access granted date.
		if ( is_null( $download->get_access_granted( 'edit' ) ) ) {
			$download->set_access_granted( time() );
		}

		$data = array(
			'download_id'         => $download->get_download_id( 'edit' ),
			'product_id'          => $download->get_product_id( 'edit' ),
			'user_id'             => $download->get_user_id( 'edit' ),
			'user_email'          => $download->get_user_email( 'edit' ),
			'order_id'            => $download->get_order_id( 'edit' ),
			'order_key'           => $download->get_order_key( 'edit' ),
			'downloads_remaining' => $download->get_downloads_remaining( 'edit' ),
			'access_granted'      => date( 'Y-m-d', $download->get_access_granted( 'edit' )->getTimestamp() ),
			'download_count'      => $download->get_download_count( 'edit' ),
			'access_expires'      => ! is_null( $download->get_access_expires( 'edit' ) ) ? date( 'Y-m-d', $download->get_access_expires( 'edit' )->getTimestamp() ) : null,
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

		if ( $result ) {
			$download->set_id( $wpdb->insert_id );
			$download->apply_changes();
		}

		do_action( 'woocommerce_grant_product_download_access', $data );
	}

	/**
	 * Method to read a download permission from the database.
	 *
	 * @param WC_Customer_Download $download WC_Customer_Download object.
	 *
	 * @throws Exception Throw exception if invalid download is passed.
	 */
	public function read( &$download ) {
		global $wpdb;

		if ( ! $download->get_id() ) {
			throw new Exception( __( 'Invalid download.', 'woocommerce' ) );
		}

		$download->set_defaults();
		$raw_download = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE permission_id = %d",
				$download->get_id()
			)
		);

		if ( ! $raw_download ) {
			throw new Exception( __( 'Invalid download.', 'woocommerce' ) );
		}

		$download->set_props(
			array(
				'download_id'         => $raw_download->download_id,
				'product_id'          => $raw_download->product_id,
				'user_id'             => $raw_download->user_id,
				'user_email'          => $raw_download->user_email,
				'order_id'            => $raw_download->order_id,
				'order_key'           => $raw_download->order_key,
				'downloads_remaining' => $raw_download->downloads_remaining,
				'access_granted'      => strtotime( $raw_download->access_granted ),
				'download_count'      => $raw_download->download_count,
				'access_expires'      => is_null( $raw_download->access_expires ) ? null : strtotime( $raw_download->access_expires ),
			)
		);
		$download->set_object_read( true );
	}

	/**
	 * Method to update a download in the database.
	 *
	 * @param WC_Customer_Download $download WC_Customer_Download object.
	 */
	public function update( &$download ) {
		global $wpdb;

		$data = array(
			'download_id'         => $download->get_download_id( 'edit' ),
			'product_id'          => $download->get_product_id( 'edit' ),
			'user_id'             => $download->get_user_id( 'edit' ),
			'user_email'          => $download->get_user_email( 'edit' ),
			'order_id'            => $download->get_order_id( 'edit' ),
			'order_key'           => $download->get_order_key( 'edit' ),
			'downloads_remaining' => $download->get_downloads_remaining( 'edit' ),
			'access_granted'      => date( 'Y-m-d', $download->get_access_granted( 'edit' )->getTimestamp() ),
			'download_count'      => $download->get_download_count( 'edit' ),
			'access_expires'      => ! is_null( $download->get_access_expires( 'edit' ) ) ? date( 'Y-m-d', $download->get_access_expires( 'edit' )->getTimestamp() ) : null,
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
	 * @param WC_Customer_Download $download WC_Customer_Download object.
	 * @param array                $args Array of args to pass to the delete method.
	 */
	public function delete( &$download, $args = array() ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
				WHERE permission_id = %d",
				$download->get_id()
			)
		);

		$download->set_id( 0 );
	}

	/**
	 * Method to delete a download permission from the database by ID.
	 *
	 * @param int $id permission_id of the download to be deleted.
	 */
	public function delete_by_id( $id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
				WHERE permission_id = %d",
				$id
			)
		);
	}

	/**
	 * Method to delete a download permission from the database by order ID.
	 *
	 * @param int $id Order ID of the downloads that will be deleted.
	 */
	public function delete_by_order_id( $id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
				WHERE order_id = %d",
				$id
			)
		);
	}

	/**
	 * Method to delete a download permission from the database by download ID.
	 *
	 * @param int $id download_id of the downloads that will be deleted.
	 */
	public function delete_by_download_id( $id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
				WHERE download_id = %s",
				$id
			)
		);
	}

	/**
	 * Method to delete a download permission from the database by user ID.
	 *
	 * @since 3.4.0
	 * @param int $id user ID of the downloads that will be deleted.
	 * @return bool True if deleted rows.
	 */
	public function delete_by_user_id( $id ) {
		global $wpdb;
		return (bool) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
				WHERE user_id = %d",
				$id
			)
		);
	}

	/**
	 * Method to delete a download permission from the database by user email.
	 *
	 * @since 3.4.0
	 * @param string $email email of the downloads that will be deleted.
	 * @return bool True if deleted rows.
	 */
	public function delete_by_user_email( $email ) {
		global $wpdb;
		return (bool) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
				WHERE user_email = %s",
				$email
			)
		);
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
	 * @param  array $args Arguments to filter downloads. $args['return'] accepts the following values: 'objects' (default), 'ids' or a comma separeted list of fields (for example: 'order_id,user_id,user_email').
	 * @return array Can be an array of permission_ids, an array of WC_Customer_Download objects or an array of arrays containing specified fields depending on the value of $args['return'].
	 */
	public function get_downloads( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'user_email'  => '',
				'user_id'     => '',
				'order_id'    => '',
				'order_key'   => '',
				'product_id'  => '',
				'download_id' => '',
				'orderby'     => 'permission_id',
				'order'       => 'ASC',
				'limit'       => -1,
				'page'        => 1,
				'return'      => 'objects',
			)
		);

		$valid_fields       = array( 'permission_id', 'download_id', 'product_id', 'order_id', 'order_key', 'user_email', 'user_id', 'downloads_remaining', 'access_granted', 'access_expires', 'download_count' );
		$get_results_output = ARRAY_A;

		if ( 'ids' === $args['return'] ) {
			$fields = 'permission_id';
		} elseif ( 'objects' === $args['return'] ) {
			$fields             = '*';
			$get_results_output = OBJECT;
		} else {
			$fields = explode( ',', (string) $args['return'] );
			$fields = implode( ', ', array_intersect( $fields, $valid_fields ) );
		}

		$query   = array();
		$query[] = "SELECT {$fields} FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE 1=1";

		if ( $args['user_email'] ) {
			$query[] = $wpdb->prepare( 'AND user_email = %s', sanitize_email( $args['user_email'] ) );
		}

		if ( $args['user_id'] ) {
			$query[] = $wpdb->prepare( 'AND user_id = %d', absint( $args['user_id'] ) );
		}

		if ( $args['order_id'] ) {
			$query[] = $wpdb->prepare( 'AND order_id = %d', $args['order_id'] );
		}

		if ( $args['order_key'] ) {
			$query[] = $wpdb->prepare( 'AND order_key = %s', $args['order_key'] );
		}

		if ( $args['product_id'] ) {
			$query[] = $wpdb->prepare( 'AND product_id = %d', $args['product_id'] );
		}

		if ( $args['download_id'] ) {
			$query[] = $wpdb->prepare( 'AND download_id = %s', $args['download_id'] );
		}

		$orderby     = in_array( $args['orderby'], $valid_fields, true ) ? $args['orderby'] : 'permission_id';
		$order       = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';
		$orderby_sql = sanitize_sql_orderby( "{$orderby} {$order}" );
		$query[]     = "ORDER BY {$orderby_sql}";

		if ( 0 < $args['limit'] ) {
			$query[] = $wpdb->prepare( 'LIMIT %d, %d', absint( $args['limit'] ) * absint( $args['page'] - 1 ), absint( $args['limit'] ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( implode( ' ', $query ), $get_results_output );

		switch ( $args['return'] ) {
			case 'ids':
				return wp_list_pluck( $results, 'permission_id' );
			case 'objects':
				return array_map( array( $this, 'get_download' ), $results );
			default:
				return $results;
		}
	}

	/**
	 * Update download ids if the hash changes.
	 *
	 * @deprecated 3.3.0 Download id is now a static UUID and should not be changed based on file hash.
	 *
	 * @param  int    $product_id Product ID.
	 * @param  string $old_id Old download_id.
	 * @param  string $new_id New download_id.
	 */
	public function update_download_id( $product_id, $old_id, $new_id ) {
		global $wpdb;

		wc_deprecated_function( __METHOD__, '3.3' );

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
	 * @param  int $customer_id Customer ID.
	 * @return array
	 */
	public function get_downloads_for_customer( $customer_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions as permissions
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
				ORDER BY permissions.order_id, permissions.product_id, permissions.permission_id;",
				$customer_id,
				date( 'Y-m-d', current_time( 'timestamp' ) )
			)
		);
	}

	/**
	 * Update user prop for downloads based on order id.
	 *
	 * @param  int    $order_id Order ID.
	 * @param  int    $customer_id Customer ID.
	 * @param  string $email Customer email address.
	 */
	public function update_user_by_order_id( $order_id, $customer_id, $email ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'woocommerce_downloadable_product_permissions',
			array(
				'user_id'    => $customer_id,
				'user_email' => $email,
			),
			array(
				'order_id' => $order_id,
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
