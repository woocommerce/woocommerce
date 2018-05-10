<?php

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
function woo_dash_enqueue_script( $hook ){
	if ( 'toplevel_page_woodash' !== $hook ) {
		return;
	}

	wp_enqueue_script( WOO_DASH_APP );
	wp_enqueue_style( WOO_DASH_APP );
}
add_action( 'admin_enqueue_scripts', 'woo_dash_enqueue_script' );

function woo_dash_admin_body_class( $admin_body_class = '' ) {
	global $hook_suffix;

	if ( ! in_array( $hook_suffix, array( 'toplevel_page_woodash' ) ) ) {
		return $admin_body_class;
	}

	$classes = explode( ' ', trim( $admin_body_class ) );
	$classes[] = 'woocommerce-page';
	$admin_body_class = implode( ' ', array_unique( $classes ) );
	return " $admin_body_class ";
}
add_filter( 'admin_body_class', 'woo_dash_admin_body_class' );


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
