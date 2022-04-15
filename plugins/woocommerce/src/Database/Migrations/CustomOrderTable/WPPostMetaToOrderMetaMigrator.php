<?php

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

class WPPostMetaToOrderMetaMigrator extends MetaToMetaTableMigrator {

	private $excluded_columns;

	public function __construct( $excluded_columns ) {
		$this->excluded_columns = $excluded_columns;
		parent::__construct();
	}

	/**
	 * Generate config for meta data migration.
	 *
	 * @return array Meta data migration config.
	 */
	public function get_meta_config() {
		global $wpdb;
		// TODO: Remove hardcoding.
		$this->table_names = array(
			'orders'    => $wpdb->prefix . 'wc_orders',
			'addresses' => $wpdb->prefix . 'wc_order_addresses',
			'op_data'   => $wpdb->prefix . 'wc_order_operational_data',
			'meta'      => $wpdb->prefix . 'wc_orders_meta',
		);

		return array(
			'source'      => array(
				'meta'          => array(
					'table_name'        => $wpdb->postmeta,
					'entity_id_column'  => 'post_id',
					'meta_key_column'   => 'meta_key',
					'meta_value_column' => 'meta_value',
				),
				'entity' => array(
					'table_name'       => $this->table_names['orders'],
					'source_id_column' => 'post_id',
					'id_column'        => 'id',
				),
				'excluded_keys' => $this->excluded_columns,
			),
			'destination' => array(
				'meta'   => array(
					'table_name'        => $this->table_names['meta'],
					'entity_id_column'  => 'order_id',
					'meta_key_column'   => 'meta_key',
					'meta_value_column' => 'meta_value',
					'entity_id_type'    => 'int',
					'meta_id_column'    => 'id'
				),
			),
		);
	}
}
