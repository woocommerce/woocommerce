<?php
/**
 * LocationsController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Locations;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the wc_locations table and the shared location domain wiring.
 *
 * @internal
 */
class LocationsController extends \Automattic\WooCommerce\Internal\AbstractFeatureTablesInstaller {

	public const TABLES_CREATED_OPTION    = 'woocommerce_locations_db_tables_created';
	public const POS_LOCATION_TYPE        = 'pos';
	public const CONSUMER_FILTER          = 'woocommerce_location_feature_consumers';
	public const DEFAULT_LOCATIONS_OPTION = 'woocommerce_default_locations';

	/**
	 * Get the wc_locations table name.
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_locations';
	}

	/**
	 * Get the CREATE TABLE statement for wc_locations.
	 */
	public function get_database_schema(): string {
		global $wpdb;

		$collate    = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
		$table_name = $this->get_table_name();

		return "CREATE TABLE {$table_name} (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	type varchar(20) NOT NULL,
	address_1 varchar(255) NOT NULL DEFAULT '',
	address_2 varchar(255) NOT NULL DEFAULT '',
	city varchar(100) NOT NULL DEFAULT '',
	state varchar(100) NOT NULL DEFAULT '',
	postcode varchar(20) NOT NULL DEFAULT '',
	country char(2) NOT NULL DEFAULT '',
	date_created_gmt datetime NOT NULL,
	date_modified_gmt datetime NOT NULL,
	date_deleted_gmt datetime NULL DEFAULT NULL,
	PRIMARY KEY  (id),
	KEY type (type)
) $collate;";
	}

	/**
	 * Register the location data store.
	 *
	 * @param array $data_stores Registered data stores.
	 * @return array
	 */
	public function register_data_store( $data_stores ) {
		if ( is_array( $data_stores ) ) {
			$data_stores['location'] = LocationDataStore::class;
		}
		return $data_stores;
	}

	/**
	 * Feature ids that consume the locations domain (OR of these = "locations enabled").
	 *
	 * @return string[]
	 */
	public function get_consumer_feature_ids(): array {
		/**
		 * Filters the feature ids that consume the locations domain. The locations
		 * feature is considered enabled when any one of these feature ids is enabled.
		 *
		 * @param string[] $feature_ids Feature ids.
		 *
		 * @since 11.0.0
		 */
		return (array) apply_filters( self::CONSUMER_FILTER, array() );
	}

	/**
	 * Whether any location-consumer feature is enabled.
	 */
	public function is_enabled(): bool {
		foreach ( $this->get_consumer_feature_ids() as $feature_id ) {
			if ( FeaturesUtil::feature_is_enabled( (string) $feature_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the id of the active default location for a type, or 0. Reads the
	 * woocommerce_default_locations option map and self-heals a dangling entry.
	 *
	 * @param string $type Location type.
	 */
	public function get_default_location_id( string $type ): int {
		$map = (array) get_option( self::DEFAULT_LOCATIONS_OPTION, array() );
		$id  = isset( $map[ $type ] ) ? (int) $map[ $type ] : 0;
		if ( $id > 0 && ( new LocationDataStore() )->is_active_location( $id ) ) {
			return $id;
		}
		return 0;
	}

	/**
	 * Set the default location for a type (one per type, inherently).
	 *
	 * @param string $type        Location type.
	 * @param int    $location_id Location id.
	 */
	public function set_default_location( string $type, int $location_id ): void {
		$map          = (array) get_option( self::DEFAULT_LOCATIONS_OPTION, array() );
		$map[ $type ] = $location_id;
		update_option( self::DEFAULT_LOCATIONS_OPTION, $map );
	}

	/**
	 * Seed the default pos location from store settings if none exists. Idempotent.
	 *
	 * The address is a one-time snapshot, not a live mirror of store settings.
	 */
	public function maybe_seed_default_location(): void {
		if ( $this->get_default_location_id( self::POS_LOCATION_TYPE ) > 0 ) {
			return;
		}

		$name = (string) get_option( 'woocommerce_pos_store_name', '' );
		if ( '' === $name ) {
			$name = (string) get_bloginfo( 'name' );
		}

		$base = wc_get_base_location();

		$location = new Location();
		$location->set_type( self::POS_LOCATION_TYPE );
		$location->set_name( $name );
		$location->set_address_1( (string) get_option( 'woocommerce_store_address', '' ) );
		$location->set_address_2( (string) get_option( 'woocommerce_store_address_2', '' ) );
		$location->set_city( (string) get_option( 'woocommerce_store_city', '' ) );
		$location->set_postcode( (string) get_option( 'woocommerce_store_postcode', '' ) );
		$location->set_country( (string) ( $base['country'] ?? '' ) );
		$location->set_state( (string) ( $base['state'] ?? '' ) );
		$location->save();

		$this->set_default_location( self::POS_LOCATION_TYPE, $location->get_id() );
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
		return in_array( $feature_id, $this->get_consumer_feature_ids(), true );
	}

	/**
	 * Seed the default location once the table has been created.
	 */
	protected function after_tables_created(): void {
		$this->maybe_seed_default_location();
	}

	/**
	 * Register subclass-specific hooks.
	 */
	protected function register_hooks(): void {
		add_filter( 'woocommerce_data_stores', array( $this, 'register_data_store' ) );
	}
}
