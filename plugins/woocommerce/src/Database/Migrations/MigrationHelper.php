<?php
/**
 * Helper class with utility functions for migrations.
 */

namespace Automattic\WooCommerce\Database\Migrations;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * Helper class to assist with migration related operations.
 */
class MigrationHelper {

	/**
	 * Placeholders that we will use in building $wpdb queries.
	 *
	 * @var string[]
	 */
	private static $wpdb_placeholder_for_type = array(
		'int'        => '%d',
		'decimal'    => '%f',
		'string'     => '%s',
		'date'       => '%s',
		'date_epoch' => '%s',
		'bool'       => '%d',
	);

	/**
	 * Helper method to escape backtick in various schema fields.
	 *
	 * @param array $schema_config Schema config.
	 *
	 * @return array Schema config escaped for backtick.
	 */
	public static function escape_schema_for_backtick( array $schema_config ): array {
		array_walk( $schema_config['source']['entity'], array( self::class, 'escape_and_add_backtick' ) );
		array_walk( $schema_config['source']['meta'], array( self::class, 'escape_and_add_backtick' ) );
		array_walk( $schema_config['destination'], array( self::class, 'escape_and_add_backtick' ) );
		return $schema_config;
	}

	/**
	 * Helper method to escape backtick in column and table names.
	 * WP does not provide a method to escape table/columns names yet, but hopefully soon in @link https://core.trac.wordpress.org/ticket/52506
	 *
	 * @param string|array $identifier Column or table name.
	 *
	 * @return array|string|string[] Escaped identifier.
	 */
	public static function escape_and_add_backtick( $identifier ) {
		return '`' . str_replace( '`', '``', $identifier ) . '`';
	}

	/**
	 * Return $wpdb->prepare placeholder for data type.
	 *
	 * @param string $type Data type.
	 *
	 * @return string $wpdb placeholder.
	 */
	public static function get_wpdb_placeholder_for_type( string $type ): string {
		return self::$wpdb_placeholder_for_type[ $type ];
	}

	/**
	 * Generates ON DUPLICATE KEY UPDATE clause to be used in migration.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return string SQL clause for INSERT...ON DUPLICATE KEY UPDATE
	 */
	public static function generate_on_duplicate_statement_clause( array $columns ): string {
		$db_util = wc_get_container()->get( DatabaseUtil::class );
		return $db_util->generate_on_duplicate_statement_clause( $columns );
	}

	/**
	 * Migrate state codes in all the required places in the database (hopefully) when they change for a given country.
	 *
	 * @param string $country_code The country that has the states for which the migration is needed.
	 * @param array  $old_to_new_states_mapping An associative array where keys are the old state codes and values are the new state codes.
	 * @return void
	 */
	public static function migrate_country_states( string $country_code, array $old_to_new_states_mapping ): void {
		self::migrate_country_states_for_shipping_locations( $country_code, $old_to_new_states_mapping );

		// We'll migrate only the authoritative orders table,
		// the sync mechanism (if enabled) will take care of updating the backup table.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			self::migrate_country_states_for_cot_orders( $country_code, $old_to_new_states_mapping );
		} else {
			self::migrate_country_states_for_post_orders( $country_code, $old_to_new_states_mapping );
		}

		self::migrate_country_states_for_store_location( $country_code, $old_to_new_states_mapping );
	}

	/**
	 * Migrate state codes in the shipping locations table.
	 *
	 * @param string $country_code The country that has the states for which the migration is needed.
	 * @param array  $old_to_new_states_mapping An associative array where keys are the old state codes and values are the new state codes.
	 * @return void
	 */
	private static function migrate_country_states_for_shipping_locations( string $country_code, array $old_to_new_states_mapping ): void {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared

		$sql            = "SELECT location_id, location_code FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE location_code LIKE '{$country_code}:%'";
		$locations_data = $wpdb->get_results( $sql, ARRAY_A );

		foreach ( $locations_data as $location_data ) {
			$old_state_code = substr( $location_data['location_code'], 3 );
			if ( array_key_exists( $old_state_code, $old_to_new_states_mapping ) ) {
				$new_location_code = "{$country_code}:{$old_to_new_states_mapping[$old_state_code]}";
				$update_query      = $wpdb->prepare(
					"UPDATE {$wpdb->prefix}woocommerce_shipping_zone_locations SET location_code=%s WHERE location_id=%d",
					$new_location_code,
					$location_data['location_id']
				);
				$wpdb->query( $update_query );
			}
		}

		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Migrate state codes for orders in the orders table.
	 *
	 * @param string $country_code The country that has the states for which the migration is needed.
	 * @param array  $old_to_new_states_mapping An associative array where keys are the old state codes and values are the new state codes.
	 * @return void
	 */
	private static function migrate_country_states_for_cot_orders( string $country_code, array $old_to_new_states_mapping ): void {
		global $wpdb;

		$current_date_gmt          = current_time( 'Y-m-d H:i:s', true );
		$states_as_comma_separated = "('" . join( "','", array_keys( $old_to_new_states_mapping ) ) . "')";

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$sql            = $wpdb->prepare(
			"SELECT id, order_id, state FROM {$wpdb->prefix}wc_order_addresses WHERE country=%s AND state IN {$states_as_comma_separated}",
			$country_code
		);
		$addresses_data = $wpdb->get_results( $sql, ARRAY_A );

		foreach ( $addresses_data as $address_data ) {
			$update_query = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_order_addresses SET state=%s WHERE id=%d",
				$old_to_new_states_mapping[ $address_data['state'] ],
				$address_data['id']
			);
			$wpdb->query( $update_query );

			$update_query = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_orders SET date_updated_gmt=%s WHERE id=%d",
				$current_date_gmt,
				$address_data['order_id']
			);
			$wpdb->query( $update_query );
		}

		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Migrate state codes for orders in the posts table.
	 *
	 * @param string $country_code The country that has the states for which the migration is needed.
	 * @param array  $old_to_new_states_mapping An associative array where keys are the old state codes and values are the new state codes.
	 * @return void
	 */
	private static function migrate_country_states_for_post_orders( string $country_code, array $old_to_new_states_mapping ): void {
		global $wpdb;

		$current_date              = current_time( 'Y-m-d H:i:s', false );
		$current_date_gmt          = current_time( 'Y-m-d H:i:s', true );
		$states_as_comma_separated = "('" . join( "','", array_keys( $old_to_new_states_mapping ) ) . "')";

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT meta_id, post_id, meta_value FROM {$wpdb->prefix}postmeta
				WHERE (meta_key='_billing_state' OR meta_key='_shipping_state')
				AND meta_value IN {$states_as_comma_separated}
				AND post_id IN (
					SELECT post_id FROM {$wpdb->prefix}postmeta WHERE
					(meta_key = '_billing_country' OR meta_key='_shipping_country')
					AND meta_value=%s
				)",
			$country_code
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$addresses_data = $wpdb->get_results( $sql, ARRAY_A );

		foreach ( $addresses_data as $address_data ) {
			$update_query = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}postmeta SET meta_value=%s WHERE meta_id=%d",
				$old_to_new_states_mapping[ $address_data['meta_value'] ],
				$address_data['meta_id']
			);
			$wpdb->query( $update_query );

			$update_query = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}posts SET post_modified=%s, post_modified_gmt=%s WHERE ID=%d",
				$current_date,
				$current_date_gmt,
				$address_data['post_id']
			);
			$wpdb->query( $update_query );
		}
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Migrate the state code for the store location.
	 *
	 * @param string $country_code The country that has the states for which the migration is needed.
	 * @param array  $old_to_new_states_mapping An associative array where keys are the old state codes and values are the new state codes.
	 * @return void
	 */
	private static function migrate_country_states_for_store_location( string $country_code, array $old_to_new_states_mapping ): void {
		$store_location = get_option( 'woocommerce_default_country', '' );
		if ( StringUtil::starts_with( $store_location, "{$country_code}:" ) ) {
			$old_location_code = substr( $store_location, 3 );
			if ( array_key_exists( $old_location_code, $old_to_new_states_mapping ) ) {
				$new_location_code = "{$country_code}:{$old_to_new_states_mapping[$old_location_code]}";
				update_option( 'woocommerce_default_country', $new_location_code );
			}
		}
	}
}
