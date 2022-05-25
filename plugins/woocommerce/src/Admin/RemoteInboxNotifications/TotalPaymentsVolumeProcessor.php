<?php
/**
 * Rule processor that passes when a store's payments volume exceeds a provided amount.
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore as Orders;

/**
 * Rule processor that passes when a store's payments volume exceeds a provided amount.
 */
class TotalPaymentsVolumeProcessor implements RuleProcessorInterface {
	/**
	 * Compare against the store's total payments volume.
	 *
	 * @param object $rule         The rule being processed by this rule processor.
	 * @param object $stored_state Stored state.
	 *
	 * @return bool The result of the operation.
	 */
	public function process( $rule, $stored_state ) {
		$value = Orders::get_total_sales( $rule->days );

		return ComparisonOperation::compare(
			$value,
			$rule->value,
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
		if ( ! isset( $rule->days ) ) {
			return false;
		}

		if ( ! isset( $rule->value ) ) {
			return false;
		}

		if ( ! isset( $rule->operation ) ) {
			return false;
		}

		return true;
	}
}
