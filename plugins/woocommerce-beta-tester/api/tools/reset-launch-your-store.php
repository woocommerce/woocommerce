<?php

defined( 'ABSPATH' ) || exit;

register_woocommerce_admin_test_helper_rest_route(
	'/tools/reset-launch-your-store',
	'tools_reset_launch_your_store'
);

/**
 * Reset Launch Your Store settings.
 *
 * This function resets the following:
 * - Site visibility settings
 * - Template changes
 * - Survey completion state
 * - Essential task list completion tracking
 *
 * @return bool True if the reset was successful, false otherwise.
 */
function tools_reset_launch_your_store() {
	global $wpdb;

	// Reset site visibility settings.
	delete_option( 'woocommerce_coming_soon' );
	delete_option( 'woocommerce_store_pages_only' );
	delete_option( 'woocommerce_private_link' );
	delete_option( 'woocommerce_share_key' );

	$result = ( new \Automattic\WooCommerce\Admin\API\LaunchYourStore() )->initialize_coming_soon();
	if ( ! $result ) {
		return new WP_Error( 'initialization_failed', 'Failed to initialize coming soon mode.' );
	}

	// Remove template changes.
	$template = get_block_template( 'woocommerce/woocommerce//coming-soon', 'wp_template' );
	if ( $template && isset( $template->wp_id ) ) {
		$delete_result = wp_delete_post( $template->wp_id, true );
		if ( false === $delete_result ) {
			return new WP_Error( 'template_deletion_failed', 'Failed to delete the coming soon template.' );
		}
	}

	// Reset survey completion state.
	delete_option( 'woocommerce_admin_launch_your_store_survey_completed' );

	// Reset essential task list completion tracking.
	$tasks_completed = get_option( \Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task::COMPLETED_OPTION, array() );
	$tasks_completed = array_filter(
		$tasks_completed,
		function( $task ) {
			return 'launch-your-store' !== $task;
		}
	);
	update_option( \Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task::COMPLETED_OPTION, $tasks_completed );

	// Reset setup task list completion tracking.
	$task_lists_completed = get_option( \Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList::COMPLETED_OPTION, array() );
	$task_lists_completed = array_filter(
		$task_lists_completed,
		function( $task_list ) {
			return 'setup' !== $task_list;
		}
	);
	update_option( \Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList::COMPLETED_OPTION, $task_lists_completed );

	return true;
}
