<?php

/**
 * Register a new menu page for the Dashboard
 */
function woo_dash_register_page(){
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

/**
 * Set up a div for the app to render into.
 */
function woo_dash_page(){
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Woo Dashboard Demo', 'woo-dash' ); ?></h1>
		<div id="root"></div>
	</div>
<?php
}
