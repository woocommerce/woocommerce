<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

/**
 * Class FulfillmentsController
 *
 * Base controller for fulfillments management.
 */
class FulfillmentsController {
	/**
	 * Provides the list of classes that this controller provides.
	 *
	 * @var string[]
	 */
	private $provides = array(
		FulfillmentsManager::class,
		FulfillmentsRenderer::class,
		FulfillmentsSettings::class,
		OrderFulfillmentsRestController::class,
	);

	/**
	 * Initialize the controller.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'initialize_fulfillments' ), 10, 0 );
	}

	/**
	 * Initialize the fulfillments controller.
	 */
	public function initialize_fulfillments() {
		$container           = wc_get_container();
		$features_controller = $container->get( FeaturesController::class );

		// If fulfillments feature is not enabled, do not add the DB tables, and don't register the controller.
		if ( ! $features_controller->feature_is_enabled( 'fulfillments' ) ) {
			return;
		}

		// Create the database tables if they do not exist.
		$this->maybe_create_db_tables();

		// Register the classes that this controller provides.
		foreach ( $this->provides as $class ) {
			$class = $container->get( $class );
			if ( method_exists( $class, 'register' ) ) {
				$class->register();
			}
		}
	}

	/**
	 * Create the database tables if they do not exist.
	 *
	 * @return void
	 */
	private function maybe_create_db_tables(): void {
		global $wpdb;

		if ( get_option( 'woocommerce_fulfillments_db_tables_created', false ) ) {
			// The tables already exist, no need to create them again.
			return;
		}

		// Drop the tables if they exist, to ensure a clean slate.
		// If one table exists and the other does not, it will be an issue.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_order_fulfillments" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_order_fulfillment_meta" );

		// Bulk delete order fulfillment status meta from legacy and HPOS order tables.
		$this->bulk_delete_order_fulfillment_status_meta();

		$collate       = '';
		$container     = wc_get_container();
		$database_util = $container->get( DatabaseUtil::class );

		$max_index_length = $database_util->get_max_index_length();
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$schema = "CREATE TABLE {$wpdb->prefix}wc_order_fulfillments (
			fulfillment_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entity_type varchar(255) NOT NULL,
			entity_id bigint(20) unsigned NOT NULL,
			status varchar(255) NOT NULL,
			is_fulfilled tinyint(1) NOT NULL DEFAULT 0,
			date_updated datetime NOT NULL,
			date_deleted datetime NULL,
			PRIMARY KEY (fulfillment_id),
			KEY entity_type_id (entity_type({$max_index_length}), entity_id)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_order_fulfillment_meta (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			fulfillment_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) NULL,
			meta_value longtext NULL,
			date_updated datetime NOT NULL,
			date_deleted datetime NULL,
			PRIMARY KEY (meta_id),
			KEY meta_key (meta_key({$max_index_length})),
			KEY fulfillment_id (fulfillment_id)
		) $collate;";

		$database_util->dbdelta( $schema );

		// Update the option to indicate that the tables have been created.
		update_option( 'woocommerce_fulfillments_db_tables_created', true );
	}

	/**
	 * Bulk delete fulfillment status meta for specific order IDs, or all orders if no order ID specified.
	 *
	 * This method deletes the fulfillment status meta for the specified order IDs from both the legacy postmeta table
	 * and the HPOS meta table.
	 *
	 * @param array<int> $order_ids Array of order IDs to delete fulfillment status meta for.
	 */
	private function bulk_delete_order_fulfillment_status_meta( $order_ids = array() ): void {
		$this->delete_legacy_order_fulfillment_meta( $order_ids );
		$this->delete_hpos_order_fulfillment_meta( $order_ids );
	}

	/**
	 * Delete fulfillment status meta from legacy postmeta table.
	 *
	 * @param array<int> $order_ids Array of order IDs to delete fulfillment status meta for.
	 */
	private function delete_legacy_order_fulfillment_meta( $order_ids = array() ) {
		global $wpdb;

		if ( ! empty( $order_ids ) ) {
			$order_params = array_merge( array( '_fulfillment_status' ), $order_ids );
			$wpdb->query(
				$wpdb->prepare(
					"DELETE pm FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE p.post_type = 'shop_order'
					AND pm.meta_key = %s
					AND pm.post_id IN (" . implode( ',', array_fill( 0, count( $order_ids ), '%d' ) ) . ')',
					...$order_params
				)
			);
		} else {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE pm FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE p.post_type = 'shop_order'
					AND pm.meta_key = %s",
					'_fulfillment_status'
				)
			);
		}
	}

	/**
	 * Delete fulfillment status meta from HPOS meta table.
	 *
	 * @param array<int> $order_ids Array of order IDs to delete fulfillment status meta for.
	 */
	private function delete_hpos_order_fulfillment_meta( $order_ids = array() ): void {
		global $wpdb;

		// Check if HPOS meta table exists.
		$table_name = $wpdb->prefix . 'wc_orders_meta';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return;
		}

		if ( ! empty( $order_ids ) ) {
			$order_params = array_merge( array( '_fulfillment_status' ), $order_ids );
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}wc_orders_meta
					WHERE meta_key = %s
					AND order_id IN (" . implode( ',', array_fill( 0, count( $order_ids ), '%d' ) ) . ')',
					...$order_params
				)
			);
		} else {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}wc_orders_meta
					WHERE meta_key = %s",
					'_fulfillment_status'
				)
			);
		}
	}
}
