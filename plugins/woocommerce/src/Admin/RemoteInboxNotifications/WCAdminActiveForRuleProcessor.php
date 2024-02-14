<?php
/**
 * Rule processor for publishing if wc-admin has been active for at least the
 * given number of seconds.
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Rule processor for publishing if wc-admin has been active for at least the
 * given number of seconds.
 */
class WCAdminActiveForRuleProcessor implements RuleProcessorInterface {

	/**
	 * Provides the amount of time wcadmin has been active for.
	 *
	 * @var WCAdminActiveForProvider
	 */
	protected $wcadmin_active_for_provider;

	/**
	 * Constructor
	 *
	 * @param object $wcadmin_active_for_provider Provides the amount of time wcadmin has been active for.
	 */
	public function __construct( $wcadmin_active_for_provider = null ) {
		$this->wcadmin_active_for_provider = null === $wcadmin_active_for_provider
			? new WCAdminActiveForProvider()
			: $wcadmin_active_for_provider;
	}

	/**
	 * Performs a comparison operation against the amount of time wc-admin has
	 * been active for in days.
	 *
	 * @param object $rule         The rule being processed.
	 * @param object $stored_state Stored state.
	 *
	 * @return bool The result of the operation.
	 */
	public function process( $rule, $stored_state ) {
		$active_for_seconds = $this->wcadmin_active_for_provider->get_wcadmin_active_for_in_seconds();

		if ( ! $active_for_seconds || ! is_numeric( $active_for_seconds ) || $active_for_seconds < 0 ) {
			return false;
		}

		$rule_seconds = $rule->days * DAY_IN_SECONDS;

		return ComparisonOperation::compare(
			$active_for_seconds,
			$rule_seconds,
			$rule->operation
		);
	}

	/**
	 * Validates the rule.
	 *
	 * @param object $rule The rule to validate.
	 *
	 * @return bool Pass/fail.
	 */
	public function validate( $rule ) {
		// Ensure that 'days' property is set and is a valid numeric value.
		if ( ! isset( $rule->days ) || ! is_numeric( $rule->days ) || $rule->days < 0 ) {
			return false;
		}

		if ( ! isset( $rule->operation ) ) {
			return false;
		}

		return true;
	}
}
