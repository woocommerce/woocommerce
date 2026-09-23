<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Settings;
use WC_REST_Setting_Options_Controller;
use WC_Unit_Test_Case;

/**
 * Tests for the Settings class.
 */
class SettingsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Settings
	 */
	private $sut;

	/**
	 * Filter hooks added during a test, as [ tag, callback ] pairs, removed again in tearDown().
	 *
	 * @var array[]
	 */
	private $added_filters = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = Settings::get_instance();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->added_filters as $added_filter ) {
			remove_filter( $added_filter[0], $added_filter[1] );
		}
		$this->added_filters = array();
		parent::tearDown();
	}

	/**
	 * @testdox Should register the date type setting with a 'date_paid' default, matching the reports data stores.
	 */
	public function test_date_type_setting_has_date_paid_default(): void {
		$sut = new Settings();

		$date_type = $this->find_setting( $sut->add_settings( array() ), 'woocommerce_date_type' );

		$this->assertNotNull( $date_type, 'The woocommerce_date_type setting should be registered in the wc_admin group' );
		$this->assertSame( 'date_paid', $date_type['default'] ?? null, 'The date type default should match the date_paid fallback used by the reports data stores' );
	}

	/**
	 * @testdox Should resolve the date type value to 'date_paid' when the option has never been saved.
	 */
	public function test_date_type_value_falls_back_to_date_paid_when_option_is_unset(): void {
		delete_option( 'woocommerce_date_type' );

		$date_type = $this->find_setting( $this->get_wc_admin_group_settings(), 'woocommerce_date_type' );

		$this->assertNotNull( $date_type, 'The woocommerce_date_type setting should be registered in the wc_admin group' );
		$this->assertSame( 'date_paid', $date_type['value'], 'An unset date type option should resolve to the effective default, date_paid' );
	}

	/**
	 * @testdox Should resolve the date type value to the saved option value when one exists.
	 */
	public function test_date_type_value_reflects_saved_option(): void {
		update_option( 'woocommerce_date_type', 'date_completed' );

		$date_type = $this->find_setting( $this->get_wc_admin_group_settings(), 'woocommerce_date_type' );

		$this->assertNotNull( $date_type, 'The woocommerce_date_type setting should be registered in the wc_admin group' );
		$this->assertSame( 'date_completed', $date_type['value'], 'A saved date type option should be reflected as the setting value' );
	}

	/**
	 * @testdox Should decode HTML entities in the currency separators exposed to the client.
	 */
	public function test_currency_settings_decode_separator_entities(): void {
		update_option( 'woocommerce_price_thousand_sep', '&nbsp;' );
		update_option( 'woocommerce_price_decimal_sep', '&#44;' );

		$currency_settings = Settings::get_currency_settings();

		$this->assertSame( "\u{00A0}", $currency_settings['thousandSeparator'], 'A thousand separator stored as an HTML entity should be decoded to the real character' );
		$this->assertSame( ',', $currency_settings['decimalSeparator'], 'A decimal separator stored as an HTML entity should be decoded to the real character' );
	}

	/**
	 * @testdox Excluded report order statuses default to pending, cancelled, and failed when not filtered.
	 */
	public function test_default_excluded_order_statuses_without_filter(): void {
		$settings = $this->sut->add_settings( array() );
		$setting  = $this->find_setting( $settings, 'woocommerce_excluded_report_order_statuses' );

		$this->assertSame( array( 'pending', 'cancelled', 'failed' ), $setting['default'] );
	}

	/**
	 * @testdox Actionable order statuses default to processing and on-hold when not filtered.
	 */
	public function test_default_actionable_order_statuses_without_filter(): void {
		$settings = $this->sut->add_settings( array() );
		$setting  = $this->find_setting( $settings, 'woocommerce_actionable_order_statuses' );

		$this->assertSame( array( 'processing', 'on-hold' ), $setting['default'] );
	}

	/**
	 * @testdox woocommerce_analytics_settings_default_excluded_order_statuses lets a plugin activate a status by default.
	 */
	public function test_default_excluded_order_statuses_can_be_filtered(): void {
		$this->add_default_status_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses' );

		$settings = $this->sut->add_settings( array() );
		$setting  = $this->find_setting( $settings, 'woocommerce_excluded_report_order_statuses' );

		$this->assertContains( 'refunded', $setting['default'] );
	}

	/**
	 * @testdox woocommerce_analytics_settings_default_actionable_order_statuses lets a plugin activate a status by default.
	 */
	public function test_default_actionable_order_statuses_can_be_filtered(): void {
		$this->add_default_status_filter( 'woocommerce_analytics_settings_default_actionable_order_statuses' );

		$settings = $this->sut->add_settings( array() );
		$setting  = $this->find_setting( $settings, 'woocommerce_actionable_order_statuses' );

		$this->assertContains( 'refunded', $setting['default'] );
	}

	/**
	 * @testdox A default order statuses filter returning an unknown status has that status dropped.
	 */
	public function test_default_order_statuses_filter_drops_unknown_status(): void {
		$this->add_default_status_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses' );

		$settings = $this->sut->add_settings( array() );
		$setting  = $this->find_setting( $settings, 'woocommerce_excluded_report_order_statuses' );

		$this->assertNotContains( 'not-a-real-status', $setting['default'] );
	}

	/**
	 * @testdox A default order statuses filter returning only unknown slugs falls back to the built-in default.
	 */
	public function test_default_order_statuses_filter_all_unknown_falls_back(): void {
		add_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses', array( $this, 'return_only_unknown_statuses' ) );
		$this->added_filters[] = array( 'woocommerce_analytics_settings_default_excluded_order_statuses', array( $this, 'return_only_unknown_statuses' ) );

		$settings = $this->sut->add_settings( array() );
		$setting  = $this->find_setting( $settings, 'woocommerce_excluded_report_order_statuses' );

		$this->assertSame( array( 'pending', 'cancelled', 'failed' ), $setting['default'] );
	}

	/**
	 * Filter callback that returns only unregistered status slugs.
	 *
	 * @param array $statuses Default statuses.
	 * @return array
	 */
	public function return_only_unknown_statuses( $statuses ) {
		unset( $statuses );
		return array( 'not-a-real-status', 'also-fake' );
	}

	/**
	 * @testdox A default order statuses filter returning a non-array falls back to the built-in default.
	 */
	public function test_default_order_statuses_filter_invalid_type_falls_back(): void {
		add_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses', '__return_false' );
		$this->added_filters[] = array( 'woocommerce_analytics_settings_default_excluded_order_statuses', '__return_false' );

		$settings = $this->sut->add_settings( array() );
		$setting  = $this->find_setting( $settings, 'woocommerce_excluded_report_order_statuses' );

		$this->assertSame( array( 'pending', 'cancelled', 'failed' ), $setting['default'] );
	}

	/**
	 * Hook the shared filter callback and track it for removal in tearDown().
	 *
	 * @param string $filter Filter tag to hook.
	 */
	private function add_default_status_filter( string $filter ): void {
		$callback = array( $this, 'add_status_to_defaults' );
		add_filter( $filter, $callback );
		$this->added_filters[] = array( $filter, $callback );
	}

	/**
	 * Filter callback that appends a known status and an unknown status to a default statuses array.
	 *
	 * @param array $statuses Default statuses.
	 * @return array
	 */
	public function add_status_to_defaults( $statuses ) {
		$statuses[] = 'refunded';
		$statuses[] = 'not-a-real-status';
		return $statuses;
	}

	/**
	 * @testdox get_valid_order_statuses_or_default() keeps arrays as-is (dropping non-strings) and falls back only for unusable values.
	 * @dataProvider provider_valid_order_statuses_or_default
	 * @param mixed $value    Value to validate.
	 * @param array $expected Expected result.
	 */
	public function test_get_valid_order_statuses_or_default( $value, array $expected ): void {
		$result = Settings::get_valid_order_statuses_or_default( $value, array( 'processing', 'on-hold' ) );

		$this->assertSame( $expected, $result );
	}

	/**
	 * Data provider for test_get_valid_order_statuses_or_default.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provider_valid_order_statuses_or_default(): array {
		return array(
			'non-empty array passes through'              => array( array( 'refunded' ), array( 'refunded' ) ),
			'non-array value falls back'                  => array( false, array( 'processing', 'on-hold' ) ),
			'empty array passes through'                  => array( array(), array() ),
			'nested array elements are dropped'           => array( array( 'refunded', array( 'nested' ) ), array( 'refunded' ) ),
			'non-string elements are dropped'             => array( array( 'refunded', 42, null ), array( 'refunded' ) ),
			'blank string elements are dropped'           => array( array( 'refunded', '' ), array( 'refunded' ) ),
			'slugs are trimmed'                           => array( array( ' refunded ' ), array( 'refunded' ) ),
			'all-blank array falls back as unusable'      => array( array( ' ', '' ), array( 'processing', 'on-hold' ) ),
			'all-non-string array falls back as unusable' => array( array( 42, null ), array( 'processing', 'on-hold' ) ),
		);
	}

	/**
	 * @testdox A default order statuses filter is the source of truth for runtime consumers, not just the settings UI.
	 */
	public function test_default_excluded_order_statuses_accessor_applies_filter(): void {
		$this->add_default_status_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses' );

		$statuses = Settings::get_default_excluded_order_statuses();

		$this->assertContains( 'refunded', $statuses, 'The filtered status should reach runtime consumers.' );
	}

	/**
	 * @testdox The actionable default accessor applies its filter.
	 */
	public function test_default_actionable_order_statuses_accessor_applies_filter(): void {
		$this->add_default_status_filter( 'woocommerce_analytics_settings_default_actionable_order_statuses' );

		$statuses = Settings::get_default_actionable_order_statuses();

		$this->assertContains( 'refunded', $statuses, 'The filtered status should reach runtime consumers.' );
	}

	/**
	 * @testdox A saved empty selection is respected, so unchecking every status does not restore the defaults.
	 */
	public function test_saved_empty_selection_is_respected(): void {
		$result = Settings::get_valid_order_statuses_or_default( array(), array( 'pending', 'cancelled', 'failed' ) );

		$this->assertSame( array(), $result, 'An explicitly saved empty selection must not be replaced by the built-in default.' );
	}

	/**
	 * @testdox A filter returning an empty array keeps the settings UI empty, agreeing with the runtime accessors.
	 */
	public function test_filter_returning_empty_array_keeps_ui_empty(): void {
		$empty_filter = function () {
			return array();
		};
		add_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses', $empty_filter );
		$this->added_filters[] = array( 'woocommerce_analytics_settings_default_excluded_order_statuses', $empty_filter );

		$settings = $this->sut->add_settings( array() );
		$setting  = $this->find_setting( $settings, 'woocommerce_excluded_report_order_statuses' );

		$this->assertSame( array(), $setting['default'], 'An empty filtered default should stay empty in the UI.' );
		$this->assertSame( array(), Settings::get_default_excluded_order_statuses(), 'The UI and runtime consumers should agree.' );
	}

	/**
	 * Get the resolved wc_admin group settings via the REST settings controller.
	 *
	 * @return array
	 */
	private function get_wc_admin_group_settings(): array {
		$settings = ( new WC_REST_Setting_Options_Controller() )->get_group_settings( 'wc_admin' );

		$this->assertIsArray( $settings, 'The wc_admin settings group should resolve to an array of settings' );

		return $settings;
	}

	/**
	 * Find a setting by id in a list of settings.
	 *
	 * @param array  $settings List of setting definitions.
	 * @param string $id       Setting id to look for.
	 * @return array|null The first matching setting, or null if none matches.
	 */
	private function find_setting( array $settings, string $id ): ?array {
		foreach ( $settings as $setting ) {
			if ( ( $setting['id'] ?? '' ) === $id ) {
				return $setting;
			}
		}
		return null;
	}
}
