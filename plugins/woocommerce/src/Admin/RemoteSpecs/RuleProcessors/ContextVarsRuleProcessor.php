<?php

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors;

/**
 * Rule processor for context_vars rules.
 *
 * Processes the following rule:
 * {
 *     "type": "context_vars",
 *     "name": "name of a property in the context_vars array",
 *     "value": "value to match",
 *     "operation": "operation"
 * }
 */
class ContextVarsRuleProcessor implements RuleProcessorInterface {

	/**
	 * The list of context variables.
	 *
	 * @var array a list of context variables.
	 */
	private array $vars;

	/**
	 * Constructor.
	 *
	 * @param array $vars a list of context variables.
	 */
	public function __construct( array $vars ) {
		$this->vars = $vars;
	}

	/**
	 * Performs a comparison operation against the option value.
	 *
	 * @param object $rule         The specific rule being processed by this rule processor.
	 * @param object $stored_state Stored state.
	 *
	 * @return bool The result of the operation.
	 */
	public function process( $rule, $stored_state ) {
		if ( ! isset( $this->vars[ $rule->name ] ) ) {
			return false;
		}

		return ComparisonOperation::compare( $this->vars[ $rule->name ], $rule->value, $rule->operation );
	}

	/**
	 * Validates the rule.
	 *
	 * @param object $rule The rule to validate.
	 *
	 * @return bool Pass/fail.
	 */
	public function validate( $rule ) {
		if ( ! isset( $rule->name ) || ! isset( $rule->value ) || ! isset( $rule->operation ) ) {
			return false;
		}
		return true;
	}
}
