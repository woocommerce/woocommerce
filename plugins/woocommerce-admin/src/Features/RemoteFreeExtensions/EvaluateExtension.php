<?php
/**
 * Evaluates the spec and returns a status.
 */

namespace Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RuleEvaluator;

/**
 * Evaluates the spec and returns the evaluated method.
 */
class EvaluateExtension {
	/**
	 * Evaluates the spec and returns the method.
	 *
	 * @param array $spec The method to evaluate.
	 * @return array The evaluated method.
	 */
	public static function evaluate( $spec ) {
		$rule_evaluator = new RuleEvaluator();
		$method         = $spec;

		if ( isset( $spec->is_visible ) ) {
			$is_visible         = $rule_evaluator->evaluate( $spec->is_visible );
			$method->is_visible = $is_visible;
			// Return early if visibility does not pass.
			if ( ! $is_visible ) {
				return $method;
			}
		}

		if ( isset( $spec->is_configured ) ) {
			$is_configured         = $rule_evaluator->evaluate( $method->is_configured );
			$method->is_configured = $is_configured;
		}

		return $method;
	}
}
