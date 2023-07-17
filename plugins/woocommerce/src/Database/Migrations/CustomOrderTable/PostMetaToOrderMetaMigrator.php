<?php
/**
 * Migration class for migrating from WPPostMeta to OrderMeta table.
 */

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Database\Migrations\MetaToMetaTableMigrator;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;

/**
 * Helper class to migrate records from the WordPress post meta table
 * to the custom orders meta table.
 *
 * @package Automattic\WooCommerce\Database\Migrations\CustomOrderTable
 */
class PostMetaToOrderMetaMigrator extends MetaToMetaTableMigrator {

	/**
	 * List of meta keys to exclude from migration.
	 *
	 * @var array
	 */
	private $excluded_columns;

	/**
	 * PostMetaToOrderMetaMigrator constructor.
	 *
	 * @param array $excluded_columns List of meta keys to exclude from migration.
	 */
	public function __construct( $excluded_columns ) {
		$this->excluded_columns = $excluded_columns;
		parent::__construct();
	}

	/**
	 * Generate config for meta data migration.
	 *
	 * @return array Meta data migration config.
	 */
	protected function get_meta_config(): array {
		global $wpdb;

		return array(
			'source'      => array(
				'meta'          => array(
					'table_name'        => $wpdb->postmeta,
					'entity_id_column'  => 'post_id',
					'meta_id_column'    => 'meta_id',
					'meta_key_column'   => 'meta_key',
					'meta_value_column' => 'meta_value',
				),
				'entity'        => array(
					'table_name'       => $wpdb->posts,
					'source_id_column' => 'ID',
					'id_column'        => 'ID',
				),
				'excluded_keys' => $this->excluded_columns,
			),
			'destination' => array(
				'meta' => array(
					'table_name'        => OrdersTableDataStore::get_meta_table_name(),
					'entity_id_column'  => 'order_id',
					'meta_key_column'   => 'meta_key',
					'meta_value_column' => 'meta_value',
					'entity_id_type'    => 'int',
					'meta_id_column'    => 'id',
				),
			),
		);
	}
}
