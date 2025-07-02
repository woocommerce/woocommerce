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
	 * Database utility instance.
	 *
	 * @var DatabaseUtil
	 */
	private DatabaseUtil $database_util;

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
		/**
		 * FeaturesController instance.
		 *
		 * @var FeaturesController $features_controller
		 */
		$features_controller = wc_get_container()->get( FeaturesController::class );
		if ( ! $features_controller->feature_is_enabled( 'fulfillments' ) ) {
			return;
		}

		// Create the database tables if they do not exist.
		$this->maybe_create_db_tables();

		// At this point, the database tables are created, so we can safely add them to the list of tables to install or drop.
		add_filter( 'woocommerce_install_get_tables', array( $this, 'add_tables_to_install' ) );

		// Register the classes that this controller provides.
		$container = wc_get_container();
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

		/**
		 * Check if the tables already exist.
		 *
		 * Checking if an option is set will also send a query to the database, so there's
		 * nothing different in terms of performance with checking the table existence directly,
		 * and checking an option.
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wc_order_fulfillments'" ) ) {
			return; // Tables already exist, no need to create them.
		}

		$collate          = '';
		$max_index_length = $this->database_util->get_max_index_length();
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

		$this->database_util->dbdelta( $schema );
	}

	/**
	 * Add the fulfillment tables to the list of tables to be installed, or dropped when uninstalling.
	 *
	 * @param array $tables The list of tables to install/drop.
	 * @return array The updated list of tables.
	 */
	public function add_tables_to_install( array $tables ): array {
		global $wpdb;

		$tables[] = "{$wpdb->prefix}wc_order_fulfillments";
		$tables[] = "{$wpdb->prefix}wc_order_fulfillment_meta";

		return $tables;
	}
}
