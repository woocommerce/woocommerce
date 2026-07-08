<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Analytics;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\RemoteInboxNotifications;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Unit_Test_Case;

/**
 * Unit tests to verify if the Analytics feature is enabled.
 */
class FeatureEnabledTest extends WC_Unit_Test_Case {
	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_analytics_enabled' );
		delete_option( RemoteInboxNotifications::TOGGLE_OPTION_NAME );
		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_analytics_feature' ) );
		remove_filter( 'woocommerce_admin_features', array( $this, 'disable_launch_your_store_feature' ) );
		remove_filter( 'woocommerce_admin_features', array( $this, 'disable_customize_store_feature' ) );

		parent::tearDown();
	}

	/**
	 * @testdox Should disable the analytics feature when the option value is disabled.
	 */
	public function test_should_be_disabled_when_the_option_value_is_disabled(): void {
		update_option( 'woocommerce_analytics_enabled', 'no' );

		$this->assertFalse(
			FeaturesUtil::feature_is_enabled( 'analytics' ),
			'Analytics should be disabled when the feature option is disabled.'
		);
	}

	/**
	 * @testdox Should remove analytics from legacy admin features when the option value is disabled.
	 */
	public function test_should_remove_analytics_from_legacy_admin_features_when_the_option_value_is_disabled(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_analytics_feature' ) );
		update_option( 'woocommerce_analytics_enabled', 'no' );
		$this->setExpectedDeprecated( "Automattic\WooCommerce\Admin\Features\Features::is_enabled( 'analytics' )" );

		try {
			$this->assertFalse(
				Features::is_enabled( 'analytics' ),
				'Analytics should be unavailable in legacy admin features when the feature option is disabled.'
			);
		} finally {
			remove_filter( 'woocommerce_admin_features', array( $this, 'enable_analytics_feature' ) );
		}
	}

	/**
	 * @testdox Should keep retired feature flags enabled through the legacy is_enabled shim.
	 */
	public function test_should_keep_retired_feature_flags_enabled_through_legacy_is_enabled_shim(): void {
		$this->setExpectedDeprecated( "Automattic\WooCommerce\Admin\Features\Features::is_enabled( 'launch-your-store' )" );

		$this->assertTrue(
			Features::is_enabled( 'launch-your-store' ),
			'Retired feature flags should remain enabled through the compatibility shim.'
		);
	}

	/**
	 * @testdox Should keep retired feature flags available through the legacy exists shim.
	 */
	public function test_should_keep_retired_feature_flags_available_through_legacy_exists_shim(): void {
		$this->setExpectedDeprecated( "Automattic\WooCommerce\Admin\Features\Features::exists( 'customize-store' )" );

		$this->assertTrue(
			Features::exists( 'customize-store' ),
			'Retired feature flags should remain available through the compatibility shim.'
		);
	}

	/**
	 * @testdox Should respect filtered retired feature flags through the legacy is_enabled shim.
	 */
	public function test_should_respect_filtered_retired_feature_flags_through_legacy_is_enabled_shim(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_launch_your_store_feature' ) );
		$this->setExpectedDeprecated( "Automattic\WooCommerce\Admin\Features\Features::is_enabled( 'launch-your-store' )" );

		try {
			$this->assertFalse(
				Features::is_enabled( 'launch-your-store' ),
				'Retired feature flags should respect the filtered feature list through the compatibility shim.'
			);
		} finally {
			remove_filter( 'woocommerce_admin_features', array( $this, 'disable_launch_your_store_feature' ) );
		}
	}

	/**
	 * @testdox Should respect filtered retired feature flags through the legacy exists shim.
	 */
	public function test_should_respect_filtered_retired_feature_flags_through_legacy_exists_shim(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_customize_store_feature' ) );
		$this->setExpectedDeprecated( "Automattic\WooCommerce\Admin\Features\Features::exists( 'customize-store' )" );

		try {
			$this->assertFalse(
				Features::exists( 'customize-store' ),
				'Retired feature flags should respect the filtered feature list through the compatibility shim.'
			);
		} finally {
			remove_filter( 'woocommerce_admin_features', array( $this, 'disable_customize_store_feature' ) );
		}
	}

	/**
	 * @testdox Should return false for unknown feature flags.
	 */
	public function test_should_return_false_for_unknown_feature_flags(): void {
		$this->assertFalse(
			Features::is_enabled( 'unknown-feature-flag' ),
			'Unknown feature flags should not be enabled by the compatibility shim.'
		);

		$this->assertFalse(
			Features::exists( 'unknown-feature-flag' ),
			'Unknown feature flags should not exist through the compatibility shim.'
		);
	}

	/**
	 * @testdox Should keep remote inbox notifications option-aware through the legacy shim.
	 */
	public function test_should_keep_remote_inbox_notifications_option_aware_through_legacy_shim(): void {
		update_option( RemoteInboxNotifications::TOGGLE_OPTION_NAME, 'no' );
		$this->setExpectedDeprecated( "Automattic\WooCommerce\Admin\Features\Features::is_enabled( 'remote-inbox-notifications' )" );

		$this->assertFalse(
			Features::is_enabled( 'remote-inbox-notifications' ),
			'Remote inbox notifications should remain option-aware through the compatibility shim.'
		);
	}

	/**
	 * Enable the analytics feature in the legacy admin feature list.
	 *
	 * @param array $features Feature slugs.
	 * @return array
	 */
	public function enable_analytics_feature( array $features ): array {
		$features[] = 'analytics';

		return array_unique( $features );
	}

	/**
	 * Disable the launch your store feature in the legacy admin feature list.
	 *
	 * @param array $features Feature slugs.
	 * @return array
	 */
	public function disable_launch_your_store_feature( array $features ): array {
		return array_values( array_diff( $features, array( 'launch-your-store' ) ) );
	}

	/**
	 * Disable the customize store feature in the legacy admin feature list.
	 *
	 * @param array $features Feature slugs.
	 * @return array
	 */
	public function disable_customize_store_feature( array $features ): array {
		return array_values( array_diff( $features, array( 'customize-store' ) ) );
	}
}
