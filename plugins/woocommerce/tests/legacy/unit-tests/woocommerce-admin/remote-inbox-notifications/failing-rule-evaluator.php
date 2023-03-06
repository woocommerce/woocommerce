<?php
/**
 * FailingRuleEvaluator
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

/**
 * class FailingRuleEvaluator
 */
class FailingRuleEvaluator {
	/**
	 * Evaluate to false.
	 *
	 * @param array $rules The rules to evaluate.
	 *
	 * @return bool The evaluated result.
	 */
	public function evaluate( $rules ) {
		return false;
	}
}
