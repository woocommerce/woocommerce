<?php
/**
 * Is WooExpress rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\IsWooExpressRuleProcessor;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_IsWooExpressRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_IsWooExpressRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Set Up Before Class.
	 */
	public static function setUpBeforeClass(): void {
		/**
		 * Fake function wc_calypso_bridge_is_woo_express_plan so that we can test the processor.
		 */
		function wc_calypso_bridge_is_woo_express_plan() {
			return apply_filters( 'test_wc_calypso_bridge_is_woo_express_plan', true ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		}
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'test_wc_calypso_bridge_is_woo_express_plan' );
	}

	/**
	 * Get the is_woo_express rule.
	 *
	 * @return object The rule.
	 */
	private function get_rule() {
		return json_decode(
			'{
					"type": "is_woo_express",
					"value": true
			}'
		);
	}

	/**
	 * Test that the processor returns true if the site is on a Woo Express plan.
	 * @group fast
	 */
	public function test_is_woo_express_plan() {
		add_filter( 'test_wc_calypso_bridge_is_woo_express_plan', '__return_true' );

		$processor = new IsWooExpressRuleProcessor();
		$result    = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Test that the processor returns false if the site is not on a Woo Express plan.
	 * @group fast
	 */
	public function test_is_not_woo_express_plan() {
		add_filter( 'test_wc_calypso_bridge_is_woo_express_plan', '__return_false' );

		$processor = new IsWooExpressRuleProcessor();
		$result    = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Test that the processor returns false if plan name is not defined.
	 * @group fast
	 */
	public function test_invalid_plan_name() {
		$rule = (object) array(
			'type'  => 'is_woo_express',
			'value' => true,
			'plan'  => 'invalid_plan',
		);

		$processor = new IsWooExpressRuleProcessor();
		$result    = $processor->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Test that the processor returns true if it's a trial plan.
	 * @group fast
	 */
	public function test_is_trial_plan() {
		/** Fake function wc_calypso_bridge_is_woo_express_trial_plan. */
		function wc_calypso_bridge_is_woo_express_trial_plan() {
			return true;
		}

		$processor = new IsWooExpressRuleProcessor();

		$rule = (object) array(
			'type'  => 'is_woo_express',
			'value' => true,
			'plan'  => 'trial',
		);

		$result = $processor->process( $rule, new stdClass() );
		$this->assertEquals( true, $result );
	}
}
