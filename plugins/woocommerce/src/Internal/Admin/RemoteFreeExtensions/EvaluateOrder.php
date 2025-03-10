<?php

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator;

/**
 * Takes already evaluated spec and evaluates the "order" property.
 * This should be used after the spec has been fully evaluated in order to have "context_plugins" work property.
 * "context_plugins" expectes to have the final list of plugins available.
 */
class EvaluateOrder {
	/**
	 * The value to use when the `order` is missing.
	 */
	const MISSING_ORDER_VALUE = 9999;
	/**
	 * Evaluates the spec and returns a status.
	 *
	 * @param array $spec The spec to evaluate. This should be fully evaluated already. We're only processing "order".
	 * @param array $context The context variables.
	 *
	 * @return mixed The evaluated spec.
	 */
	public function evaluate( array $spec, array $context = array() ) {
		$rule_evaluator = new RuleEvaluator( new GetRuleProcessor( $context ) );

		foreach ( $spec as $index => $spec_item ) {
			$spec_item->order = $this->evaluate_order( $rule_evaluator, $spec_item->order, $index );
		}

		return $spec;
	}

	/**
	 * Evaluates the order property.
	 *
	 * @param RuleEvaluator $rule_evaluator The rule evaluator.
	 * @param object        $order The order to evaluate.
	 *
	 * @return mixed The evaluated order.
	 */
	protected function evaluate_order( RuleEvaluator $rule_evaluator, $order, $default_sort_order ) {
		// Return 9999 if the order is not set. This is the highest possible order.
		if ( ! isset( $order->value ) ) {
			return $default_sort_order;
		}

		if ( ! isset( $order->overrides ) ) {
			return $order->value;
		}

		foreach ( $order->overrides as $override ) {
			if ( ! isset( $override->rules ) ) {
				continue;
			}

			$result = $rule_evaluator->evaluate( $override->rules );
			if ( $result ) {
				return $override->value;
			}
		}

		return $order->value;
	}
}
