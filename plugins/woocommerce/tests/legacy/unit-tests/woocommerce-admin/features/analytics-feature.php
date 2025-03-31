<?php
/**
 * Test the class for Analytics feature .
 *
 * @package WooCommerce\Admin\Tests\Features\Analytics
 */

declare(strict_types=1);

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * class WC_Admin_Tests_Analytics_Feature.
 */
class WC_Admin_Tests_Analytics_Feature extends WC_Unit_Test_Case {
	/**
	 * Test that the analytics feature should be disabled when not in admin and the option value is disabled.
	 */
	public function test_analytics_feature_should_be_disabled_when_not_in_admin_and_the_option_value_is_disabled() {
		// Simulate a non-admin environment by disabling feature loading.
		add_filter( 'woocommerce_admin_should_load_features', '__return_false' );

		// Set the analytics feature option to no.
		update_option( 'woocommerce_analytics_enabled', 'no' );

		$this->assertFalse( Features::is_enabled( 'analytics' ) );
	}
}
