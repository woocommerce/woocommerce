<?php
/**
 * Test the API controller class that handles the marketing campaigns REST response.
 *
 * @package WooCommerce\Admin\Tests\Admin\Features\OnboardingTasks\Tasks
 */

namespace Automattic\WooCommerce\Tests\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\ExperimentalShippingRecommendation;
use WC_Unit_Test_Case;

/**
 * ExperimentalShippingRecommendation test.
 *
 * @class ExperimentalShippingRecommendationTest.
 */
class ExperimentalShippingRecommendationTest extends WC_Unit_Test_Case {

	/**
	 * @var int
	 */
	const HOOK_PRIORITY = 9999;

	/**
	 * @var string[]
	 */
	private $enabled_admin_features_mock;

	/**
	 * @var string[]
	 */
	private $active_plugin_basenames_mock;

	/**
	 * Set things up before each test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'woocommerce_admin_features', array( $this, 'get_mocked_admin_features' ), self::HOOK_PRIORITY );
		add_filter( 'pre_option_active_plugins', array( $this, 'get_mocked_active_plugins' ), self::HOOK_PRIORITY );
		$this->enabled_admin_features_mock  = array();
		$this->active_plugin_basenames_mock = array();
	}

	/**
	 * Clean up after each test case.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		remove_filter( 'woocommerce_admin_features', array( $this, 'get_mocked_admin_features' ), self::HOOK_PRIORITY );
		remove_filter( 'pre_option_active_plugins', array( $this, 'get_mocked_active_plugins' ), self::HOOK_PRIORITY );
		wp_cache_delete( 'plugins', 'plugins' );
	}

	/**
	 * Tests the "happy path".
	 *
	 * @return void
	 */
	public function test_can_view_returns_true() {
		$this->enabled_admin_features_mock = array( 'shipping-smart-defaults' );
		$this->mock_active_plugin_basenames( array() );

		$this->assertTrue( ( new ExperimentalShippingRecommendation() )->can_view() );
	}

	/**
	 * Tests that the task is unavailable if smart shipping defaults are disabled.
	 *
	 * @return void
	 */
	public function test_can_view_returns_false_if_smart_defaults_are_disabled() {
		$this->enabled_admin_features_mock = array();
		$this->mock_active_plugin_basenames( array() );

		$this->assertFalse( ( new ExperimentalShippingRecommendation() )->can_view() );
	}

	/**
	 * Tests that if the WooCommerce Shipping extension is enabled, the task is unavailable.
	 *
	 * @return void
	 */
	public function test_can_view_returns_false_if_woocommerce_shipping_is_active() {
		$this->enabled_admin_features_mock = array( 'shipping-smart-defaults' );
		$this->mock_active_plugin_basenames(
			array(
				'woocommerce-shipping/woocommerce-shipping.php',
			)
		);

		$this->assertFalse( ( new ExperimentalShippingRecommendation() )->can_view() );
	}

	/**
	 * Tests that if the WooCommerce Tax extension is enabled, the task is unavailable.
	 *
	 * @return void
	 */
	public function test_can_view_returns_false_if_woocommerce_tax_is_active() {
		$this->enabled_admin_features_mock = array( 'shipping-smart-defaults' );
		$this->mock_active_plugin_basenames(
			array(
				'woocommerce-tax/woocommerce-tax.php',
			)
		);

		$this->assertFalse( ( new ExperimentalShippingRecommendation() )->can_view() );
	}

	/**
	 * Tests that if the WooCommerce Shipping and WooCommerce Tax extensions are enabled, the task is unavailable.
	 *
	 * @return void
	 */
	public function test_can_view_returns_false_if_multiple_conflicting_plugins_are_active() {
		$this->enabled_admin_features_mock = array( 'shipping-smart-defaults' );
		$this->mock_active_plugin_basenames(
			array(
				'woocommerce-shipping/woocommerce-shipping.php',
				'woocommerce-tax/woocommerce-tax.php',
			)
		);

		$this->assertFalse( ( new ExperimentalShippingRecommendation() )->can_view() );
	}

	/**
	 * Mocks the list of basenames of active plugins.
	 *
	 * A basename is a reference to the plugin formatted as "foo/foo.php".
	 *
	 * @param string[] $plugin_basenames Array of plugin basenames, e.g. [ "foo/foo.php", "bar/bar.php" ].
	 *
	 * @return void
	 */
	public function mock_active_plugin_basenames( $plugin_basenames ) {
		// Overwrite `get_option()` results.
		$this->active_plugin_basenames_mock = $plugin_basenames;

		// Overwrite `get_plugins()` results (used by a nested function of PluginsHelper::is_plugin_active()`).
		wp_cache_set( 'plugins', array( '' => array_flip( $plugin_basenames ) ), 'plugins' );
	}

	/**
	 * Returns a mocked list of enabled WC Admin features.
	 *
	 * @see \wc_admin_get_feature_config
	 *
	 * @return string[]
	 */
	public function get_mocked_admin_features() {
		return $this->enabled_admin_features_mock;
	}

	/**
	 * Returns a mocked list of active plugin basenames.
	 *
	 * @see self::mock_active_plugin_basenames
	 *
	 * @return string[]
	 */
	public function get_mocked_active_plugins() {
		return $this->active_plugin_basenames_mock;
	}
}
