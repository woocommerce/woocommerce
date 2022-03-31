<?php
/**
 * WCAdmin active for rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotification
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\WCAdminActiveForRuleProcessor;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_WCAdminActiveForRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_WCAdminActiveForRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Greater than 7 days evaluates to true
	 *
	 * @group fast
	 */
	public function test_greater_than_7_days_evaluates_to_true() {
		$processor = new WCAdminActiveForRuleProcessor(
			new MockWCAdminActiveForProvider()
		);
		$rule      = json_decode(
			'{
				"type": "wcadmin_active_for",
				"operation": ">",
				"days": 7
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Greater than 12 days evaluates to false
	 *
	 * @group fast
	 */
	public function test_greater_than_12_days_evaluates_to_false() {
		$processor = new WCAdminActiveForRuleProcessor(
			new MockWCAdminActiveForProvider()
		);
		$rule      = json_decode(
			'{
				"type": "wcadmin_active_for",
				"operation": ">",
				"days": 12
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}
}
