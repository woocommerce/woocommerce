<?php
/**
 * Option rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\OptionRuleProcessor;

/**
 * class WC_Tests_RemoteInboxNotifications_OptionRuleProcessor
 */
class WC_Tests_RemoteInboxNotifications_OptionRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * No default option resolves to false.
	 *
	 * @group fast
	 */
	public function test_rule_passes_for_no_default_option() {
		$processor = new OptionRuleProcessor();
		$rule      = json_decode(
			'
			{
				"type": "option",
				"option_name": "NON_EXISTENT_OPTION",
				"value": false,
				"operation": "="
			}
			'
		);

		$result = $processor->process( $rule, null );

		$this->assertEquals( true, $result );
	}

	/**
	 * Default option of true resolves to true.
	 *
	 * @group fast
	 */
	public function test_rule_passes_for_default_option() {
		$processor = new OptionRuleProcessor();
		$rule      = json_decode(
			'
			{
				"type": "option",
				"option_name": "NON_EXISTENT_OPTION",
				"value": true,
				"default": true,
				"operation": "="
			}
			'
		);

		$result = $processor->process( $rule, null );

		$this->assertEquals( true, $result );
	}
}
