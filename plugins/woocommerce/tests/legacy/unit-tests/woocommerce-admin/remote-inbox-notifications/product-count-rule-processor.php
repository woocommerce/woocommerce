<?php
/**
 * Product count rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\ProductCountRuleProcessor;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_ProductCountRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_ProductCountRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Get a product_count rule that passes when the product count is > 5.
	 *
	 * @return object
	 */
	private function get_rule() {
		return json_decode(
			'{
				"type": "product_count",
				"value": 5,
				"operation": ">"
			}'
		);
	}

	/**
	 * Given 4 products, more than 5 products rule evaluates to false.
	 *
	 * @group fast
	 */
	public function test_spec_fails_for_not_matching_criteria() {
		$query     = new MockProductQuery( 4 );
		$processor = new ProductCountRuleProcessor( $query );

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Given 6 products, more than 5 products rule evaluates to true.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_matching_criteria() {
		$query     = new MockProductQuery( 6 );
		$processor = new ProductCountRuleProcessor( $query );

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}
}
