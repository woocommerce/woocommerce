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
	 * @testdox Missing permalink bases honor catalogs supplied by translation loaders.
	 * @testWith ["pre_load_textdomain"]
	 *           ["override_load_textdomain"]
	 *
	 * @param string $hook Translation loading filter.
	 */
	public function test_permalink_defaults_honor_translation_loaders( string $hook ): void {
		global $l10n, $l10n_unloaded, $wp_textdomain_registry;

		$locale                = 'wc_LOADER';
		$previous_path         = $wp_textdomain_registry->get( 'woocommerce', $locale );
		$previous_translations = $l10n['woocommerce'] ?? null;
		$previous_unloaded     = $l10n_unloaded['woocommerce'] ?? null;
		$previous_locale       = WP_Translation_Controller::get_instance()->get_locale();
		$catalog               = new \MO();
		$catalog->add_entry(
			array(
				'singular'     => 'product',
				'context'      => 'slug',
				'translations' => array( 'loader-product' ),
			)
		);
		$wp_textdomain_registry->set( 'woocommerce', $locale, WC_ABSPATH . 'tests/legacy/unit-tests/util/fixtures/' );
		add_filter( 'pre_option_WPLANG', static fn() => $locale );
		add_filter(
			$hook,
			static function ( $override, $domain ) use ( $catalog ) {
				if ( 'woocommerce' === $domain ) {
					$GLOBALS['l10n'][ $domain ] = $catalog; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate an external translation loader.
					return true;
				}
				return $override;
			},
			10,
			2
		);
		delete_option( 'woocommerce_permalinks' );
		$l10n_unloaded['woocommerce'] = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Model a request that disabled just-in-time loading.

		try {
			wc_get_permalink_structure();
			$this->assertSame( 'loader-product', get_option( 'woocommerce_permalinks' )['product_base'] );
			$this->assertSame( $previous_translations, $l10n['woocommerce'] ?? null, 'Restore the request catalog.' );
			$this->assertSame( $previous_locale, WP_Translation_Controller::get_instance()->get_locale() );
			$this->assertTrue( $l10n_unloaded['woocommerce'] ?? false, 'Preserve the request decision to disable just-in-time loading.' );
		} finally {
			$wp_textdomain_registry->set( 'woocommerce', $locale, $previous_path );
			if ( null === $previous_unloaded ) {
				unset( $l10n_unloaded['woocommerce'] );
			} else {
				$l10n_unloaded['woocommerce'] = $previous_unloaded; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the test's original marker.
			}
		}
	}

	/**
	 * @testdox Missing permalink bases keep slugs customized through gettext filters.
	 * @testWith ["en_US"]
	 *           ["fr_FR"]
	 *
	 * @param string $request_locale Locale of the request that fills in the missing bases.
	 */
	public function test_missing_permalink_bases_keep_gettext_filtered_slugs( string $request_locale ): void {
		$slugs = array(
			'product-category' => 'waren-kategorie',
			'product-tag'      => 'waren-schlagwort',
		);
		add_filter( 'pre_determine_locale', static fn() => $request_locale );
		add_filter(
			'gettext_with_context',
			static fn( $translation, $text, $context, $domain ) => 'woocommerce' === $domain && 'slug' === $context ? ( $slugs[ $text ] ?? $translation ) : $translation,
			10,
			4
		);
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'  => 'waren',
				'category_base' => '',
				'tag_base'      => '',
			)
		);

		wc_get_permalink_structure();
		$permalinks = get_option( 'woocommerce_permalinks' );

		$this->assertSame( 'waren', $permalinks['product_base'], 'A stored base should be left alone.' );
		$this->assertSame( 'waren-kategorie', $permalinks['category_base'], 'The filtered category slug should be stored.' );
		$this->assertSame( 'waren-schlagwort', $permalinks['tag_base'], 'The filtered tag slug should be stored.' );
	}

	/**
	 * @testdox A translation loader that reads the permalink settings does not re-enter the slug lookup.
	 */
	public function test_translate_slug_does_not_re_enter_from_a_translation_loader(): void {
		global $wp_textdomain_registry;

		$loader_calls  = 0;
		$previous_path = $wp_textdomain_registry->get( 'woocommerce', 'wc_LOADER' );
		$wp_textdomain_registry->set( 'woocommerce', 'wc_LOADER', WC_ABSPATH . 'tests/legacy/unit-tests/util/fixtures/' );
		add_filter( 'pre_option_WPLANG', static fn() => 'wc_LOADER' );
		add_filter( 'pre_determine_locale', static fn() => 'en_US' );
		add_filter(
			'pre_load_textdomain',
			static function ( $override, $domain ) use ( &$loader_calls ) {
				if ( 'woocommerce' === $domain ) {
					++$loader_calls;
					if ( $loader_calls > 10 ) {
						throw new \RuntimeException( 'The translation loader re-entered the slug lookup.' );
					}
					wc_get_permalink_structure();
				}
				return $override;
			},
			10,
			2
		);
		delete_option( 'woocommerce_permalinks' );

		try {
			$permalinks = wc_get_permalink_structure();
		} finally {
			$wp_textdomain_registry->set( 'woocommerce', 'wc_LOADER', $previous_path );
		}

		$this->assertSame( 'product', $permalinks['product_base'], 'The lookup should finish with the untranslated slug.' );
		$this->assertLessThanOrEqual( 3, $loader_calls, 'Each missing base should reach the loader once at most.' );
	}

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
		$wp_textdomain_registry->set( 'woocommerce', $locale, WC_ABSPATH . 'tests/legacy/unit-tests/util/fixtures/' );
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
		global $wp_textdomain_registry;

		$site_locale                = 'wc_TEST';
		$fixtures_dir               = WC_ABSPATH . 'tests/legacy/unit-tests/util/fixtures/';
		$translation_controller     = WP_Translation_Controller::get_instance();
		$original_controller_locale = $translation_controller->get_locale();
		$original_translation_path  = $wp_textdomain_registry->get( 'woocommerce', $site_locale );

		add_filter( 'pre_option_WPLANG', static fn() => $site_locale );
		add_filter( 'pre_determine_locale', static fn() => 'en_US' );
		add_filter(
			'lang_dir_for_domain',
			static fn( $path, $domain, $locale ) => 'woocommerce' === $domain && $site_locale === $locale ? $fixtures_dir : $path,
			10,
			3
		);
		add_filter(
			'load_translation_file',
			static fn( $file, $domain, $locale ) => 'woocommerce' === $domain && $site_locale === $locale ? $fixtures_dir . 'permalink-translations.php' : $file,
			10,
			3
		);
		add_filter(
			'gettext_with_context',
			static fn( $translation, $text, $context, $domain ) => 'woocommerce' === $domain && 'slug' === $context && 'product' === $text ? $translation . '-' . determine_locale() : $translation,
			10,
			4
		);

		try {
			$product_slug     = SiteLocale::translate_slug( 'product' );
			$product_tag_slug = SiteLocale::translate_slug( 'product-tag' );
		} finally {
			$translation_controller->unload_textdomain( 'woocommerce', $site_locale );
			$translation_controller->set_locale( $original_controller_locale );
			$wp_textdomain_registry->set( 'woocommerce', $site_locale, $original_translation_path );
		}

		$this->assertSame( 'site-product-wc_TEST', $product_slug, 'A gettext filter should receive the site locale translation and see the site locale.' );
		$this->assertSame( 'product-tag', $product_tag_slug, 'An untranslated slug should be returned unchanged.' );
		$this->assertSame( $original_controller_locale, $translation_controller->get_locale(), 'The translation controller locale should be restored.' );
		$this->assertSame( 'en_US', determine_locale(), 'The request locale should stay unchanged.' );
	}
}
