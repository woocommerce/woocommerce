<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Analytics;

use Automattic\WooCommerce\Admin\Features\Features;
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
		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_analytics_feature' ) );

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
	 * Enable the analytics feature in the legacy admin feature list.
	 *
	 * @param array $features Feature slugs.
	 * @return array
	 */
	public function enable_analytics_feature( array $features ): array {
		$features[] = 'analytics';

		return array_unique( $features );
	}
}
