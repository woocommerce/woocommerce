<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Address;

/**
 * Detects operational settings that still use Nepal's legacy state codes.
 */
final class NepalStateCodeRemediation {

	private const COUNTRY_CODE = 'NP';

	/**
	 * Get legacy Nepal configuration that requires merchant review.
	 *
	 * The result is evaluated dynamically by Site Health so newly imported legacy
	 * configuration is detected and resolved configuration clears automatically.
	 *
	 * @return array<string, bool> Detected configuration, or an empty array when clean.
	 *
	 * @since 11.1.0
	 */
	public function get_status(): array {
		global $wpdb;

		$legacy_state_codes = array_keys( LegacyStateCodes::get_known_states( self::COUNTRY_CODE ) );
		$status             = array();

		if ( empty( $legacy_state_codes ) ) {
			return $status;
		}

		$store_location = explode( ':', (string) get_option( 'woocommerce_default_country', '' ), 2 );

		if ( self::COUNTRY_CODE === ( $store_location[0] ?? '' ) && in_array( $store_location[1] ?? '', $legacy_state_codes, true ) ) {
			$status['store_location'] = true;
		}

		$legacy_shipping_codes = array_map(
			static fn( string $state_code ): string => self::COUNTRY_CODE . ':' . $state_code,
			$legacy_state_codes
		);
		$shipping_placeholders = implode( ', ', array_fill( 0, count( $legacy_shipping_codes ), '%s' ) );
		$shipping_query        = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- The guarded placeholder list is generated immediately above.
			"SELECT location_id FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE location_type = 'state' AND location_code IN ({$shipping_placeholders}) LIMIT 1",
			$legacy_shipping_codes
		);
		$shipping_query_result = $this->run_detection_query( $shipping_query );

		if ( $shipping_query_result['database_error'] ) {
			return array( 'database_error' => true );
		}

		if ( $shipping_query_result['value'] ) {
			$status['shipping_zones'] = true;
		}

		$tax_placeholders = implode( ', ', array_fill( 0, count( $legacy_state_codes ), '%s' ) );
		$tax_query        = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The guarded placeholder list is generated immediately above.
			"SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s AND tax_rate_state IN ({$tax_placeholders}) LIMIT 1",
			array_merge( array( self::COUNTRY_CODE ), $legacy_state_codes )
		);
		$tax_query_result = $this->run_detection_query( $tax_query );

		if ( $tax_query_result['database_error'] ) {
			return array( 'database_error' => true );
		}

		if ( $tax_query_result['value'] ) {
			$status['tax_rates'] = true;
		}

		return $status;
	}

	/**
	 * Run a prepared detection query without printing database errors into Site Health.
	 *
	 * @param string $query Prepared SQL query.
	 * @return array{value: mixed, database_error: bool} Query result and error status.
	 */
	private function run_detection_query( string $query ): array {
		global $wpdb;

		$previous_suppress_errors = $wpdb->suppress_errors();

		try {
			$value = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The caller prepares the query immediately before invoking this method.

			return array(
				'value'          => $value,
				'database_error' => '' !== $wpdb->last_error,
			);
		} finally {
			$wpdb->suppress_errors( $previous_suppress_errors );
		}
	}
}
