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

		$store_location = wc_format_country_state_string( (string) get_option( 'woocommerce_default_country', '' ) );

		if ( self::COUNTRY_CODE === $store_location['country'] && in_array( $store_location['state'], $legacy_state_codes, true ) ) {
			$status['store_location'] = true;
		}

		$legacy_shipping_codes = array_map(
			static fn( string $state_code ): string => self::COUNTRY_CODE . ':' . $state_code,
			$legacy_state_codes
		);
		$placeholders          = implode( ', ', array_fill( 0, count( $legacy_state_codes ), '%s' ) );

		$detection_queries = array(
			'shipping_zones' => $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- The guarded placeholder list is generated immediately above.
				"SELECT location_id FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE location_type = 'state' AND location_code IN ({$placeholders}) LIMIT 1",
				$legacy_shipping_codes
			),
			'tax_rates'      => $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The guarded placeholder list is generated immediately above.
				"SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s AND tax_rate_state IN ({$placeholders}) LIMIT 1",
				array_merge( array( self::COUNTRY_CODE ), $legacy_state_codes )
			),
		);

		foreach ( $detection_queries as $status_key => $query ) {
			$query_result = $this->run_detection_query( $query );

			if ( $query_result['database_error'] ) {
				return array( 'database_error' => true );
			}

			if ( $query_result['value'] ) {
				$status[ $status_key ] = true;
			}
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
