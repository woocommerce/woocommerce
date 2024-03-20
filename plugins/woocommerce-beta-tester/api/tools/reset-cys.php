<?php
/**
 * Reset CYS API initialization for beta testing.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

register_woocommerce_admin_test_helper_rest_route(
	'/tools/reset-cys',
	'tools_reset_cys'
);

/**
 * A tool to delete all products.
 */
function tools_reset_cys() {
	global $wpdb;

	// Reset the home template.
	$current_theme = wp_get_theme();
	$template      = get_block_template( $current_theme->template . '//home', 'wp_template' );
	if ( $template->id ) {
		wp_delete_post( $template->wp_id, true );
	}

	// Reset the custom styles.
	$wpdb->delete(
		$wpdb->prefix . 'posts',
		array(
			'post_type'  => 'wp_global_styles',
			'post_title' => 'Custom Styles',
		),
		array( '%s', '%s' )
	);

	$wpdb->delete(
		$wpdb->prefix . 'posts',
		array(
			'post_type'  => 'revision',
			'post_title' => 'Custom Styles',
		),
		array( '%s', '%s' )
	);

	// Reset the patterns AI data.
	$wpdb->delete(
		$wpdb->prefix . 'posts',
		array(
			'post_type'  => 'patterns_ai_data',
			'post_title' => 'Patterns AI Data',
		),
		array( '%s', '%s' )
	);

	return true;
}
