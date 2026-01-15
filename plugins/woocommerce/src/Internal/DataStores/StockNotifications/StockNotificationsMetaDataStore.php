<?php
/**
 * StockNotificationsMetaDataStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DataStores\StockNotifications;

use Automattic\WooCommerce\Internal\DataStores\CustomMetaDataStore;

defined( 'ABSPATH' ) || exit;

/**
 * Mimics a WP metadata (i.e. add_metadata(), get_metadata() and friends) implementation using a custom table.
 */
class StockNotificationsMetaDataStore extends CustomMetaDataStore {

	/**
	 * Returns the name of the table used for storage.
	 *
	 * @return string
	 */
	public function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wc_stock_notificationmeta';
	}

	/**
	 * Returns the name of the field/column used for identifiying metadata entries.
	 *
	 * @return string
	 */
	protected function get_meta_id_field() {
		return 'id';
	}

	/**
	 * Returns the name of the field/column used for associating meta with objects.
	 *
	 * @return string
	 */
	protected function get_object_id_field() {
		return 'notification_id';
	}

	/**
	 * Delete by notification ID.
	 *
	 * @param int $notification_id The notification ID.
	 * @return bool True if the metadata were deleted, false otherwise.
	 */
	public function delete_by_notification_id( $notification_id ) {
		global $wpdb;

		$table  = $this->get_table_name();
		$result = $wpdb->delete(
			$table,
			array( 'notification_id' => $notification_id ),
			array( '%d' )
		);

		return false === $result ? false : true;
	}
}
