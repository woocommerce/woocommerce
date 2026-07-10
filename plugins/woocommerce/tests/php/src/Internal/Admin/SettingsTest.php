<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Settings;
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
	 * Find a registered setting by its id.
	 *
	 * @param array  $settings Settings array returned from add_settings().
	 * @param string $id       Setting id to find.
	 * @return array|null
	 */
	private function find_setting( array $settings, string $id ) {
		foreach ( $settings as $setting ) {
			if ( $setting['id'] === $id ) {
				return $setting;
			}
		}
		return null;
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
	 * @testdox get_valid_order_statuses_or_default() only falls back for non-array or empty values.
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
			'non-empty array passes through' => array( array( 'refunded' ), array( 'refunded' ) ),
			'non-array value falls back'     => array( false, array( 'processing', 'on-hold' ) ),
			'empty array falls back'         => array( array(), array( 'processing', 'on-hold' ) ),
		);
	}
}
