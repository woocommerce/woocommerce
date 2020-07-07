<?php
/**
 * Evaluate the given rules as an AND operation - return false early if a
 * rule evaluates to false.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Evaluate the given rules as an AND operation - return false early if a
 * rule evaluates to false.
 */
class RuleEvaluator {
	/**
	 * Constructor.
	 *
	 * @param GetRuleProcessor $get_rule_processor The GetRuleProcessor to use.
	 */
	public function __construct( $get_rule_processor = null ) {
		$this->get_rule_processor = null === $get_rule_processor
			? new GetRuleProcessor()
			: $get_rule_processor;
	}

	/**
	 * Evaluate the given rules as an AND operation - return false early if a
	 * rule evaluates to false.
	 *
	 * @param array|object $rules        The rule or rules being processed.
	 * @param object       $stored_state Stored state.
	 *
	 * @return bool The result of the operation.
	 */
	public function evaluate( $rules, $stored_state ) {
		if ( ! is_array( $rules ) ) {
			$rules = array( $rules );
		}

		if ( 0 === count( $rules ) ) {
			return false;
		}

		foreach ( $rules as $rule ) {
			$processor        = $this->get_rule_processor->get_processor( $rule->type );
			$processor_result = $processor->process( $rule, $stored_state );

			if ( ! $processor_result ) {
				return false;
			}
		}

		return true;
	}
}
