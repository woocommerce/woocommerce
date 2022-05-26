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
		$value = Orders::get_total_sales( $rule->timeframe );

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
		$allowed_timeframes = array(
			'last_week',
			'last_month',
			'last_quarter',
			'last_6_months',
			'last_year',
		);

		if ( ! isset( $rule->timeframe ) || ! in_array( $rule->timeframe, $allowed_timeframes, true ) ) {
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
