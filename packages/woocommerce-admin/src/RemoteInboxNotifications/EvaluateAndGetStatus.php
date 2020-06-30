<?php
/**
 * Evaluates the spec and returns a status.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Note;

/**
 * Evaluates the spec and returns a status.
 */
class EvaluateAndGetStatus {
	/**
	 * Evaluates the spec and returns a status.
	 *
	 * @param array  $spec           The spec to evaluate.
	 * @param string $current_status The note's current status.
	 * @param object $stored_state   Stored state.
	 * @param object $rule_evaluator Evaluates rules into true/false.
	 *
	 * @return string The evaluated status.
	 */
	public static function evaluate( $spec, $current_status, $stored_state, $rule_evaluator ) {
		// No rules should leave the note alone.
		if ( ! isset( $spec->rules ) ) {
			return $current_status;
		}

		$evaluated_result = $rule_evaluator->evaluate( $spec->rules, $stored_state );

		// Pending notes should be the spec status if the spec passes,
		// left alone otherwise.
		if ( WC_Admin_Note::E_WC_ADMIN_NOTE_PENDING === $current_status ) {
			return $evaluated_result
				? $spec->status
				: WC_Admin_Note::E_WC_ADMIN_NOTE_PENDING;
		}

		// When allow_redisplay isn't set, just leave the note alone.
		if ( ! isset( $spec->allow_redisplay ) || ! $spec->allow_redisplay ) {
			return $current_status;
		}

		// allow_redisplay is set, unaction the note if eval to true.
		return $evaluated_result
			? WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED
			: $current_status;
	}
}
