<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use WP_Textdomain_Registry;
use WP_Translation_Controller;

/**
 * Resolves the configured site locale and translates WooCommerce slugs into it.
 *
 * Values persisted site-wide, or compared against persisted values, must not depend on the
 * request locale: a temporary locale switch, an administrator's profile language, or a
 * multilingual plugin's `locale` filter would otherwise decide what gets stored.
 *
 * @since 11.2.0
 */
class SiteLocale {

	/**
	 * Get the site locale from stored settings, bypassing the request cache and the `locale` filter.
	 *
	 * Mirrors the stored-setting chain that get_locale() reads underneath.
	 *
	 * @since 11.2.0
	 *
	 * @return string Site locale, for example en_US.
	 */
	public static function get(): string {
		if ( is_multisite() && wp_installing() ) {
			$site_locale = get_site_option( 'WPLANG' );
		} else {
			$site_locale = get_option( 'WPLANG' );

			if ( false === $site_locale && is_multisite() ) {
				$site_locale = get_site_option( 'WPLANG' );
			}
		}

		if ( false === $site_locale ) {
			$site_locale = defined( 'WPLANG' ) ? WPLANG : ( $GLOBALS['wp_local_package'] ?? '' );
		}

		$site_locale = is_string( $site_locale ) ? sanitize_locale_name( $site_locale ) : '';

		return '' !== $site_locale ? $site_locale : 'en_US';
	}

	/**
	 * Translate a WooCommerce slug into the site locale, bypassing gettext filters.
	 *
	 * @since 11.2.0
	 *
	 * @param string $slug Untranslated slug, translated with the `slug` context.
	 * @return string Translated slug, or the untranslated one when no translation exists.
	 */
	public static function translate_slug( string $slug ): string {
		$site_locale = self::get();

		self::load_translations( $site_locale );

		$translated_slug = WP_Translation_Controller::get_instance()->translate( $slug, 'slug', 'woocommerce', $site_locale );

		return is_string( $translated_slug ) && '' !== $translated_slug ? $translated_slug : $slug;
	}

	/**
	 * Load the WooCommerce translations for a locale without touching the request's translations.
	 *
	 * The load_textdomain() call also registers what it loads under the legacy $l10n global, so that global
	 * is isolated while loading and restored afterwards, along with the controller's locale.
	 *
	 * @param string $locale Locale to load.
	 */
	private static function load_translations( string $locale ): void {
		global $l10n, $wp_textdomain_registry;

		$translation_controller = WP_Translation_Controller::get_instance();

		if ( $translation_controller->is_textdomain_loaded( 'woocommerce', $locale ) || ! $wp_textdomain_registry instanceof WP_Textdomain_Registry ) {
			return;
		}

		$translation_path           = $wp_textdomain_registry->get( 'woocommerce', $locale );
		$custom_translation_file    = WP_LANG_DIR . '/woocommerce/woocommerce-' . $locale . '.mo';
		$previous_controller_locale = $translation_controller->get_locale();
		$had_previous_translations  = isset( $l10n['woocommerce'] );
		$previous_translations      = $l10n['woocommerce'] ?? null;

		unset( $l10n['woocommerce'] );

		try {
			if ( is_readable( $custom_translation_file ) ) {
				load_textdomain( 'woocommerce', $custom_translation_file, $locale );
			}

			if ( is_string( $translation_path ) && '' !== $translation_path ) {
				load_textdomain( 'woocommerce', trailingslashit( $translation_path ) . 'woocommerce-' . $locale . '.mo', $locale );
			}
		} finally {
			$translation_controller->set_locale( $previous_controller_locale );

			if ( $had_previous_translations ) {
				$l10n['woocommerce'] = $previous_translations; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the translations isolated above.
			} else {
				unset( $l10n['woocommerce'] );
			}
		}
	}
}
