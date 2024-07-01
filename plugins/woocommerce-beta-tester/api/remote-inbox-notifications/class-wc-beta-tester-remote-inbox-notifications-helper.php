<?php

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsDataSourcePoller;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\GetRuleProcessor;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator;

/**
 * Helper class for remote inbox notifications.
 */
class WC_Beta_Tester_Remote_Inbox_Notifications_Helper {

	/**
	 * Get the name of the transient used to store the remote inbox notifications.
	 *
	 * @return string The transient name.
	 */
	public static function get_transient_name() {
		return 'woocommerce_admin_' . RemoteInboxNotificationsDataSourcePoller::ID . '_specs';
	}

	/**
	 * Retrieve the transient data using the transient name.
	 *
	 * @return mixed The transient data.
	 */
	public static function get_transient() {
		return get_transient( static::get_transient_name() );
	}

	/**
	 * Delete a specific notification by its ID from the database.
	 *
	 * @param int $id The ID of the notification to delete.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_by_id( $id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'wc_admin_notes', array( 'note_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'wc_admin_note_actions', array( 'note_id' => $id ) );
		return true;
	}

	/**
	 * Delete all notifications and their associated actions from the database.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return array Associative array containing the count of deleted notes and actions.
	 */
	public static function delete_all() {
		global $wpdb;

		$deleted_note_count   = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_admin_notes" );
		$deleted_action_count = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_admin_note_actions" );
		return array(
			'deleted_note_count'   => $deleted_note_count,
			'deleted_action_count' => $deleted_action_count,
		);
	}

	/**
	 * Test a specific notification by its name and optionally run it.
	 *
	 * @param string      $name The name of the notification to test.
	 * @param string|null $locale The locale of the notification. Defaults to the current system locale.
	 * @param bool        $run Whether to run the notification if the test passes. Defaults to false.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function test( $name, $locale = null, $run = false ) {
		$notifications = static::get_transient();

		if ( ! $locale ) {
			$locale = get_locale();
		}

		if ( ! isset( $notifications[ $locale ][ $name ] ) ) {
			return new WP_Error(
				404,
				"'{$name}' was not found in the latest remote inbox notification transient. Please re-run the import command.",
			);
		}

		$spec           = $notifications[ $locale ][ $name ];
		$test           = true;
		$failed_rules   = array();
		$rule_processor = new GetRuleProcessor();
		foreach ( $spec->rules as $rule ) {
			if ( ! is_object( $rule ) ) {
				$test = false;
				break;
			}
			$processor        = $rule_processor->get_processor( $rule->type );
			$processor_result = $processor->process( $rule, null );
			if ( ! $processor_result ) {
				$test           = false;
				$failed_rules[] = $rule;
			}
		}

		if ( $test && $run ) {
			$stored_state = RemoteInboxNotificationsEngine::get_stored_state();
			SpecRunner::run_spec( $spec, $stored_state );
		}

		if ( ! $test ) {
			return new WP_Error(
				400,
				"Test failed for '{$name}'",
				array( 'failed_rules' => $failed_rules )
			);
		}

		return true;
	}

	/**
	 * Import specifications and store them in the transient.
	 *
	 * @param array $specs An array of specifications to import.
	 * @return bool True on success.
	 */
	public static function import( $specs ) {
		$stored_state = RemoteInboxNotificationsEngine::get_stored_state();
		$transient    = static::get_transient();

		foreach ( $specs as $spec ) {
			SpecRunner::run_spec( $spec, $stored_state );
			if ( isset( $spec->locales ) && is_array( $spec->locales ) ) {
				foreach ( $spec->locales as $locale ) {
					$transient[ $locale->locale ][ $spec->slug ] = $spec;
				}
			}
		}

		set_transient( static::get_transient_name(), $transient );

		return true;
	}
}
