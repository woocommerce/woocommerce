<?php
/**
 * Rule processor that passes (or fails) when the site is on the eCommerce
 * plan.
 *
 * @package WooCommerce\Admin\Classes
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Rule processor that passes (or fails) when the site is on the Woo Express plan.
 * You may optionally pass a plan name to target specific Woo Express plan.
 */
class IsWooExpressRuleProcessor implements RuleProcessorInterface {
	/**
	 * Passes (or fails) based on whether the site is a Woo Express plan.
	 *
	 * @param object $rule         The rule being processed by this rule processor.
	 * @param object $stored_state Stored state.
	 *
	 * @return bool The result of the operation.
	 */
	public function process( $rule, $stored_state ) {
		if ( ! function_exists( 'wc_calypso_bridge_is_woo_express_plan' ) ) {
			return false === $rule->value;
		}

		// If the plan is undefined, only check if it's a Woo Express plan.
		if ( ! isset( $rule->plan ) ) {
			return wc_calypso_bridge_is_woo_express_plan() === $rule->value;
		}

		// If a plan name is defined, only evaluate the plan if we're on the Woo Express plan.
		if ( isset( $rule->plan ) && wc_calypso_bridge_is_woo_express_plan() ) {
			if ( function_exists( 'wc_calypso_bridge_is_woo_express_' . (string) $rule->plan . '_plan' ) ) {
				$fn = 'wc_calypso_bridge_is_woo_express_' . (string) $rule->plan . '_plan';
				return $fn() === $rule->value;
			} elseif ( function_exists( 'wc_calypso_bridge_is_ecommerce_' . (string) $rule->plan . '_plan' ) ) {
				$fn = 'wc_calypso_bridge_is_ecommerce_' . (string) $rule->plan . '_plan';
				return $fn() === $rule->value;
			} else {
				// If an invalid plan name is given, only evaluate the rule if we're targeting all plans other than the specified (invalid) one.
				return false === $rule->value;
			}
		}

		return false;
	}

	/**
	 * Validate the rule.
	 *
	 * @param object $rule The rule to validate.
	 *
	 * @return bool Pass/fail.
	 */
	public function validate( $rule ) {
		if ( ! isset( $rule->value ) ) {
			return false;
		}

		if ( isset( $rule->plan ) ) {
			if ( ! function_exists( 'wc_calypso_bridge_is_woo_express_plan' ) ) {
				return false;
			}
		}

		return true;
	}
}
