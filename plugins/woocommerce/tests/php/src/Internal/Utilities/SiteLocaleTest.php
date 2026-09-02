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
	 * @testdox translate_slug() uses the site locale translations and ignores request-scoped gettext filters.
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
			static fn( $translation, $text, $context, $domain ) => 'woocommerce' === $domain && 'slug' === $context ? 'request-' . $text : $translation,
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

		$this->assertSame( 'site-product', $product_slug, 'A translated slug should come from the site locale translations.' );
		$this->assertSame( 'product-tag', $product_tag_slug, 'An untranslated slug should be returned unchanged.' );
		$this->assertSame( $original_controller_locale, $translation_controller->get_locale(), 'The translation controller locale should be restored.' );
		$this->assertSame( 'en_US', determine_locale(), 'The request locale should stay unchanged.' );
	}
}
