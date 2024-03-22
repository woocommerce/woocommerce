<?php
/**
 * WCAdmin active for rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotification
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\WCAdminActiveForRuleProcessor;

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

	/**
	 * Invalid value returns false
	 *
	 * @dataProvider data_provider_for_invalid_admin_active_for
	 * @group fast
	 * @param mixed $wcadmin_active_for WCAdmin active for value.
	 *
	 */
	public function test_admin_active_for_provider_returns_invalid_value( $wcadmin_active_for ) {
		$mocked = $this->getMockBuilder( MockWCAdminActiveForProvider::class )->getMock();
		$mocked
			->expects( $this->once() )
			->method( 'get_wcadmin_active_for_in_seconds' )
			->willReturn( $wcadmin_active_for );

		$processor = new WCAdminActiveForRuleProcessor( $mocked );
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

	/**
	 * Data provider for invalid admin active for.
	 */
	public function data_provider_for_invalid_admin_active_for() {
		return array(
			array( null ),
			array( 'invalid' ),
			array( array() ),
			array( -1 ),
			array( '-10' ),
		);
	}

	/**
	 * Valid rule returns true
	 *
	 * @group fast
	 */
	public function test_valid_rule() {
		$processor = new WCAdminActiveForRuleProcessor();
		$rule      = json_decode(
			'{
				"type": "wcadmin_active_for",
				"operation": ">",
				"days": 12
			}'
		);

		$result = $processor->validate( $rule );

		$this->assertEquals( true, $result );
	}

	/**
	 * Invalid rule returns false
	 *
	 * @dataProvider data_provider_for_invalid_rule
	 * @group fast
	 * @param mixed $rule Rule.
	 */
	public function test_invalid_rule( $rule ) {
		$processor = new WCAdminActiveForRuleProcessor();
		$result    = $processor->validate( $rule );

		$this->assertEquals( false, $result );
	}

	/**
	 * Data provider for invalid rule
	 */
	public function data_provider_for_invalid_rule() {
		return array(
			array(
				json_decode(
					'{
						"type": "wcadmin_active_for",
						"operation": ">",
						"days": -1
					}'
				),
			),
			array(
				json_decode(
					'{
						"type": "wcadmin_active_for",
						"operation": ">",
						"days": null
					}'
				),
			),
			array(
				json_decode(
					'{
						"type": "wcadmin_active_for",
						"operation": ">",
						"days": "wrong type"
					}'
				),
			),
		);
	}
}
