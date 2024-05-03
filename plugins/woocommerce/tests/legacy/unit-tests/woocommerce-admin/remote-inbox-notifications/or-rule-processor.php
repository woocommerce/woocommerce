<?php
/**
 * Or rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\OrRuleProcessor;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_OrRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_OrRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Both operands evaluating to false and ORed together evaluates to false.
	 *
	 * @group fast
	 */
	public function test_spec_fails_for_both_operands_false() {
		$get_rule_processor = new MockGetRuleProcessor();
		$processor          = new OrRuleProcessor(
			new RuleEvaluator(
				$get_rule_processor
			)
		);
		$rule               = json_decode(
			'{
				"type": "or",
				"operands": [
					[
						{
							"type": "publish_after_time",
							"publish_after": "2020-04-24 11:00:00"
						}
					],
					[
						{
							"type": "publish_after_time",
							"publish_after": "2020-04-24 11:00:00"
						}
					]
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * First operand evaluating to true and ORed together evaluates to true.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_first_operand_true() {
		$get_rule_processor = new MockGetRuleProcessor();
		$processor          = new OrRuleProcessor(
			new RuleEvaluator(
				$get_rule_processor
			)
		);
		$rule               = json_decode(
			'{
				"type": "or",
				"operands": [
					[
						{
							"type": "publish_after_time",
							"publish_after": "2020-04-24 09:00:00"
						}
					],
					[
						{
							"type": "publish_after_time",
							"publish_after": "2020-04-24 11:00:00"
						}
					]
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Second operand evaluating to true and ORed together evaluates to true.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_second_operand_true() {
		$get_rule_processor = new MockGetRuleProcessor();
		$processor          = new OrRuleProcessor(
			new RuleEvaluator(
				$get_rule_processor
			)
		);
		$rule               = json_decode(
			'{
				"type": "or",
				"operands": [
					[
						{
							"type": "publish_after_time",
							"publish_after": "2020-04-24 11:00:00"
						}
					],
					[
						{
							"type": "publish_after_time",
							"publish_after": "2020-04-24 09:00:00"
						}
					]
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Both operands evaluating to true and ORed together evaluates to true.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_both_operands_true() {
		$get_rule_processor = new MockGetRuleProcessor();
		$processor          = new OrRuleProcessor(
			new RuleEvaluator(
				$get_rule_processor
			)
		);
		$rule               = json_decode(
			'{
				"type": "or",
				"operands": [
					[
						{
							"type": "publish_after_time",
							"publish_after": "2020-04-24 09:00:00"
						}
					],
					[
						{
							"type": "publish_after_time",
							"publish_after": "2020-04-24 09:00:00"
						}
					]
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( true, $result );
	}
}
