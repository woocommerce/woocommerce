<?php
/**
 * AbstractFeatureTablesInstaller class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Base for controllers that own a feature-gated custom table created on-enable
 * (never via WC_Install::get_schema()) and dropped on uninstall.
 *
 * @internal
 */
abstract class AbstractFeatureTablesInstaller {

	/**
	 * Database utility.
	 *
	 * @var DatabaseUtil
	 */
	protected $database_util;

	/**
	 * Dependency injection.
	 *
	 * @internal
	 *
	 * @param DatabaseUtil $database_util Database utility.
	 */
	final public function init( DatabaseUtil $database_util ): void {
		$this->database_util = $database_util;
	}

	/**
	 * The table name this installer owns.
	 */
	abstract public function get_table_name(): string;

	/**
	 * The CREATE TABLE schema for the owned table.
	 */
	abstract public function get_database_schema(): string;

	/**
	 * Whether this installer's feature is enabled.
	 */
	abstract public function is_enabled(): bool;

	/**
	 * The option name latching that the table has been created.
	 */
	abstract protected function get_tables_created_option(): string;

	/**
	 * Whether a feature-enabled-changed event for $feature_id should trigger install.
	 *
	 * @param string $feature_id Feature id.
	 */
	abstract protected function handles_feature( string $feature_id ): bool;

	/**
	 * Whether the owned table exists.
	 */
	public function tables_exist(): bool {
		global $wpdb;
		$table_name = $this->get_table_name();
		return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) );
	}

	/**
	 * Create the owned table (dbDelta; callers guard on tables_exist()).
	 */
	public function create_tables(): void {
		$this->database_util->dbdelta( $this->get_database_schema() );
	}

	/**
	 * Register the owned table so uninstall drops it.
	 *
	 * @param array $tables Table names.
	 * @return array
	 */
	public function add_table_to_install_list( $tables ) {
		if ( is_array( $tables ) ) {
			$tables[] = $this->get_table_name();
		}
		return $tables;
	}

	/**
	 * Create the table (and run any post-create step) if the feature is enabled. Idempotent.
	 * On failure the latch is left unset so a later call retries.
	 */
	public function maybe_install(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		$tables_exist = $this->tables_exist();
		if ( 'yes' === get_option( $this->get_tables_created_option() ) && $tables_exist ) {
			return;
		}
		try {
			if ( ! $tables_exist ) {
				$this->create_tables();
			}
			$this->after_tables_created();
		} catch ( \Exception $e ) {
			wc_get_logger()->error(
				'Multi-location install/seed failed: ' . $e->getMessage(),
				array( 'source' => 'multi-location-inventory' )
			);
			return;
		}
		update_option( $this->get_tables_created_option(), 'yes' );
	}

	/**
	 * React to a feature flag transition.
	 *
	 * @param string $feature_id Feature id.
	 * @param bool   $enabled    New enabled state.
	 */
	public function on_feature_enabled_changed( $feature_id, $enabled ): void {
		if ( $enabled && $this->handles_feature( (string) $feature_id ) ) {
			$this->maybe_install();
		}
	}

	/**
	 * Register hooks. Called once from the bootstrap.
	 */
	public function register(): void {
		add_filter( 'woocommerce_install_get_tables', array( $this, 'add_table_to_install_list' ) );
		add_action( 'woocommerce_feature_enabled_changed', array( $this, 'on_feature_enabled_changed' ), 10, 2 );
		add_action( 'woocommerce_installed', array( $this, 'maybe_install' ) );
		add_action( 'woocommerce_updated', array( $this, 'maybe_install' ) );
		$this->register_hooks();
	}

	/**
	 * Post-create step (e.g. seeding). Default no-op; subclasses may override.
	 */
	protected function after_tables_created(): void {}

	/**
	 * Register subclass-specific hooks (data-store filter, consumer filter, ...). Default no-op.
	 */
	protected function register_hooks(): void {}
}
