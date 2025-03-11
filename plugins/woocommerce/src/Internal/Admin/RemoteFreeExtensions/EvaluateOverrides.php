<?php

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator;

/**
 * Evaluates `overrides` property in the spec and returns the evaluated spec.
 */
class EvaluateOverrides {
	/**
	 * Evaluates the spec and returns a status.
	 *
	 * @param array $spec The spec to evaluate.
	 * @param array $context The context variables.
	 *
	 * @return array The evaluated spec.
	 */
	public function evaluate( array $spec, array $context = array() ) {
		$rule_evaluator = new RuleEvaluator( new GetRuleProcessorForOverrides( $context ) );

		foreach ( $spec as $spec_item ) {
			if ( isset( $spec_item->overrides ) && is_array( $spec_item->overrides ) ) {
				foreach ( $spec_item->overrides as $override ) {
					if ( ! isset( $override->rules ) || ! is_array( $override->rules ) || ! isset( $override->field ) || ! isset( $override->value ) ) {
						continue;
					}

					if ( $rule_evaluator->evaluate( $override->rules ) ) {
						$spec_item->{$override->field} = $override->value;
					}
				}
			}
		}

		return $spec;
	}
}
