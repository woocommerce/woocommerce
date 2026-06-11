<?php
/**
 * LocationStockInstaller class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Inventory;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Creates POS location stock tables and required setup rows.
 *
 * @internal
 */
class LocationStockInstaller {

	/**
	 * Option key used to latch schema creation.
	 */
	public const TABLES_CREATED_OPTION = 'woocommerce_pos_location_stock_db_tables_created';

	/**
	 * Option key used to latch POS location creation.
	 */
	public const POS_LOCATION_CREATED_OPTION = 'woocommerce_pos_location_stock_pos_location_created';

	private const MISSING_TABLES_OPTION = 'woocommerce_pos_location_stock_schema_missing_tables';

	/**
	 * Database utilities.
	 *
	 * @var DatabaseUtil
	 */
	private DatabaseUtil $database_util;

	/**
	 * Feature and configuration gate.
	 *
	 * @var LocationStockGate
	 */
	private LocationStockGate $gate;

	/**
	 * Location stock service.
	 *
	 * @var LocationStockService
	 */
	private LocationStockService $location_stock_service;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 */
	final public function init( DatabaseUtil $database_util, LocationStockGate $gate, LocationStockService $location_stock_service ): void {
		$this->database_util          = $database_util;
		$this->gate                   = $gate;
		$this->location_stock_service = $location_stock_service;
	}

	/**
	 * Register installer hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_installed', array( $this, 'maybe_create_db_tables' ) );
		add_action( 'woocommerce_updated', array( $this, 'maybe_create_db_tables' ) );
		add_action( 'init', array( $this, 'maybe_create_db_tables' ), 5 );
	}

	/**
	 * Create inventory tables when POS location stock is enabled.
	 */
	public function maybe_create_db_tables(): void {
		if ( ! $this->gate->feature_is_enabled() ) {
			return;
		}

		if ( 'yes' === get_option( self::TABLES_CREATED_OPTION, 'no' ) && $this->location_stock_service->tables_exist() ) {
			$this->maybe_ensure_pos_location();
			return;
		}

		$schema = $this->location_stock_service->get_database_schema();
		$this->database_util->dbdelta( $schema );

		$missing_tables = $this->database_util->get_missing_tables( $schema );
		if ( ! empty( $missing_tables ) ) {
			update_option( self::TABLES_CREATED_OPTION, 'no' );
			update_option( self::MISSING_TABLES_OPTION, $missing_tables );
			return;
		}

		update_option( self::TABLES_CREATED_OPTION, 'yes' );
		delete_option( self::MISSING_TABLES_OPTION );
		$this->ensure_pos_location();
	}

	/**
	 * Ensure the POS row once after table creation or upgrade.
	 */
	private function maybe_ensure_pos_location(): void {
		if ( 'yes' === get_option( self::POS_LOCATION_CREATED_OPTION, 'no' ) ) {
			return;
		}

		$this->ensure_pos_location();
	}

	/**
	 * Ensure the POS row and latch the setup.
	 */
	private function ensure_pos_location(): void {
		$this->location_stock_service->ensure_pos_location();
		update_option( self::POS_LOCATION_CREATED_OPTION, 'yes' );
	}
}
