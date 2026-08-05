<?php
/**
 * Filters for maintaining backwards compatibility with deprecated options.
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\TaskList;
use WC_Install;

/**
 * DeprecatedOptions class.
 */
class DeprecatedOptions {
	/**
	 * Initialize.
	 *
	 * @internal
	 */
	final public static function init(): void {
		add_filter( 'pre_option_woocommerce_task_list_complete', array( __CLASS__, 'get_deprecated_options' ), 10, 2 );
		add_filter( 'pre_option_woocommerce_task_list_hidden', array( __CLASS__, 'get_deprecated_options' ), 10, 2 );
		add_filter( 'pre_option_woocommerce_extended_task_list_hidden', array( __CLASS__, 'get_deprecated_options' ), 10, 2 );
		add_filter( 'pre_update_option_woocommerce_task_list_complete', array( __CLASS__, 'update_deprecated_options' ), 10, 3 );
		add_filter( 'pre_update_option_woocommerce_task_list_hidden', array( __CLASS__, 'update_deprecated_options' ), 10, 3 );
		add_filter( 'pre_update_option_woocommerce_extended_task_list_hidden', array( __CLASS__, 'update_deprecated_options' ), 10, 3 );
	}

	/**
	 * Get the values from the correct source when attempting to retrieve deprecated options.
	 *
	 * @param mixed  $pre_option Pre option value.
	 * @param string $option Option name.
	 * @return mixed
	 */
	public static function get_deprecated_options( $pre_option, $option ) {
		if ( defined( 'WC_INSTALLING' ) && WC_INSTALLING === true ) {
			return $pre_option;
		}

		switch ( $option ) {
			case 'woocommerce_task_list_complete':
				$completed = get_option( 'woocommerce_task_list_completed_lists', array() );
				return is_array( $completed ) && in_array( 'setup', $completed, true ) ? 'yes' : 'no';
			case 'woocommerce_task_list_hidden':
				$hidden = get_option( 'woocommerce_task_list_hidden_lists', array() );
				return is_array( $hidden ) && in_array( 'setup', $hidden, true ) ? 'yes' : 'no';
			case 'woocommerce_extended_task_list_hidden':
				$hidden = get_option( 'woocommerce_task_list_hidden_lists', array() );
				return is_array( $hidden ) && in_array( 'extended', $hidden, true ) ? 'yes' : 'no';
			default:
				return $pre_option;
		}
	}

	/**
	 * Updates the new option names when deprecated options are updated.
	 * This is a temporary fallback until we can fully remove the old task list components.
	 *
	 * @param mixed  $value New value.
	 * @param mixed  $old_value Old value.
	 * @param string $option Option name.
	 * @return mixed
	 */
	public static function update_deprecated_options( $value, $old_value, $option ) {
		switch ( $option ) {
			case 'woocommerce_task_list_complete':
				$completed = get_option( 'woocommerce_task_list_completed_lists', array() );
				if ( is_array( $completed ) ) {
					if ( 'yes' === $value ) {
						if ( ! in_array( 'setup', $completed, true ) ) {
							$completed[] = 'setup';
							update_option( 'woocommerce_task_list_completed_lists', $completed, true );
						}
					} else {
						$completed = array_diff( $completed, array( 'setup' ) );
						update_option( 'woocommerce_task_list_completed_lists', array_values( $completed ), true );
					}
					delete_option( 'woocommerce_task_list_complete' );
				}
				return $old_value;
			case 'woocommerce_task_list_hidden':
				$task_list = TaskLists::get_list( 'setup' );
				if ( ! $task_list ) {
					return $value;
				}
				$update = 'yes' === $value ? $task_list->hide() : $task_list->unhide();
				delete_option( 'woocommerce_task_list_hidden' );
				return $old_value;
			case 'woocommerce_extended_task_list_hidden':
				$task_list = TaskLists::get_list( 'extended' );
				if ( ! $task_list ) {
					return $value;
				}
				$update = 'yes' === $value ? $task_list->hide() : $task_list->unhide();
				delete_option( 'woocommerce_extended_task_list_hidden' );
				return $old_value;
			default:
				return $value;
		}
	}
}
