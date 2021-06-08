<?php
/**
 * Evaluates the spec and returns a status.
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RuleEvaluator;

/**
 * Evaluates the spec and returns the evaluated method.
 */
class EvaluateMethod {
	/**
	 * Evaluates the spec and returns the method.
	 *
	 * @param array $spec The method to evaluate.
	 * @return array The evaluated method.
	 */
	public static function evaluate( $spec ) {
		$rule_evaluator = new RuleEvaluator();
		$method         = (object) $spec;

		if ( isset( $method->is_visible ) ) {
			$is_visible         = $rule_evaluator->evaluate( $method->is_visible );
			$method->is_visible = $is_visible;
		}

		return $method;
	}
}
