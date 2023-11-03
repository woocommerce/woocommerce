<?php
/**
 * Evaluate and get status tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\EvaluateAndGetStatus;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_EvaluateAndGetStatus
 */
class WC_Admin_Tests_RemoteInboxNotifications_EvaluateAndGetStatus extends WC_Unit_Test_Case {
	/**
	 * Build up a spec given the supplied parameters.
	 *
	 * @param bool $allow_redisplay Allow note redisplay after it has been actioned.
	 *
	 * @return object The spec object.
	 */
	private function get_spec( $allow_redisplay ) {
		return json_decode(
			'{
				"slug": "test",
				"status": "unactioned",
				"rules": [],
				"allow_redisplay": ' . ( $allow_redisplay ? 'true' : 'false' ) . '
			}'
		);
	}

	/**
	 * Get a spec with no rules property.
	 *
	 * @return object The spec object.
	 */
	private function get_no_rules_spec() {
		return json_decode(
			'{
				"status": "unactioned",
				"allow_redisplay": false
			}'
		);
	}

	/**
	 * Tests that for a pending note evaling to true, status is changed
	 * to the spec status.
	 *
	 * @group fast
	 */
	public function test_pending_note_eval_to_true() {
		$spec = $this->get_spec( false );

		$result = EvaluateAndGetStatus::evaluate(
			$spec,
			'unactioned',
			new stdClass(),
			new PassingRuleEvaluator()
		);

		$this->assertEquals( 'unactioned', $result );
	}

	/**
	 * Tests that for a pending note evaluating to false, status is
	 * left at pending.
	 *
	 * @group fast
	 */
	public function test_pending_note_eval_to_false() {
		$spec = $this->get_spec( false );

		$result = EvaluateAndGetStatus::evaluate(
			$spec,
			'pending',
			new stdClass(),
			new FailingRuleEvaluator()
		);

		$this->assertEquals( 'pending', $result );
	}

	/**
	 * Tests that for a snoozed note evaluating to true without allow_redisplay
	 * set, status is left as snoozed.
	 *
	 * @group fast
	 */
	public function test_snoozed_note_eval_to_true_without_allow_redisplay() {
		$spec = $this->get_spec( false );

		$result = EvaluateAndGetStatus::evaluate(
			$spec,
			'snoozed',
			new stdClass(),
			new PassingRuleEvaluator()
		);

		$this->assertEquals( 'snoozed', $result );
	}

	/**
	 * Tests that for a snoozed note evaluating to false without
	 * allow_redisplay set, status is left as snoozed
	 *
	 * @group fast
	 */
	public function test_snoozed_note_eval_to_false_without_allow_redisplay() {
		$spec = $this->get_spec( false );

		$result = EvaluateAndGetStatus::evaluate(
			$spec,
			'snoozed',
			new stdClass(),
			new FailingRuleEvaluator()
		);

		$this->assertEquals( 'snoozed', $result );
	}

	/**
	 * Tests that for an actioned note eval to true with allow_redisplay set,
	 * status is changed to unactioned.
	 *
	 * @group fast
	 */
	public function test_actioned_note_eval_to_true_with_allow_redisplay_set() {
		$spec = $this->get_spec( true );

		$result = EvaluateAndGetStatus::evaluate(
			$spec,
			'actioned',
			new stdClass(),
			new PassingRuleEvaluator()
		);

		$this->assertEquals( 'unactioned', $result );
	}

	/**
	 * Tests that for an actioned note eval to false with allow_redirect set,
	 * status is left at actioned.
	 *
	 * @group fast
	 */
	public function test_actioned_note_eval_to_false_with_allow_redisplay_set() {
		$spec = $this->get_spec( true );

		$result = EvaluateAndGetStatus::evaluate(
			$spec,
			'actioned',
			new stdClass(),
			new FailingRuleEvaluator()
		);

		$this->assertEquals( 'actioned', $result );
	}

	/**
	 * Tests that for a pending note eval to true with allow_redirect
	 * set, status is changed to unactioned.
	 *
	 * @group fast
	 */
	public function test_pending_note_eval_to_true_with_allow_redirect_set() {
		$spec = $this->get_spec( true );

		$result = EvaluateAndGetStatus::evaluate(
			$spec,
			'pending',
			new stdClass(),
			new PassingRuleEvaluator()
		);

		$this->assertEquals( 'unactioned', $result );
	}

	/**
	 * Tests that for a pending note eval to false with allow_redirect
	 * set, status is left as pending.
	 *
	 * @group fast
	 */
	public function test_pending_note_eval_to_false_with_allow_redirect_set() {
		$spec = $this->get_spec( true );

		$result = EvaluateAndGetStatus::evaluate(
			$spec,
			'pending',
			new stdClass(),
			new FailingRuleEvaluator()
		);

		$this->assertEquals( 'pending', $result );
	}

	/**
	 * Tests that for a spec with no rules the current status is returned.
	 *
	 * @group fast
	 */
	public function test_spec_with_no_rules_returns_current_status() {
		$spec = $this->get_no_rules_spec();

		$result = EvaluateAndGetStatus::evaluate(
			$spec,
			'unactioned',
			new stdClass(),
			new FailingRuleEvaluator()
		);

		$this->assertEquals( 'unactioned', $result );
	}
}
