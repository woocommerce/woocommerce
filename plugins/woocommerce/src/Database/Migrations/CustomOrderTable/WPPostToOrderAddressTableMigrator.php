<?php
/**
 * Class for WPPost to wc_order_address table migrator.
 */

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

/**
 * Class WPPostToOrderAddressTableMigrator
 *
 * @package Automattic\WooCommerce\Database\Migrations\CustomOrderTable
 */
class WPPostToOrderAddressTableMigrator extends MetaToCustomTableMigrator {
	/**
	 * Type of addresses being migrated, could be billing|shipping.
	 *
	 * @var $type
	 */
	protected $type;

	/**
	 * WPPostToOrderAddressTableMigrator constructor.
	 *
	 * @param string $type Type of addresses being migrated, could be billing|shipping.
	 */
	public function __construct( $type ) {
		$this->type = $type;
		parent::__construct();
	}

	/**
	 * Get schema config for wp_posts and wc_order_address table.
	 *
	 * @return array Config.
	 */
	public function get_schema_config() {
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
				'entity' => array(
					'table_name'             => $this->table_names['orders'],
					'meta_rel_column'        => 'post_id',
					'destination_rel_column' => 'id',
					'primary_key'            => 'post_id',
				),
				'meta'   => array(
					'table_name'        => $wpdb->postmeta,
					'meta_key_column'   => 'meta_key',
					'meta_value_column' => 'meta_value',
					'entity_id_column'  => 'post_id',
				),
			),
			'destination' => array(
				'table_name'        => $this->table_names['addresses'],
				'source_rel_column' => 'order_id',
				'primary_key'       => 'id',
				'primary_key_type'  => 'int',
			),
		);
	}

	/**
	 * Get columns config.
	 *
	 * @return \string[][] Config.
	 */
	public function get_core_column_mapping() {
		$type = $this->type;

		return array(
			'id'   => array(
				'type'        => 'int',
				'destination' => 'order_id',
			),
			'type' => array(
				'type'          => 'string',
				'destination'   => 'address_type',
				'select_clause' => "'$type'",
			),
		);
	}

	/**
	 * Get meta data config.
	 *
	 * @return \string[][] Config.
	 */
	public function get_meta_column_config() {
		$type = $this->type;

		return array(
			"_{$type}_first_name" => array(
				'type'        => 'string',
				'destination' => 'first_name',
			),
			"_{$type}_last_name"  => array(
				'type'        => 'string',
				'destination' => 'last_name',
			),
			"_{$type}_company"    => array(
				'type'        => 'string',
				'destination' => 'company',
			),
			"_{$type}_address_1"  => array(
				'type'        => 'string',
				'destination' => 'address_1',
			),
			"_{$type}_address_2"  => array(
				'type'        => 'string',
				'destination' => 'address_2',
			),
			"_{$type}_city"       => array(
				'type'        => 'string',
				'destination' => 'city',
			),
			"_{$type}_state"      => array(
				'type'        => 'string',
				'destination' => 'state',
			),
			"_{$type}_postcode"   => array(
				'type'        => 'string',
				'destination' => 'postcode',
			),
			"_{$type}_country"    => array(
				'type'        => 'string',
				'destination' => 'country',
			),
			"_{$type}_email"      => array(
				'type'        => 'string',
				'destination' => 'email',
			),
			"_{$type}_phone"      => array(
				'type'        => 'string',
				'destination' => 'phone',
			),
		);
	}

	/**
	 * We overwrite this method to add a subclause to only fetch address of current type.
	 *
	 * @param array $entity_ids List of entity IDs to verify.
	 *
	 * @return array Already migrated entities, would be of the form
	 * array(
	 *      '$source_id1' => array(
	 *          'source_id' => $source_id1,
	 *          'destination_id' => $destination_id1,
	 *      ),
	 *      ...
	 * )
	 */
	public function get_already_migrated_records( $entity_ids ) {
		global $wpdb;
		$source_table                   = $this->schema_config['source']['entity']['table_name'];
		$source_destination_join_column = $this->schema_config['source']['entity']['destination_rel_column'];
		$source_primary_key_column      = $this->schema_config['source']['entity']['primary_key'];

		$destination_table              = $this->schema_config['destination']['table_name'];
		$destination_source_join_column = $this->schema_config['destination']['source_rel_column'];
		$destination_primary_key_column = $this->schema_config['destination']['primary_key'];

		$address_type = $this->type;

		$entity_id_placeholder = implode( ',', array_fill( 0, count( $entity_ids ), '%d' ) );

		$already_migrated_entity_ids = $wpdb->get_results(
			$wpdb->prepare(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- All columns and table names are hardcoded.
				"
SELECT source.`$source_primary_key_column` as source_id, destination.`$destination_primary_key_column` as destination_id
FROM `$destination_table` destination
JOIN `$source_table` source ON source.`$source_destination_join_column` = destination.`$destination_source_join_column`
WHERE source.`$source_primary_key_column` IN ( $entity_id_placeholder ) AND destination.`address_type` = '$address_type'
				",
				$entity_ids
			)
		// phpcs:enable
		);

		return array_column( $already_migrated_entity_ids, null, 'source_id' );
	}
}
