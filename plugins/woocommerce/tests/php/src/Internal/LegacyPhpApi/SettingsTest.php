<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\LegacyPhpApi;

use Automattic\WooCommerce\Api\Infrastructure\Main;
use Automattic\WooCommerce\Internal\Api\Settings;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use WC_Unit_Test_Case;

/**
 * Tests for the GraphQL API Settings class on PHP versions where the
 * dual-code GraphQL feature is unavailable (PHP < 8.1).
 *
 * Lives outside `tests/php/src/Internal/Api/` because that directory is
 * excluded from the default testsuite (its dummy fixture API uses PHP 8.1+
 * syntax). These tests must run on PHP 7.4 / 8.0 to verify that
 * {@see Settings::add_section()} and {@see Settings::add_settings()}
 * gracefully degrade to a no-op when {@see Main::is_enabled()} returns false.
 *
 * `Settings.php` and `Main.php` are intentionally PHP 7.4-parseable, and the
 * {@see Main::is_enabled()} short-circuit prevents the methods from ever
 * reaching the lines that reference PHP 8.1+ classes such as `GraphQLController`.
 */
class SettingsTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var Settings
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_or_disable_feature( true );
		$this->sut = new Settings();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		$this->enable_or_disable_feature( false );
		parent::tearDown();
	}

	/**
	 * Toggle the dual_code_graphql_api feature flag via its underlying option.
	 *
	 * @param bool $enable True to enable, false to disable.
	 */
	private function enable_or_disable_feature( bool $enable ): void {
		update_option(
			wc_get_container()->get( FeaturesController::class )->feature_enable_option_name( 'dual_code_graphql_api' ),
			$enable ? 'yes' : 'no'
		);
	}

	/**
	 * @testdox add_section is a no-op on PHP < 8.1.
	 */
	public function test_add_section_is_noop_on_unsupported_php(): void {
		if ( PHP_VERSION_ID >= 80100 ) {
			$this->markTestSkipped( 'Only relevant on PHP < 8.1.' );
		}

		$input  = array( 'features' => 'Features' );
		$result = $this->sut->add_section( $input );

		$this->assertSame( $input, $result );
	}

	/**
	 * @testdox add_settings returns the input unchanged on PHP < 8.1.
	 */
	public function test_add_settings_is_noop_on_unsupported_php(): void {
		if ( PHP_VERSION_ID >= 80100 ) {
			$this->markTestSkipped( 'Only relevant on PHP < 8.1.' );
		}

		$input  = array( array( 'id' => 'existing' ) );
		$result = $this->sut->add_settings( $input, Settings::SECTION_ID );

		$this->assertSame( $input, $result );
	}
}
