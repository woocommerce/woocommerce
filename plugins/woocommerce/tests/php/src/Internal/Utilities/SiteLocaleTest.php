<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\SiteLocale;
use WC_Unit_Test_Case;

/**
 * Tests for the SiteLocale utility.
 */
class SiteLocaleTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should resolve the site locale from the WPLANG option.
	 */
	public function test_get_resolves_the_wplang_option(): void {
		// sanitize_option() rejects languages without an installed pack, and the test env has none.
		$allow_french = static fn( array $languages ): array => array_merge( $languages, array( 'fr_FR' ) );

		add_filter( 'get_available_languages', $allow_french );
		try {
			update_option( 'WPLANG', 'fr_FR' );

			$this->assertSame( 'fr_FR', SiteLocale::get() );
		} finally {
			remove_filter( 'get_available_languages', $allow_french );
		}
	}

	/**
	 * @testdox Should fall back to en_US when no site language is configured.
	 */
	public function test_get_falls_back_to_en_us(): void {
		delete_option( 'WPLANG' );

		$this->assertSame( 'en_US', SiteLocale::get() );
	}

	/**
	 * @testdox Should not be affected by the `locale` filter.
	 */
	public function test_get_ignores_the_locale_filter(): void {
		$filter_locale = static fn(): string => 'de_DE';

		add_filter( 'locale', $filter_locale, 5 );
		try {
			$this->assertSame( 'en_US', SiteLocale::get(), 'Request-scoped locale filters must not change the resolved site locale.' );
		} finally {
			remove_filter( 'locale', $filter_locale, 5 );
		}
	}

	/**
	 * @testdox Should run the callback under the site locale and restore the request locale.
	 */
	public function test_run_executes_under_site_locale_and_restores(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );
		set_current_screen( 'options-permalink' );

		$this->assertSame( 'fr_FR', determine_locale(), 'The admin request should use the user locale before running.' );

		$locale_inside = SiteLocale::run( 'determine_locale' );

		$this->assertSame( 'en_US', $locale_inside, 'The callback should observe the site locale.' );
		$this->assertSame( 'fr_FR', determine_locale(), 'The request locale should be restored afterwards.' );
	}

	/**
	 * @testdox Should return the callback result unchanged when no locale switch is needed.
	 */
	public function test_run_passes_through_without_a_switch(): void {
		$this->assertSame( 'unchanged', SiteLocale::run( static fn(): string => 'unchanged' ) );
	}

	/**
	 * An enclosing wc_switch_to_site_locale() window registers `plugin_locale` → `get_locale`
	 * and expects wc_restore_locale() to remove it. A nested run() must not strip that
	 * registration when it tears down its own locale switch.
	 *
	 * @testdox Should preserve a pre-existing plugin_locale filter registration across a nested run.
	 */
	public function test_run_preserves_an_outer_plugin_locale_filter(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );
		set_current_screen( 'options-permalink' );

		add_filter( 'plugin_locale', 'get_locale' );
		try {
			SiteLocale::run( static fn() => null );

			$this->assertNotFalse( has_filter( 'plugin_locale', 'get_locale' ), 'The outer plugin_locale registration must survive a nested run.' );
		} finally {
			remove_filter( 'plugin_locale', 'get_locale' );
		}
	}

	/**
	 * @testdox Should remove the plugin_locale filter it added once the run finishes.
	 */
	public function test_run_removes_the_plugin_locale_filter_it_added(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );
		set_current_screen( 'options-permalink' );

		$this->assertFalse( has_filter( 'plugin_locale', 'get_locale' ), 'The filter should be absent before running.' );

		SiteLocale::run( static fn() => null );

		$this->assertFalse( has_filter( 'plugin_locale', 'get_locale' ), 'The filter added during the run should be removed afterwards.' );
	}

	/**
	 * @testdox Should restore the request locale when the callback throws.
	 */
	public function test_run_restores_the_locale_when_the_callback_throws(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );
		set_current_screen( 'options-permalink' );

		try {
			SiteLocale::run(
				static function (): void {
					throw new \RuntimeException( 'boom' );
				}
			);
			$this->fail( 'The callback exception should propagate.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'boom', $e->getMessage() );
		}

		$this->assertSame( 'fr_FR', determine_locale(), 'The request locale should be restored even when the callback throws.' );
		$this->assertFalse( has_filter( 'plugin_locale', 'get_locale' ), 'No plugin_locale registration should be left behind.' );
	}
}
