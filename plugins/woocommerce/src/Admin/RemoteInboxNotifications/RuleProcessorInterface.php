<?php
/**
 * Interface for a rule processor.
 *
 * @deprecated 9.4.0 Use \Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleProcessorInterface instead.
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Rule processor interface
 *
 * @deprecated 9.4.0 Use \Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleProcessorInterface instead.
 */
interface RuleProcessorInterface {
	/**
	 * Processes a rule, returning the boolean result of the processing.
	 *
	 * @param object $rule         The rule to process.
	 * @param object $stored_state Stored state.
	 *
	 * @return bool The result of the processing.
	 */
	public function process( $rule, $stored_state );

	/**
	 * Validates the rule.
	 *
	 * @param object $rule The rule to validate.
	 *
	 * @return bool Pass/fail.
	 */
	public function validate( $rule );
}
