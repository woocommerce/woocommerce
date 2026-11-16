<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\SiteLocale;
use WC_Unit_Test_Case;
use WP_Translation_Controller;

/**
 * Tests for the SiteLocale class.
 */
class SiteLocaleTest extends WC_Unit_Test_Case {

	/**
	 * @testdox translate_slug() prefers translations loaded for the site locale over a catalog supplied by a loader.
	 */
	public function test_translate_slug_prefers_site_locale_translations_over_a_loader_catalog(): void {
		global $wp_textdomain_registry;

		$locale        = 'wc_ORDER';
		$custom_file   = WP_LANG_DIR . '/woocommerce/woocommerce-' . $locale . '.mo';
		$previous_path = $wp_textdomain_registry->get( 'woocommerce', $locale );
		$catalogs      = array();
		foreach ( array( 'site-product', 'loader-product' ) as $translation ) {
			$catalogs[ $translation ] = new \MO();
			$catalogs[ $translation ]->add_entry(
				array(
					'singular'     => 'product',
					'context'      => 'slug',
					'translations' => array( $translation ),
				)
			);
		}
		wp_mkdir_p( dirname( $custom_file ) );
		$this->assertTrue( $catalogs['site-product']->export_to_file( $custom_file ), 'The site locale translation file should be written.' );
		$wp_textdomain_registry->set( 'woocommerce', $locale, WC_ABSPATH . 'tests/php/src/Internal/Utilities/fixtures/' );
		add_filter( 'pre_option_WPLANG', static fn() => $locale );
		add_filter( 'pre_determine_locale', static fn() => 'en_US' );
		add_filter(
			'override_load_textdomain',
			static function ( $override, $domain, $mofile ) use ( $catalogs, $custom_file ) {
				if ( 'woocommerce' === $domain && $custom_file !== $mofile ) {
					$GLOBALS['l10n'][ $domain ] = $catalogs['loader-product']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate a loader that ignores the requested locale.
					return true;
				}
				return $override;
			},
			10,
			3
		);

		try {
			$product_slug = SiteLocale::translate_slug( 'product' );
		} finally {
			WP_Translation_Controller::get_instance()->unload_textdomain( 'woocommerce', $locale );
			$wp_textdomain_registry->set( 'woocommerce', $locale, $previous_path );
			wp_delete_file( $custom_file );
		}

		$this->assertSame( 'site-product', $product_slug, 'The translation loaded for the site locale should win.' );
	}

	/**
	 * @testdox translate_slug() looks for a file named after the locale when the language directory is inside the active theme.
	 */
	public function test_translate_slug_uses_the_theme_file_name_for_a_theme_language_directory(): void {
		global $wp_textdomain_registry;

		$locale        = 'wc_THEME';
		$theme_path    = trailingslashit( get_stylesheet_directory() ) . 'languages/';
		$previous_path = $wp_textdomain_registry->get( 'woocommerce', $locale );
		$loaded_files  = array();
		$wp_textdomain_registry->set( 'woocommerce', $locale, $theme_path );
		add_filter( 'pre_option_WPLANG', static fn() => $locale );
		add_filter( 'pre_determine_locale', static fn() => 'en_US' );
		add_filter(
			'load_textdomain_mofile',
			static function ( $mofile, $domain ) use ( &$loaded_files ) {
				if ( 'woocommerce' === $domain ) {
					$loaded_files[] = $mofile;
				}
				return $mofile;
			},
			10,
			2
		);

		try {
			SiteLocale::translate_slug( 'product' );
		} finally {
			$wp_textdomain_registry->set( 'woocommerce', $locale, $previous_path );
		}

		$this->assertSame( array( $theme_path . 'wc_THEME.mo' ), $loaded_files, 'A theme language directory should use the locale as the file name.' );
	}

	/**
	 * @testdox translate_slug() on a site-locale request prefers registered site locale translations over the catalog already loaded.
	 * @testWith [true, "site-product", "wc_TEST"]
	 *           [false, "loaded-product", "wc_TEST"]
	 *           [false, "product", "en_US"]
	 *
	 * @param bool   $has_site_translations Whether translations are registered for the site locale.
	 * @param string $expected_slug         Expected translated slug.
	 * @param string $request_locale        Locale of the current request.
	 */
	public function test_translate_slug_on_a_site_locale_request( bool $has_site_translations, string $expected_slug, string $request_locale ): void {
		global $l10n, $wp_textdomain_registry;

		$locale                = 'wc_TEST';
		$fixtures_dir          = WC_ABSPATH . 'tests/php/src/Internal/Utilities/fixtures/';
		$previous_path         = $wp_textdomain_registry->get( 'woocommerce', $locale );
		$previous_translations = $l10n['woocommerce'] ?? null;
		$loaded_catalog        = new \MO();
		$loaded_catalog->add_entry(
			array(
				'singular'     => 'product',
				'context'      => 'slug',
				'translations' => array( 'loaded-product' ),
			)
		);
		add_filter( 'pre_option_WPLANG', static fn() => $locale );
		add_filter( 'pre_determine_locale', static fn() => $request_locale );
		if ( $has_site_translations ) {
			$wp_textdomain_registry->set( 'woocommerce', $locale, $fixtures_dir );
			add_filter(
				'load_translation_file',
				static fn( $file, $domain, $file_locale ) => 'woocommerce' === $domain && $locale === $file_locale ? $fixtures_dir . 'permalink-translations.php' : $file,
				10,
				3
			);
		}
		$l10n['woocommerce'] = $loaded_catalog; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate a catalog loaded earlier in the request.

		try {
			$product_slug = SiteLocale::translate_slug( 'product' );
		} finally {
			WP_Translation_Controller::get_instance()->unload_textdomain( 'woocommerce', $locale );
			$wp_textdomain_registry->set( 'woocommerce', $locale, $previous_path );
			if ( null === $previous_translations ) {
				unset( $l10n['woocommerce'] );
			} else {
				$l10n['woocommerce'] = $previous_translations; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the test's original translations.
			}
		}

		$this->assertSame( $expected_slug, $product_slug, 'Registered site locale translations should win, with the loaded catalog as the fallback.' );
	}

	/**
	 * @testdox get() reads the stored site locale and ignores the locale filter.
	 */
	public function test_get_ignores_the_locale_filter(): void {
		add_filter( 'pre_option_WPLANG', static fn() => 'de_DE' );
		add_filter( 'locale', static fn() => 'fr_FR' );

		$this->assertSame( 'fr_FR', get_locale(), 'The request locale should follow the locale filter.' );
		$this->assertSame( 'de_DE', SiteLocale::get(), 'The site locale should come from the stored setting.' );
	}

	/**
	 * @testdox get() falls back to en_US when no site locale is stored.
	 */
	public function test_get_falls_back_to_en_us(): void {
		add_filter( 'pre_option_WPLANG', static fn() => '' );

		$this->assertSame( 'en_US', SiteLocale::get() );
	}

	/**
	 * @testdox translate_slug() uses the site locale translations and passes them through gettext filters in the site locale.
	 */
	public function test_translate_slug_uses_site_locale_translations(): void {
		global $l10n, $wp_textdomain_registry;

		$site_locale                = 'wc_TEST';
		$fixtures_dir               = WC_ABSPATH . 'tests/php/src/Internal/Utilities/fixtures/';
		$translation_controller     = WP_Translation_Controller::get_instance();
		$original_controller_locale = $translation_controller->get_locale();
		$original_translation_path  = $wp_textdomain_registry->get( 'woocommerce', $site_locale );
		$original_nested_path       = $wp_textdomain_registry->get( 'site-locale-test', $site_locale );
		$original_nested_catalog    = $l10n['site-locale-test'] ?? null;

		add_filter( 'pre_option_WPLANG', static fn() => $site_locale );
		add_filter( 'pre_determine_locale', static fn() => 'en_US' );
		add_filter( 'locale', static fn() => 'fr_FR' );
		add_filter(
			'lang_dir_for_domain',
			static fn( $path, $domain, $locale ) => in_array( $domain, array( 'woocommerce', 'site-locale-test' ), true ) && $site_locale === $locale ? $fixtures_dir : $path,
			10,
			3
		);
		add_filter(
			'load_translation_file',
			static fn( $file, $domain, $locale ) => in_array( $domain, array( 'woocommerce', 'site-locale-test' ), true ) && $site_locale === $locale ? $fixtures_dir . 'permalink-translations.php' : $file,
			10,
			3
		);
		add_filter(
			'gettext_with_context',
			static function ( $translation, $text, $context, $domain ) {
				if ( 'woocommerce' === $domain && 'slug' === $context && 'product' === $text ) {
					get_translations_for_domain( 'site-locale-test' )->translate( 'product', 'slug' );
					return $translation . '-' . determine_locale() . '-' . get_locale();
				}
				return $translation;
			},
			10,
			4
		);

		try {
			$product_slug     = SiteLocale::translate_slug( 'product' );
			$product_tag_slug = SiteLocale::translate_slug( 'product-tag' );

			$this->assertSame( 'site-product-wc_TEST-wc_TEST', $product_slug, 'A gettext filter should receive the site locale translation and see the site locale.' );
			$this->assertSame( 'product-tag', $product_tag_slug, 'An untranslated slug should be returned unchanged.' );
			$this->assertSame( $original_controller_locale, $translation_controller->get_locale(), 'The translation controller locale should be restored.' );
			$this->assertSame( $original_nested_catalog, $l10n['site-locale-test'] ?? null, 'A nested translation should not replace the request catalog.' );
			$this->assertSame( 'en_US', determine_locale(), 'The request locale should stay unchanged.' );
		} finally {
			$translation_controller->unload_textdomain( 'woocommerce', $site_locale );
			$translation_controller->unload_textdomain( 'site-locale-test', $site_locale );
			$translation_controller->set_locale( $original_controller_locale );
			$wp_textdomain_registry->set( 'woocommerce', $site_locale, $original_translation_path );
			$wp_textdomain_registry->set( 'site-locale-test', $site_locale, $original_nested_path );
			if ( null === $original_nested_catalog ) {
				unset( $l10n['site-locale-test'] );
			} else {
				$l10n['site-locale-test'] = $original_nested_catalog; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the test's original translations.
			}
		}
	}
}
