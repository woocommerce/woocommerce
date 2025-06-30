<?php
/**
 * FailingRuleEvaluator
 *
 * @package WooCommerce\Admin\Tests\RemoteSpecs
 */

declare( strict_types = 1 );

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
	public function evaluate( $rules ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return false;
	}
}
