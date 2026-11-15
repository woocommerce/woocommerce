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
		parent::tearDown();
	}

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

	/**
	 * @testdox get_order_statuses reflects a custom status added by the default-statuses filter when the option has never been saved.
	 *
	 * get_order_statuses() merges the registered statuses, so a built-in slug would pass even
	 * if the filter were ignored. Only a custom slug can prove the filter reaches runtime.
	 */
	public function test_default_actionable_order_statuses_filter_reaches_runtime(): void {
		delete_option( 'woocommerce_actionable_order_statuses' );
		$this->add_filter_returning(
			'woocommerce_analytics_settings_default_actionable_order_statuses',
			array( 'processing', 'on-hold', 'custom-status' )
		);

		$statuses = Controller::get_order_statuses();

		$this->assertContains( 'custom-status', $statuses, 'A status added by the filter should reach the runtime consumer.' );
	}

	/**
	 * Register a filter that returns a fixed value, tracked for removal in tearDown().
	 *
	 * @param string $tag   Filter tag to hook.
	 * @param array  $value Value the filter should return.
	 */
	private function add_filter_returning( string $tag, array $value ): void {
		$callback = static function () use ( $value ) {
			return $value;
		};
		add_filter( $tag, $callback );
		$this->added_filters[] = array( $tag, $callback );
	}
}
