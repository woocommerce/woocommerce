<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\DataStore;

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use WC_Unit_Test_Case;

/**
 * Tests for the base Reports DataStore excluded order statuses default.
 */
class ExcludedOrderStatusesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox get_excluded_report_order_statuses defaults to pending, failed, cancelled (plus auto-draft, trash) when not filtered.
	 */
	public function test_default_excluded_order_statuses_without_filter(): void {
		$statuses = $this->invoke_get_excluded_report_order_statuses();

		$this->assertContains( 'pending', $statuses );
		$this->assertContains( 'failed', $statuses );
		$this->assertContains( 'cancelled', $statuses );
		$this->assertContains( 'auto-draft', $statuses );
		$this->assertContains( 'trash', $statuses );
	}

	/**
	 * @testdox get_excluded_report_order_statuses falls back to the built-in default when the default-statuses filter returns a non-array.
	 */
	public function test_default_excluded_order_statuses_filter_invalid_type_falls_back(): void {
		add_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses', '__return_false' );

		$statuses = $this->invoke_get_excluded_report_order_statuses();

		remove_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses', '__return_false' );

		$this->assertContains( 'pending', $statuses, 'Should still contain the built-in default "pending" status.' );
		$this->assertContains( 'failed', $statuses, 'Should still contain the built-in default "failed" status.' );
		$this->assertContains( 'cancelled', $statuses, 'Should still contain the built-in default "cancelled" status.' );
	}

	/**
	 * @testdox get_excluded_report_order_statuses falls back to the built-in default when the woocommerce_analytics_excluded_order_statuses filter returns a non-array.
	 */
	public function test_excluded_order_statuses_filter_invalid_type_falls_back(): void {
		add_filter( 'woocommerce_analytics_excluded_order_statuses', '__return_false' );

		$statuses = $this->invoke_get_excluded_report_order_statuses();

		remove_filter( 'woocommerce_analytics_excluded_order_statuses', '__return_false' );

		$this->assertSame( array( 'auto-draft', 'trash', 'pending', 'failed', 'cancelled' ), $statuses );
	}

	/**
	 * Call the protected static get_excluded_report_order_statuses method via reflection.
	 *
	 * @return array
	 */
	private function invoke_get_excluded_report_order_statuses(): array {
		$method = new \ReflectionMethod( CustomersDataStore::class, 'get_excluded_report_order_statuses' );
		$method->setAccessible( true );
		return $method->invoke( null );
	}
}
