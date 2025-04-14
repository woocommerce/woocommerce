<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\DefaultFreeExtensions;
use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\EvaluateExtension;
use WC_Unit_Test_Case;

/**
 * DefaultFreeExtensions test.
 *
 * @class DefaultFreeExtensionsTest
 */
class DefaultFreeExtensionsTest extends WC_Unit_Test_Case {

	/**
	 * Mock of bundles of extensions to recommend.
	 *
	 * We will test the `is_visible` conditions on the plugins themselves.
	 *
	 * @var array
	 */
	private $bundles_mock;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_default_country', 'US:CA' );

		/*
		 * Required for the BaseLocationCountryRuleProcessor
		 * to not return false for "US:CA" country-state combo.
		 */
		update_option( 'woocommerce_store_address', 'foo' );

		update_option( 'active_plugins', array( 'foo/foo.php' ) );

		$this->bundles_mock = array(
			array(
				'key'     => 'foo',
				'title'   => 'Test bundle',
				'plugins' => array(
					DefaultFreeExtensions::get_plugin( 'woocommerce-shipping' ),
					DefaultFreeExtensions::get_plugin( 'woocommerce-services:tax' ),
				),
			),
			array(
				'key'     => 'obw/core-profiler',
				'title'   => 'Core Profiler Bundle',
				'plugins' => array(
					DefaultFreeExtensions::get_plugin( 'woocommerce-payments' ),
					DefaultFreeExtensions::get_plugin( 'mailpoet' ),
				),
			),
		);
	}

	/**
	 * Tests the default behavior of recommending WCS&T as the tax solution.
	 *
	 * @return void
	 */
	public function test_wcservices_is_recommended_for_tax() {
		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		$this->assertContains( 'woocommerce-services:tax', $recommended_plugin_slugs );
	}

	/**
	 * Tests the default behavior of recommending WC Shipping as the shipping solution.
	 *
	 * @return void
	 */
	public function test_wcshipping_is_recommended_for_shipping() {
		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		$this->assertContains( 'woocommerce-shipping', $recommended_plugin_slugs );
	}

	/**
	 * Asserts WCS&T is not recommended in unsupported countries.
	 *
	 * @return void
	 */
	public function test_wcservices_is_not_recommended_if_in_an_unsupported_country() {
		update_option( 'woocommerce_default_country', 'FOO' );

		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		$this->assertNotContains( 'woocommerce-services:tax', $recommended_plugin_slugs );
	}

	/**
	 * Asserts WC Shipping is not recommended in unsupported countries.
	 *
	 * @return void
	 */
	public function test_wcshipping_is_not_recommended_if_in_an_unsupported_country() {
		update_option( 'woocommerce_default_country', 'FOO' );

		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		$this->assertNotContains( 'woocommerce-shipping', $recommended_plugin_slugs );
	}

	/**
	 * Asserts WCS&T is still recommended if WooCommerce Shipping is active.
	 *
	 * @return void
	 */
	public function test_wcservices_is_recommended_if_woocommerce_shipping_is_active() {
		// Arrange.
		// Make sure the plugin passes as active.
		$shipping_plugin_file = 'woocommerce-shipping/woocommerce-shipping.php';
		// To pass the validation, we need to the plugin file to exist.
		$shipping_plugin_file_path = WP_PLUGIN_DIR . '/' . $shipping_plugin_file;
		self::touch( $shipping_plugin_file_path );
		update_option( 'active_plugins', array( $shipping_plugin_file ) );

		// Act.
		$recommended_plugin_slugs = $this->get_recommended_plugin_slugs( $this->bundles_mock );

		// Assert.
		$this->assertContains( 'woocommerce-services:tax', $recommended_plugin_slugs );

		// Clean up.
		self::rmdir( dirname( $shipping_plugin_file_path ) );
		self::delete_folders( dirname( $shipping_plugin_file_path ) );
	}

	/**
	 * Evaluates bundles passed as argument and extracts keys of recommended plugins.
	 *
	 * @param array $bundles Array of bundles to evaluate.
	 *
	 * @return array
	 */
	private function get_recommended_plugin_slugs( $bundles ) {
		/*
		 * The json_decode( json_encode() ) call is a trick that
		 * DefaultFreeExtensions::get_all uses to convert the entire
		 * associative array into an object.
		 */
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- We're duplicating what the tested class does.
		$bundles = json_decode( json_encode( $bundles ) );
		$results = EvaluateExtension::evaluate_bundles( $bundles );

		return array_map(
			function ( $plugin ) {
				return $plugin->key;
			},
			$results['bundles'][0]['plugins']
		);
	}

	/**
	 * Tests that core profiler bundle is removed when feature flag is enabled and user is in rollout group.
	 */
	public function test_core_profiler_bundle_is_removed_when_feature_enabled_and_in_rollout() {
		// Enable the feature flag.
		$filter = function ( $config ) {
			$config['disable-core-profiler-fallback'] = true;
			return $config;
		};
		add_filter( 'woocommerce_admin_get_feature_config', $filter );

		// Set user in rollout group (1-60).
		update_option( 'woocommerce_remote_variant_assignment', 30 );

		$bundles     = DefaultFreeExtensions::get_all();
		$bundle_keys = array_map(
			function ( $bundle ) {
				return $bundle->key;
			},
			$bundles
		);

		$this->assertNotContains( 'obw/core-profiler', $bundle_keys );

		// Cleanup.
		remove_filter( 'woocommerce_admin_get_feature_config', $filter );
	}

	/**
	 * Tests that core profiler bundle remains when feature flag is enabled but user is not in rollout group.
	 */
	public function test_core_profiler_bundle_remains_when_feature_enabled_but_not_in_rollout() {
		// Enable the feature flag.
		$filter = function ( $config ) {
			$config['disable-core-profiler-fallback'] = true;
			return $config;
		};
		add_filter( 'woocommerce_admin_get_feature_config', $filter );

		// Set user outside rollout group (61-120).
		update_option( 'woocommerce_remote_variant_assignment', 90 );

		$bundles     = DefaultFreeExtensions::get_all();
		$bundle_keys = array_map(
			function ( $bundle ) {
				return $bundle->key;
			},
			$bundles
		);

		$this->assertContains( 'obw/core-profiler', $bundle_keys );

		// Cleanup.
		remove_filter( 'woocommerce_admin_get_feature_config', $filter );
	}

	/**
	 * Tests that core profiler bundle remains when feature flag is disabled.
	 */
	public function test_core_profiler_bundle_remains_when_feature_disabled() {
		// Disable the feature flag.
		$filter = function ( $config ) {
			$config['disable-core-profiler-fallback'] = false;
			return $config;
		};
		add_filter( 'woocommerce_admin_get_feature_config', $filter );

		// Set user in rollout group (shouldn't matter).
		update_option( 'woocommerce_remote_variant_assignment', 30 );

		$bundles     = DefaultFreeExtensions::get_all();
		$bundle_keys = array_map(
			function ( $bundle ) {
				return $bundle->key;
			},
			$bundles
		);

		$this->assertContains( 'obw/core-profiler', $bundle_keys );

		// Cleanup.
		remove_filter( 'woocommerce_admin_get_feature_config', $filter );
	}
}
