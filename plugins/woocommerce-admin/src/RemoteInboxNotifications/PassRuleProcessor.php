<?php
/**
 * Rule processor that passes. This is required because an empty set of rules
 * (or predicate) evaluates to false.
 *
 * @package WooCommerce Admin/Classes;
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Rule processor that passes.
 */
class PassRuleProcessor implements RuleProcessorInterface {
	/**
	 * Passes the rule.
	 *
	 * @param object $rule The specific rule being processed by this rule processor.
	 *
	 * @return bool Always true.
	 */
	public function process( $rule ) {
		return true;
	}

	/**
	 * Validates the rule.
	 *
	 * @param object $rule The rule to validate.
	 *
	 * @return bool Pass/fail.
	 */
	public function validate( $rule ) {
		return true;
	}
}
