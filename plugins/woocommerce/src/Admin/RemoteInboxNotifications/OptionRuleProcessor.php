<?php
/**
 * Rule processor that performs a comparison operation against an option value.
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Rule processor that performs a comparison operation against an option value.
 */
class OptionRuleProcessor implements RuleProcessorInterface {
	/**
	 * Performs a comparison operation against the option value.
	 *
	 * @param object $rule         The specific rule being processed by this rule processor.
	 * @param object $stored_state Stored state.
	 *
	 * @return bool The result of the operation.
	 */
	public function process( $rule, $stored_state ) {
		$is_contains    = $rule->operation && strpos( $rule->operation, 'contains' ) !== false;
		$default_value  = $is_contains ? array() : false;
		$is_default_set = property_exists( $rule, 'default' );
		$default        = $is_default_set ? $rule->default : $default_value;
		$option_value   = $this->get_option_value( $rule, $default, $is_contains );

		if ( isset( $rule->transformers ) && is_array( $rule->transformers ) ) {
			$option_value = TransformerService::apply( $option_value, $rule->transformers, $is_default_set, $default );
		}

		return ComparisonOperation::compare(
			$option_value,
			$rule->value,
			$rule->operation
		);
	}

	/**
	 * Retrieves the option value and handles logging if necessary.
	 *
	 * @param object $rule         The specific rule being processed.
	 * @param mixed  $default      The default value.
	 * @param bool   $is_contains  Indicates whether the operation is "contains".
	 *
	 * @return mixed The option value.
	 */
	private function get_option_value( $rule, $default, $is_contains ) {
		$option_value      = get_option( $rule->option_name, $default );
		$is_contains_valid = $is_contains && ( is_array( $option_value ) || ( is_string( $option_value ) && is_string( $rule->value ) ) );

		if ( $is_contains && ! $is_contains_valid ) {
			$logger = wc_get_logger();
			$logger->warning(
				sprintf(
					'ComparisonOperation "%s" option value "%s" is not an array, defaulting to empty array.',
					$rule->operation,
					$rule->option_name
				),
				array(
					'option_value' => $option_value,
					'rule'         => $rule,
				)
			);
			$option_value = array();
		}

		return $option_value;
	}

	/**
	 * Validates the rule.
	 *
	 * @param object $rule The rule to validate.
	 *
	 * @return bool Pass/fail.
	 */
	public function validate( $rule ) {
		if ( ! isset( $rule->option_name ) ) {
			return false;
		}

		if ( ! isset( $rule->value ) ) {
			return false;
		}

		if ( ! isset( $rule->operation ) ) {
			return false;
		}

		if ( isset( $rule->transformers ) && is_array( $rule->transformers ) ) {
			foreach ( $rule->transformers as $transform_args ) {
				$transformer = TransformerService::create_transformer( $transform_args->use );
				if ( ! $transformer->validate( $transform_args->arguments ) ) {
					return false;
				}
			}
		}

		return true;
	}
}
