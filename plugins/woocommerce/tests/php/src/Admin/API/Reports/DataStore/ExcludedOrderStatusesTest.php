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
	 * Filter hooks added during a test, as [ tag, callback ] pairs, removed again in tearDown().
	 *
	 * @var array[]
	 */
	private $added_filters = array();

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->added_filters as $added_filter ) {
			remove_filter( $added_filter[0], $added_filter[1] );
		}
		$this->added_filters = array();
		delete_option( 'woocommerce_excluded_report_order_statuses' );
		parent::tearDown();
	}

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
	 * @testdox get_excluded_report_order_statuses reflects a custom status added by the default-statuses filter when the option has never been saved.
	 */
	public function test_default_excluded_order_statuses_filter_reaches_runtime(): void {
		delete_option( 'woocommerce_excluded_report_order_statuses' );
		$callback = array( $this, 'add_custom_excluded_status' );
		add_filter( 'woocommerce_analytics_settings_default_excluded_order_statuses', $callback );
		$this->added_filters[] = array( 'woocommerce_analytics_settings_default_excluded_order_statuses', $callback );

		$statuses = $this->invoke_get_excluded_report_order_statuses();

		$this->assertContains( 'custom-excluded-status', $statuses, 'A status added by the filter should reach the runtime consumer.' );
	}

	/**
	 * @testdox get_excluded_report_order_statuses respects an explicitly saved empty selection instead of restoring the defaults.
	 */
	public function test_saved_empty_excluded_statuses_are_respected(): void {
		update_option( 'woocommerce_excluded_report_order_statuses', array() );

		$statuses = $this->invoke_get_excluded_report_order_statuses();

		$this->assertNotContains( 'pending', $statuses, 'A cleared selection must not fall back to the built-in defaults.' );
		$this->assertNotContains( 'failed', $statuses, 'A cleared selection must not fall back to the built-in defaults.' );
		$this->assertNotContains( 'cancelled', $statuses, 'A cleared selection must not fall back to the built-in defaults.' );
	}

	/**
	 * @testdox get_excluded_report_order_statuses trims padded slugs and drops blank ones saved in the option.
	 */
	public function test_blank_saved_statuses_do_not_reach_runtime(): void {
		update_option( 'woocommerce_excluded_report_order_statuses', array( ' pending ', '', ' ' ) );

		$statuses = $this->invoke_get_excluded_report_order_statuses();

		$this->assertContains( 'pending', $statuses, 'A padded slug should be trimmed and kept.' );
		$this->assertNotContains( ' pending ', $statuses, 'A padded slug must not reach runtime consumers untrimmed.' );
		$this->assertNotContains( '', $statuses, 'A blank slug must not reach runtime consumers.' );
		$this->assertNotContains( ' ', $statuses, 'A whitespace-only slug must not reach runtime consumers.' );
	}

	/**
	 * Filter callback that appends a custom status to the excluded defaults.
	 *
	 * @param array $statuses Default statuses.
	 * @return array
	 */
	public function add_custom_excluded_status( $statuses ) {
		$statuses[] = 'custom-excluded-status';
		return $statuses;
	}

	/**
	 * @testdox get_excluded_report_order_statuses falls back to the pre-filter value and triggers a doing_it_wrong notice when the woocommerce_analytics_excluded_order_statuses filter returns a non-array.
	 */
	public function test_excluded_order_statuses_filter_invalid_type_falls_back(): void {
		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Admin\API\Reports\DataStore::get_excluded_report_order_statuses' );
		add_filter( 'woocommerce_analytics_excluded_order_statuses', '__return_false' );

		$statuses = $this->invoke_get_excluded_report_order_statuses();

		remove_filter( 'woocommerce_analytics_excluded_order_statuses', '__return_false' );

		$this->assertContains( 'pending', $statuses );
		$this->assertContains( 'cancelled', $statuses );
		$this->assertContains( 'failed', $statuses );
		$this->assertContains( 'auto-draft', $statuses );
		$this->assertContains( 'trash', $statuses );
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
