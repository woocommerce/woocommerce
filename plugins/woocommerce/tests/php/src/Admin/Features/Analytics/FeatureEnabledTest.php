<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Analytics;

use Automattic\WooCommerce\Admin\Features\Features;
use WC_Unit_Test_Case;

/**
 * Unit tests to verify if the Analytics feature is enabled.
 */
class FeatureEnabledTest extends WC_Unit_Test_Case {
	/**
	 * Test that the analytics feature should be disabled when not in admin and the option value is disabled.
	 */
	public function test_should_be_disabled_when_not_in_admin_and_the_option_value_is_disabled() {
		// Simulate a non-admin environment by disabling feature loading.
		add_filter( 'woocommerce_admin_should_load_features', '__return_false' );

		// Set the analytics feature option to no.
		update_option( 'woocommerce_analytics_enabled', 'no' );

		$this->assertFalse( Features::is_enabled( 'analytics' ) );
	}
}
