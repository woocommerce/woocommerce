<?php

/**
 * Returns true if we are on a JS powered admin page.
 */
function woo_dash_is_admin_page() {
	global $hook_suffix;
	if ( in_array( $hook_suffix, array( 'toplevel_page_woodash' ) ) ) {
		return true;
	}
	return false;
}

/**
 * Register a new menu page for the Dashboard
 */
function woo_dash_register_page(){
	// toplevel_page_woodash
	add_menu_page(
		__( 'Woo Dash', 'woo-dash' ),
		__( 'WC Dashboard', 'woo-dash' ),
		'manage_options',
		'woodash',
		'woo_dash_page',
		'dashicons-cart',
		6
	);
}
add_action( 'admin_menu', 'woo_dash_register_page' );

/**
 * Load the assets on the Dashboard page
 */
function woo_dash_enqueue_script(){
	if ( ! woo_dash_is_admin_page() ) {
		return;
	}

	wp_enqueue_script( WOO_DASH_APP );
	wp_enqueue_style( WOO_DASH_APP );
}
add_action( 'admin_enqueue_scripts', 'woo_dash_enqueue_script' );

function woo_dash_admin_body_class( $admin_body_class = '' ) {
	global $hook_suffix;

	if ( ! woo_dash_is_admin_page() ) {
		return $admin_body_class;
	}

	$classes = explode( ' ', trim( $admin_body_class ) );
	$classes[] = 'woocommerce-page';
	$admin_body_class = implode( ' ', array_unique( $classes ) );
	return " $admin_body_class ";
}
add_filter( 'admin_body_class', 'woo_dash_admin_body_class' );


function woo_dash_admin_before_notices() {
	if ( ! woo_dash_is_admin_page() ) {
		return;
	}
	echo '<div class="woo-dashboard__admin-notice-list-hide">';
	echo '<div class="wp-header-end"></div>'; // https://github.com/WordPress/WordPress/blob/f6a37e7d39e2534d05b9e542045174498edfe536/wp-admin/js/common.js#L737
}
add_action( 'admin_notices', 'woo_dash_admin_before_notices', 0 );

function woo_dash_admin_after_notices() {
	if ( ! woo_dash_is_admin_page() ) {
		return;
	}
	echo '</div>';
}
add_action( 'admin_notices', 'woo_dash_admin_after_notices', PHP_INT_MAX );


/**
 * Set up a div for the app to render into.
 */
function woo_dash_page(){
	?>
	<div class="wrap">
		<div id="root"></div>
	</div>
<?php
}
