<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Admin\ReportExporter;
use Automattic\WooCommerce\Internal\Admin\Analytics;
use Automattic\WooCommerce\Internal\Admin\FeaturePlugin;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use WC_Unit_Test_Case;

/**
 * Tests for the FeaturePlugin class.
 */
class FeaturePluginTest extends WC_Unit_Test_Case {
	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( Analytics::TOGGLE_OPTION_NAME );
		remove_filter( 'woocommerce_admin_disabled', '__return_true', PHP_INT_MAX );
		remove_filter( 'woocommerce_admin_features', array( $this, 'disable_analytics_feature' ), PHP_INT_MAX );

		parent::tearDown();
	}

	/**
	 * @testdox The bootstrap analytics gate respects the feature option.
	 * @dataProvider analytics_option_provider
	 *
	 * @param string|null $option_value   Analytics option value, or null when absent.
	 * @param bool        $expected_value Expected gate value.
	 */
	public function test_analytics_gate_respects_feature_option( ?string $option_value, bool $expected_value ): void {
		if ( null === $option_value ) {
			delete_option( Analytics::TOGGLE_OPTION_NAME );
		} else {
			update_option( Analytics::TOGGLE_OPTION_NAME, $option_value );
		}

		$this->assertSame( $expected_value, $this->is_analytics_enabled_during_bootstrap(), 'The bootstrap gate should match the configured Analytics option.' );
	}

	/**
	 * Values for the analytics option test.
	 *
	 * @return array<string, array{string|null, bool}>
	 */
	public function analytics_option_provider(): array {
		return array(
			'option absent'    => array( null, true ),
			'option enabled'   => array( 'yes', true ),
			'option disabled'  => array( 'no', false ),
			'unexpected value' => array( 'invalid', false ),
		);
	}

	/**
	 * @testdox The bootstrap analytics gate agrees with the canonical FeaturesController result.
	 * @dataProvider analytics_option_provider
	 *
	 * The bootstrap gate deliberately hardcodes the analytics option name and its 'yes' default so it can run
	 * before init without building translated feature definitions. This pins that duplication to the canonical
	 * path: if `enabled_by_default` for analytics ever flips upstream, the two would silently disagree on the
	 * "option absent" case and this test fails.
	 *
	 * @param string|null $option_value   Analytics option value, or null when absent.
	 * @param bool        $expected_value Expected gate value.
	 */
	public function test_analytics_gate_agrees_with_features_controller( ?string $option_value, bool $expected_value ): void {
		if ( null === $option_value ) {
			delete_option( Analytics::TOGGLE_OPTION_NAME );
		} else {
			update_option( Analytics::TOGGLE_OPTION_NAME, $option_value );
		}

		$canonical_value = wc_get_container()->get( FeaturesController::class )->feature_is_enabled( 'analytics' );

		$this->assertSame( $expected_value, $canonical_value, 'FeaturesController::feature_is_enabled( "analytics" ) drifted from the documented expectation the bootstrap gate mirrors.' );
		$this->assertSame( $canonical_value, $this->is_analytics_enabled_during_bootstrap(), 'The bootstrap gate must agree with FeaturesController::feature_is_enabled( "analytics" ) under the same option state.' );
	}

	/**
	 * @testdox The bootstrap analytics gate evaluates legacy filters when the option is disabled.
	 */
	public function test_analytics_gate_evaluates_legacy_filters_when_option_disabled(): void {
		update_option( Analytics::TOGGLE_OPTION_NAME, 'no' );
		$filter_call_count = 0;
		$filter            = static function ( $disabled ) use ( &$filter_call_count ) {
			++$filter_call_count;

			return $disabled;
		};
		add_filter( 'woocommerce_admin_disabled', $filter, PHP_INT_MAX );

		try {
			$this->assertFalse( $this->is_analytics_enabled_during_bootstrap(), 'The disabled Analytics option should keep the bootstrap gate disabled.' );
		} finally {
			remove_filter( 'woocommerce_admin_disabled', $filter, PHP_INT_MAX );
		}

		$this->assertSame( 1, $filter_call_count, 'The bootstrap gate should preserve the canonical legacy-filter evaluation order.' );
	}

	/**
	 * @testdox The bootstrap analytics gate respects the legacy WooCommerce Admin disabled filter.
	 */
	public function test_analytics_gate_respects_admin_disabled_filter(): void {
		add_filter( 'woocommerce_admin_disabled', '__return_true', PHP_INT_MAX );

		$this->assertFalse( $this->is_analytics_enabled_during_bootstrap(), 'Disabling WooCommerce Admin should disable the Analytics bootstrap gate.' );
	}

	/**
	 * @testdox The bootstrap analytics gate respects removal from the legacy feature list.
	 */
	public function test_analytics_gate_respects_legacy_feature_filter(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_analytics_feature' ), PHP_INT_MAX );

		$this->assertFalse( $this->is_analytics_enabled_during_bootstrap(), 'Removing Analytics from the legacy feature list should disable its bootstrap gate.' );
	}

	/**
	 * @testdox The bootstrap analytics gate does not translate WooCommerce strings.
	 */
	public function test_analytics_gate_does_not_translate_strings(): void {
		$translation_count = 0;
		$gettext_filter    = static function ( $translation, $text, $domain ) use ( &$translation_count ) {
			if ( 'woocommerce' === $domain ) {
				++$translation_count;
			}

			return $translation;
		};
		add_filter( 'gettext', $gettext_filter, 10, 3 );

		try {
			$this->assertTrue( $this->is_analytics_enabled_during_bootstrap(), 'Analytics should remain enabled without invoking translations.' );
		} finally {
			remove_filter( 'gettext', $gettext_filter, 10 );
		}

		$this->assertSame( 0, $translation_count, 'The bootstrap gate should not translate WooCommerce feature definitions.' );
	}

	/**
	 * @testdox Serving and expiring existing exports stays enabled when Analytics is disabled.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_export_files_are_still_handled_when_analytics_is_disabled(): void {
		$export_hook        = ReportExporter::get_action( 'export_report' );
		$scheduler_hook     = ReportExporter::get_action( 'schedule_action' );
		$scheduler_callback = array( ReportExporter::class, 'do_action_or_reschedule' );
		$download_callback  = array( ReportExporter::class, 'download_export_file' );
		$cleanup_callback   = array( ReportExporter::class, 'delete_expired_exports' );

		$this->assertIsString( $export_hook, 'The export hook should be defined.' );
		$this->assertIsString( $scheduler_hook, 'The scheduler hook should be defined.' );

		remove_action( $export_hook, $scheduler_callback, 10 );
		remove_action( $scheduler_hook, $scheduler_callback, 10 );
		remove_action( 'admin_init', $download_callback, 10 );
		remove_action( 'wc_admin_daily', $cleanup_callback, 10 );
		update_option( Analytics::TOGGLE_OPTION_NAME, 'no' );

		FeaturePlugin::instance()->includes();

		$this->assertSame( 10, has_action( 'admin_init', $download_callback ), 'Links that were already emailed should keep working.' );
		$this->assertSame( 10, has_action( 'wc_admin_daily', $cleanup_callback ), 'Existing exports should still expire.' );
		$this->assertFalse( has_action( $export_hook, $scheduler_callback ), 'Export generation should stay disabled.' );
		$this->assertFalse( has_action( $scheduler_hook, $scheduler_callback ), 'Export scheduling should stay disabled.' );
	}

	/**
	 * Disable Analytics in the legacy feature list.
	 *
	 * @param array $features Feature slugs.
	 * @return array
	 */
	public function disable_analytics_feature( array $features ): array {
		return array_values( array_diff( $features, array( 'analytics' ) ) );
	}

	/**
	 * Invoke the private bootstrap analytics gate.
	 */
	private function is_analytics_enabled_during_bootstrap(): bool {
		$method = new \ReflectionMethod( FeaturePlugin::class, 'is_analytics_enabled_during_bootstrap' );
		$method->setAccessible( true );

		return (bool) $method->invoke( FeaturePlugin::instance() );
	}
}
