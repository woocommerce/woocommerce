<?php
/**
 * Option rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\OptionRuleProcessor;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_OptionRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_OptionRuleProcessor extends WC_Unit_Test_Case {
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

	/**
	 * Test contains of array
	 *
	 * @group fast
	 */
	public function test_rule_passes_for_contains_array() {
		add_option( 'array_contain_item', array( 'test' ) );
		$processor = new OptionRuleProcessor();
		$rule      = json_decode(
			'
			{
				"type": "option",
				"option_name": "array_contain_item",
				"value": "test",
				"default": [],
				"operation": "contains"
			}
			'
		);

		$result = $processor->process( $rule, null );
		$this->assertEquals( true, $result );

		$rule->value = 'not_included';
		$result      = $processor->process( $rule, null );
		$this->assertEquals( false, $result );
		delete_option( 'array_contain_item' );
	}

	/**
	 * Test contains of substring
	 *
	 * @group fast
	 */
	public function test_rule_passes_for_contains_substring() {
		add_option( 'string_contain_substring', array( 'test' ) );
		$processor = new OptionRuleProcessor();
		$rule      = json_decode(
			'
			{
				"type": "option",
				"option_name": "string_contain_substring",
				"value": "test",
				"default": "",
				"operation": "contains"
			}
			'
		);

		$result = $processor->process( $rule, null );
		$this->assertEquals( true, $result );

		$rule->value = 'not_included';
		$result      = $processor->process( $rule, null );
		$this->assertEquals( false, $result );
		delete_option( 'string_contain_substring' );
	}

	/**
	 * Test not contains of value that is not an array
	 *
	 * @group fast
	 */
	public function test_rule_contains_with_no_array_value() {
		add_option( 'contain_item', false );
		$processor = new OptionRuleProcessor();
		$rule      = json_decode(
			'
			{
				"type": "option",
				"option_name": "contain_item",
				"value": "test",
				"default": [],
				"operation": "contains"
			}
			'
		);

		$result = $processor->process( $rule, null );
		$this->assertEquals( false, $result );

		$rule->operation = '!contains';
		$result          = $processor->process( $rule, null );
		$this->assertEquals( true, $result );

		update_option( 'contain_item', 'random_string' );
		$rule->operation = 'contains';
		$result          = $processor->process( $rule, null );
		$this->assertEquals( false, $result );

		$rule->operation = '!contains';
		$result          = $processor->process( $rule, null );
		$this->assertEquals( true, $result );
		delete_option( 'contain_item' );
	}
}
