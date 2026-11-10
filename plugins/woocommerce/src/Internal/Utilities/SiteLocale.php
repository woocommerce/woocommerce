<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use WP_Translation_Controller;

/**
 * Resolves the configured site locale and translates WooCommerce slugs into it.
 *
 * Persisted slugs must not depend on an administrator's language or a request's locale filter.
 *
 * @internal
 * @since 11.4.0
 */
class SiteLocale {

	/**
	 * Whether a slug translation is in progress.
	 *
	 * @var bool
	 */
	private static bool $is_translating = false;

	/**
	 * Get the site locale from stored settings, bypassing the request cache and the `locale` filter.
	 *
	 * Mirrors the stored-setting chain that get_locale() reads underneath.
	 *
	 * @return string Site locale, for example en_US.
	 *
	 * @since 11.4.0
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
	 * Translate a WooCommerce slug into the site locale.
	 *
	 * Translation loaders and gettext filters run in the site locale without changing request translations.
	 *
	 * @param string $slug Untranslated slug, translated with the `slug` context.
	 * @return string Translated slug, or the untranslated one when no translation exists.
	 *
	 * @since 11.4.0
	 */
	public static function translate_slug( string $slug ): string {
		global $l10n, $l10n_unloaded;

		// A translation loader that reads the permalink settings would otherwise re-enter this lookup without end.
		if ( self::$is_translating ) {
			return $slug;
		}

		self::$is_translating = true;
		try {
			$locale                     = self::get();
			$is_site_locale_request     = determine_locale() === $locale;
			$use_site_locale            = static fn() => $locale;
			$translation_controller     = WP_Translation_Controller::get_instance();
			$previous_controller_locale = $translation_controller->get_locale();
			$previous_translations      = $l10n;
			$previous_unloaded          = $l10n_unloaded;

			try {
				add_filter( 'pre_determine_locale', $use_site_locale, PHP_INT_MAX );
				add_filter( 'locale', $use_site_locale, PHP_INT_MAX );

				$translated_slug = self::load_and_translate_slug( $slug, $locale );

				if ( false === $translated_slug && $is_site_locale_request ) {
					// A loader may have supplied a request catalog without registering a translation file.
					$translated_slug = get_translations_for_domain( 'woocommerce' )->translate( $slug, 'slug' );
				}

				$translated_slug = is_string( $translated_slug ) && '' !== $translated_slug ? $translated_slug : $slug;

				// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment -- Both filters are documented in wp-includes/l10n.php.
				$filtered_slug = apply_filters( 'gettext_with_context', $translated_slug, $slug, 'slug', 'woocommerce' );
				$filtered_slug = apply_filters( 'gettext_with_context_woocommerce', $filtered_slug, $slug, 'slug', 'woocommerce' );
				// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment
			} finally {
				remove_filter( 'pre_determine_locale', $use_site_locale, PHP_INT_MAX );
				remove_filter( 'locale', $use_site_locale, PHP_INT_MAX );
				$translation_controller->set_locale( $previous_controller_locale );
				// A callback can load other text domains while the site locale is active.
				$l10n          = $previous_translations; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore all request catalogs, including domains loaded by callbacks.
				$l10n_unloaded = $previous_unloaded; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the request's just-in-time loading state.
			}
		} finally {
			self::$is_translating = false;
		}

		return is_string( $filtered_slug ) && '' !== $filtered_slug ? $filtered_slug : $translated_slug;
	}

	/**
	 * Load site-locale translations without merging the request's translation catalog.
	 *
	 * The load_textdomain() call otherwise imports an existing legacy MO file into the target locale.
	 *
	 * @param string $slug   Untranslated slug.
	 * @param string $locale Locale to load.
	 * @return string|false Translated slug, or false when no translation exists.
	 */
	private static function load_and_translate_slug( string $slug, string $locale ) {
		global $l10n, $wp_textdomain_registry;

		$translation_controller = WP_Translation_Controller::get_instance();

		if ( $translation_controller->is_textdomain_loaded( 'woocommerce', $locale ) ) {
			return $translation_controller->translate( $slug, 'slug', 'woocommerce', $locale );
		}

		$translation_path        = $wp_textdomain_registry->get( 'woocommerce', $locale );
		$custom_translation_file = WP_LANG_DIR . '/woocommerce/woocommerce-' . $locale . '.mo';
		$previous_translations   = $l10n['woocommerce'] ?? null;

		unset( $l10n['woocommerce'] );

		try {
			if ( is_readable( $custom_translation_file ) ) {
				load_textdomain( 'woocommerce', $custom_translation_file, $locale );
			}

			if ( is_string( $translation_path ) && '' !== $translation_path ) {
				// Like core, a language directory inside the active theme holds files named after the locale only.
				$translation_path = trailingslashit( $translation_path );
				$is_theme_path    = str_starts_with( $translation_path, trailingslashit( get_template_directory() ) ) || str_starts_with( $translation_path, trailingslashit( get_stylesheet_directory() ) );
				load_textdomain( 'woocommerce', $translation_path . ( $is_theme_path ? '' : 'woocommerce-' ) . $locale . '.mo', $locale );
			}

			$translated_slug = $translation_controller->translate( $slug, 'slug', 'woocommerce', $locale );

			// Override loaders can supply a legacy catalog without populating the translation controller.
			return false === $translated_slug && isset( $l10n['woocommerce'] ) ? $l10n['woocommerce']->translate( $slug, 'slug' ) : $translated_slug;
		} finally {
			if ( null !== $previous_translations ) {
				$l10n['woocommerce'] = $previous_translations; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the translations isolated above.
			} else {
				unset( $l10n['woocommerce'] );
			}
		}
	}
}
