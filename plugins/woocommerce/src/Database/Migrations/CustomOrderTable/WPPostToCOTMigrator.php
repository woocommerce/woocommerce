<?php
/**
 * Class for implementing migration from wp_posts and wp_postmeta to custom order tables.
 */

namespace Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable;

use Automattic\WooCommerce\DataBase\Migrations\MigrationErrorLogger;

/**
 * Class WPPostToCOTMigrator
 *
 * @package Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable
 */
class WPPostToCOTMigrator {

	/**
	 * Error logger for migration errors.
	 *
	 * @var MigrationErrorLogger $error_logger
	 */
	private $error_logger;

	/**
	 * Migrator instance to actually perform migrations.
	 *
	 * @var MetaToCustomTableMigrator
	 */
	private $order_table_migrator;

	/**
	 * WPPostToCOTMigrator constructor.
	 */
	public function __construct() {
		global $wpdb;

		$order_table_schema_config = array(
			'entity_schema'        => array(
				'primary_id' => 'ID',
				'table_name' => $wpdb->posts,
			),
			'entity_meta_schema'   => array(
				'meta_key_column'   => 'meta_key',
				'meta_value_column' => 'meta_value',
				'table_name'        => $wpdb->postmeta,
			),
			'destination_table'    => $wpdb->prefix . 'wc_orders',
			'entity_meta_relation' => array(
				'entity' => 'ID',
				'meta'   => 'post_id',
			),

		);

		$order_table_core_config = array(
			'ID'                => array(
				'type'        => 'int',
				'destination' => 'post_id',
			),
			'post_status'       => array(
				'type'        => 'string',
				'destination' => 'status',
			),
			'post_date_gmt'     => array(
				'type'        => 'date',
				'destination' => 'date_created_gmt',
			),
			'post_modified_gmt' => array(
				'type'        => 'date',
				'destination' => 'date_updated_gmt',
			),
			'post_parent'       => array(
				'type'        => 'int',
				'destination' => 'parent_order_id',
			),
		);

		$order_table_meta_config    = array(
			'_order_currency'       => array(
				'type'        => 'string',
				'destination' => 'currency',
			),
			'_order_tax'            => array(
				'type'        => 'decimal',
				'destination' => 'tax_amount',
			),
			'_order_total'          => array(
				'type'        => 'decimal',
				'destination' => 'total_amount',
			),
			'_customer_user'        => array(
				'type'        => 'int',
				'destination' => 'customer_id',
			),
			'_billing_email'        => array(
				'type'        => 'string',
				'destination' => 'billing_email',
			),
			'_payment_method'       => array(
				'type'        => 'string',
				'destination' => 'payment_method',
			),
			'_payment_method_title' => array(
				'type'        => 'string',
				'destination' => 'payment_method_title',
			),
			'_customer_ip_address'  => array(
				'type'        => 'string',
				'destination' => 'ip_address',
			),
			'_customer_user_agent'  => array(
				'type'        => 'string',
				'destination' => 'user_agent',
			),
		);
		$this->order_table_migrator = new MetaToCustomTableMigrator( $order_table_schema_config, $order_table_meta_config, $order_table_core_config );
		$this->error_logger         = new MigrationErrorLogger();
	}

	/**
	 * Process next migration batch, uses option `wc_cot_migration` to checkpoints of what have been processed so far.
	 *
	 * @param int $batch_size Batch size of records to migrate.
	 *
	 * @return bool True if migration is completed, false if there are still records to process.
	 */
	public function process_next_migration_batch( $batch_size = 500 ) {
		global $wpdb;
		$order_by = 'ID ASC';

		$data = $this->order_table_migrator->fetch_data_for_migration( $this->get_where_clause(), $batch_size, $order_by );

		foreach ( $data['errors'] as $post_id => $error ) {
			$this->error_logger->log( 'info', "Error in importing post id $post_id: " . print_r( $error, true ) );
		}

		if ( count( $data['data'] ) === 0 ) {
			return true;
		}

		$queries = $this->order_table_migrator->generate_insert_sql_for_batch( $data['data'], 'insert' );
		$result  = $wpdb->query( $queries ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $queries is already prepared.
		if ( count( $data['data'] ) !== $result ) {
			// Some rows were not inserted.
			// TODO: Find and log the entity ids that were not inserted.
			echo ' error ';
		}

		$last_post_migrated = max( array_keys( $data['data'] ) );
		$this->update_checkpoint( $last_post_migrated );

		return false;
	}

	/**
	 * Method to migrate single record.
	 *
	 * @param int $post_id Post ID of record to migrate.
	 *
	 * @return bool|\WP_Error
	 */
	public function process_single( $post_id ) {
		global $wpdb;

		$where_clause = $wpdb->prepare( 'ID = %d', $post_id );
		$data         = $this->order_table_migrator->fetch_data_for_migration( $where_clause, 1, 'ID ASC' );
		if ( isset( $data['errors'][ $post_id ] ) ) {
			return new \WP_Error( $data['errors'][ $post_id ] );
		}

		$queries = $this->order_table_migrator->generate_insert_sql_for_batch( $data['data'], 'replace' );
		$result  = $wpdb->query( $queries ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $queries is already prepared.
		if ( 1 !== $result ) {
			// TODO: Fetch and return last error.
			echo 'error';

			return new \WP_Error( 'error' );
		}

		return true;
	}

	/**
	 * Helper function to get where clause to send to MetaToCustomTableMigrator instance.
	 *
	 * @return string|void Where clause.
	 */
	private function get_where_clause() {
		global $wpdb;

		$checkpoint   = $this->get_checkpoint();
		$where_clause = $wpdb->prepare(
			'post_type = "shop_order" AND ID > %d',
			$checkpoint['id']
		);

		return $where_clause;
	}

	/**
	 * Current checkpoint status.
	 *
	 * @return false|mixed|void
	 */
	private function get_checkpoint() {
		return get_option( 'wc_cot_migration', array( 'id' => 0 ) );
	}

	/**
	 * Updates current checkpoint
	 *
	 * @param int $id Order ID.
	 */
	public function update_checkpoint( $id ) {
		update_option( 'wc_cot_migration', array( 'id' => $id ) );
	}
}
