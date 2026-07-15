<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Database\Migrations;

use Automattic\WooCommerce\Internal\Address\LegacyStateCodes;

/**
 * Handles upgrade compatibility for Nepal's legacy state codes.
 */
final class NepalStateCodeMigration {

	private const COUNTRY_CODE = 'NP';
	private const NOTICE_NAME  = 'nepal_legacy_state_configuration';

	/**
	 * Add a remediation notice when operational settings use legacy Nepal zones.
	 *
	 * @since 11.1.0
	 */
	public function run(): void {
		if ( ! $this->has_legacy_state_configuration() ) {
			return;
		}

		if ( ! class_exists( '\WC_Admin_Notices' ) ) {
			include_once WC_ABSPATH . 'includes/admin/class-wc-admin-notices.php';
		}

		\WC_Admin_Notices::add_custom_notice(
			self::NOTICE_NAME,
			sprintf(
				/* translators: %s: URL to WooCommerce settings. */
				__( 'Some WooCommerce settings still use Nepal\'s former zones. Review your store address, shipping zones, and tax rates so province-based rules continue to apply. <a href="%s">Review settings</a>', 'woocommerce' ),
				esc_url( admin_url( 'admin.php?page=wc-settings' ) )
			)
		);
	}

	/**
	 * Check whether operational settings contain a legacy Nepal state code.
	 */
	private function has_legacy_state_configuration(): bool {
		global $wpdb;

		$legacy_state_codes = array_keys( LegacyStateCodes::get_states( self::COUNTRY_CODE ) );
		$store_location     = explode( ':', (string) get_option( 'woocommerce_default_country', '' ), 2 );

		if ( self::COUNTRY_CODE === ( $store_location[0] ?? '' ) && in_array( $store_location[1] ?? '', $legacy_state_codes, true ) ) {
			return true;
		}

		$legacy_shipping_codes = array_map(
			static fn( string $state_code ): string => self::COUNTRY_CODE . ':' . $state_code,
			$legacy_state_codes
		);
		$shipping_placeholders = implode( ', ', array_fill( 0, count( $legacy_shipping_codes ), '%s' ) );
		$shipping_query        = $wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE location_code IN ({$shipping_placeholders}) LIMIT 1",
			$legacy_shipping_codes
		);

		if ( $wpdb->get_var( $shipping_query ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above with a dynamic placeholder list.
			return true;
		}

		$tax_placeholders = implode( ', ', array_fill( 0, count( $legacy_state_codes ), '%s' ) );
		$tax_query        = $wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s AND tax_rate_state IN ({$tax_placeholders}) LIMIT 1",
			array_merge( array( self::COUNTRY_CODE ), $legacy_state_codes )
		);

		return (bool) $wpdb->get_var( $tax_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above with a dynamic placeholder list.
	}
}
