<?php
/**
 * Get rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteSpecs
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\GetRuleProcessor;

/**
 * class WC_Admin_Tests_RemoteSpecs_RuleProcessors_GetRuleProcessor
 */
class WC_Admin_Tests_RemoteSpecs_RuleProcessors_GetRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Tests that an unknown rule processor returns a FailRuleProcessor
	 *
	 * @group fast
	 */
	public function test_unknown_rule_processor_returns_fail_rule_processor() {
		$get_rule_processor = new GetRuleProcessor();

		$result = $get_rule_processor->get_processor( 'unknown rule type' );

		$this->assertEquals( 'Automattic\\WooCommerce\\Admin\\RemoteSpecs\\RuleProcessors\\FailRuleProcessor', get_class( $result ) );
	}
}
