<?php
/**
 * ProductInventoryController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiLocationInventory;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the wc_product_inventory table; first consumer of the locations domain.
 *
 * @internal
 */
class ProductInventoryController extends \Automattic\WooCommerce\Internal\AbstractFeatureTablesInstaller {

	public const TABLES_CREATED_OPTION = 'woocommerce_multi_location_inventory_db_tables_created';
	public const FEATURE_ID            = 'multi_location_inventory';

	/**
	 * Get the wc_product_inventory table name.
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_product_inventory';
	}

	/**
	 * Get the CREATE TABLE statement.
	 */
	public function get_database_schema(): string {
		global $wpdb;

		$collate    = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
		$table_name = $this->get_table_name();

		return "CREATE TABLE {$table_name} (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	product_id bigint(20) unsigned NOT NULL,
	variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
	location_id bigint(20) unsigned NOT NULL,
	quantity decimal(19,6) NOT NULL DEFAULT 0,
	PRIMARY KEY  (id),
	UNIQUE KEY product_variation_location (product_id, variation_id, location_id),
	KEY location (location_id, product_id, variation_id)
) $collate;";
	}

	/**
	 * Whether the inventory feature is enabled.
	 */
	public function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}

	/**
	 * Register this feature as a location consumer.
	 *
	 * @param array $ids Consumer feature ids.
	 * @return array
	 */
	public function register_as_location_consumer( $ids ) {
		if ( is_array( $ids ) ) {
			$ids[] = self::FEATURE_ID;
		}
		return $ids;
	}

	/**
	 * The option name latching that the table has been created.
	 */
	protected function get_tables_created_option(): string {
		return self::TABLES_CREATED_OPTION;
	}

	/**
	 * Whether a feature-enabled-changed event for $feature_id should trigger install.
	 *
	 * @param string $feature_id Feature id.
	 */
	protected function handles_feature( string $feature_id ): bool {
		return self::FEATURE_ID === $feature_id;
	}

	/**
	 * Register subclass-specific hooks.
	 */
	protected function register_hooks(): void {
		add_filter( 'woocommerce_location_feature_consumers', array( $this, 'register_as_location_consumer' ) );
	}
}
