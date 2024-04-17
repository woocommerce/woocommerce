<?php
/**
 * Not rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\NotRuleProcessor;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_NotRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_NotRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * An empty operand evaluates to false, so negating that should
	 * evaluate to true.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_empty_operand() {
		$get_rule_processor = new MockGetRuleProcessor();
		$processor          = new NotRuleProcessor(
			new RuleEvaluator(
				$get_rule_processor
			)
		);
		$rule               = json_decode(
			'{
				"type": "not",
				"operand": []
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Operand that evaluates to true negated to false.
	 *
	 * @group fast
	 */
	public function test_spec_fails_for_passing_operand() {
		$get_rule_processor = new MockGetRuleProcessor();
		$processor          = new NotRuleProcessor(
			new RuleEvaluator(
				$get_rule_processor
			)
		);
		$rule               = json_decode(
			'{
				"type": "not",
				"operand": [
					{
						"type": "publish_after_time",
						"publish_after": "2020-04-24 09:00:00"
					}
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Operand that evaluates to false negated to true.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_failing_operand() {
		$get_rule_processor = new MockGetRuleProcessor();
		$processor          = new NotRuleProcessor(
			new RuleEvaluator(
				$get_rule_processor
			)
		);
		$rule               = json_decode(
			'{
				"type": "not",
				"operand": [
					{
						"type": "publish_after_time",
						"publish_after": "2020-04-24 11:00:00"
					}
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( true, $result );
	}
}
