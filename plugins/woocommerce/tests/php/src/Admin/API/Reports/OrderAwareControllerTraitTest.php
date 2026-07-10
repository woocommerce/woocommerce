<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports;

use Automattic\WooCommerce\Admin\API\Reports\Controller;
use WC_Unit_Test_Case;

/**
 * Tests for OrderAwareControllerTrait, via the Reports Controller class that uses it.
 */
class OrderAwareControllerTraitTest extends WC_Unit_Test_Case {

	/**
	 * @testdox get_order_statuses includes the built-in default actionable statuses when not filtered.
	 */
	public function test_default_actionable_order_statuses_without_filter(): void {
		$statuses = Controller::get_order_statuses();

		$this->assertContains( 'processing', $statuses );
		$this->assertContains( 'on-hold', $statuses );
	}

	/**
	 * @testdox get_order_statuses falls back to the built-in default actionable statuses when the default-statuses filter returns a non-array.
	 */
	public function test_default_actionable_order_statuses_filter_invalid_type_falls_back(): void {
		add_filter( 'woocommerce_analytics_settings_default_actionable_order_statuses', '__return_false' );

		$statuses = Controller::get_order_statuses();

		remove_filter( 'woocommerce_analytics_settings_default_actionable_order_statuses', '__return_false' );

		$this->assertContains( 'processing', $statuses, 'Should still contain the built-in default "processing" status.' );
		$this->assertContains( 'on-hold', $statuses, 'Should still contain the built-in default "on-hold" status.' );
	}
}
