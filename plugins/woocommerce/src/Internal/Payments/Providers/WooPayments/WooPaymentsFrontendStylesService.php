<?php
/**
 * WooPaymentsFrontendStylesService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * Shared frontend styling helpers for native WooPayments checkout surfaces.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsFrontendStylesService {

	private const STYLES_CACHE_VERSION_OPTION = 'wcpay_styles_cache_version';

	private const STYLES_CACHE_SCHEMA_VERSION = 'appearance-extractor-v3';

	/**
	 * Get the current frontend styles cache version.
	 *
	 * This version is shared by card Payment Element styling and WooPay appearance
	 * persistence so both sibling checkout surfaces invalidate cached appearance
	 * data together when the active theme or WooCommerce version changes.
	 *
	 * @return string
	 */
	public function get_styles_cache_version(): string {
		$version = get_option( self::STYLES_CACHE_VERSION_OPTION );

		if ( is_scalar( $version ) && '' !== (string) $version ) {
			return $this->append_schema_version( sanitize_text_field( (string) $version ) );
		}

		$version = md5( (string) wp_get_theme()->get_stylesheet() . '|' . ( defined( 'WC_VERSION' ) ? WC_VERSION : '' ) );
		update_option( self::STYLES_CACHE_VERSION_OPTION, $version, true );

		return $this->append_schema_version( $version );
	}

	/**
	 * Invalidate the frontend styles cache version.
	 */
	public function invalidate_styles_cache_version(): void {
		delete_option( self::STYLES_CACHE_VERSION_OPTION );
	}

	/**
	 * Append the frontend style extractor schema version.
	 *
	 * @param string $version Theme/WooCommerce style cache version.
	 * @return string
	 */
	private function append_schema_version( string $version ): string {
		return $version . '|' . self::STYLES_CACHE_SCHEMA_VERSION;
	}
}
