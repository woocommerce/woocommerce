<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Address;

/**
 * Detects operational settings that still use legacy state codes.
 */
final class LegacyStateCodeRemediation {

	/**
	 * Get legacy state configuration that requires merchant review.
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

		$status = array();

		if ( null !== LegacyStateCodes::get_known_legacy_location_name( (string) get_option( 'woocommerce_default_country', '' ) ) ) {
			$status['store_location'] = true;
		}

		foreach ( LegacyStateCodes::get_countries_with_known_states() as $country_code ) {
			$legacy_state_codes = array_keys( LegacyStateCodes::get_known_states( $country_code ) );

			if ( empty( $legacy_state_codes ) ) {
				continue;
			}

			$legacy_shipping_codes = array_map(
				static fn( string $state_code ): string => $country_code . ':' . $state_code,
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
					array_merge( array( $country_code ), $legacy_state_codes )
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
