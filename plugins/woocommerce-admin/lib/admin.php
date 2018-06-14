<?php

/**
 * Returns true if we are on a JS powered admin page.
 */
function woo_dash_is_admin_page() {
	global $hook_suffix;
	if ( in_array( $hook_suffix, array( 'woocommerce_page_woodash' ) ) ) {
		return true;
	}
	return false;
}

/**
 * Register menu pages for the Dashboard and Analytics sections
 */
function woo_dash_register_pages(){
	global $menu, $submenu;

	// woocommerce_page_woodash
	add_submenu_page(
		'woocommerce',
		__( 'WooCommerce Dashboard', 'woo-dash' ),
		__( 'Dashboard', 'woo-dash' ),
		'manage_options',
		'woodash',
		'woo_dash_page'
	);


	// toplevel_page_wooanalytics
	add_menu_page(
		__( 'WooCommerce Analytics', 'woo-dash' ),
		__( 'Analytics', 'woo-dash' ),
		'manage_options',
		'woodash#/analytics',
		'woo_dash_page',
		'dashicons-chart-bar',
		56 // After WooCommerce & Product menu items
	);

	// TODO: Remove. Test report link
	add_submenu_page(
		'woodash#/analytics',
		__( 'Report Title', 'woo-dash' ),
		__( 'Report Title', 'woo-dash' ),
		'manage_options',
		'woodash#/analytics/test',
		'woo_dash_page'
	);

	add_submenu_page(
		'woodash#/analytics',
		__( 'Revenue', 'woo-dash' ),
		__( 'Revenue', 'woo-dash' ),
		'manage_options',
		'woodash#/analytics/revenue',
		'woo_dash_page'
	);
}
add_action( 'admin_menu', 'woo_dash_register_pages' );

/**
 * This method is temporary while this is a feature plugin. As a part of core,
 * we can integrate this better with wc-admin-menus.
 *
 * It makes dashboard the top level link for 'WooCommerce' and renames the first Analytics menu item.
 */
function woo_dash_link_structure() {
	global $submenu;
	// User does not have capabilites to see the submenu
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$woodash_key = null;
	foreach ( $submenu['woocommerce'] as $submenu_key => $submenu_item ) {
		if ( 'woodash' === $submenu_item[2] ) {
			$woodash_key = $submenu_key;
			break;
		}
	}

	if ( ! $woodash_key ) {
		return;
	}

	$dashmenu    = $submenu['woocommerce'][ $woodash_key ];
	$dashmenu[2] = 'admin.php?page=woodash#/';
	unset( $submenu['woocommerce'][ $woodash_key ] );

	array_unshift( $submenu['woocommerce'], $dashmenu );
	$submenu['woodash#/analytics'][0][0] = __( 'Overview', 'woo-dash' );
}

// priority is 20 to run after https://github.com/woocommerce/woocommerce/blob/a55ae325306fc2179149ba9b97e66f32f84fdd9c/includes/admin/class-wc-admin-menus.php#L165
add_action( 'admin_head', 'woo_dash_link_structure', 20 );

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
	echo '<div class="woocommerce__admin-notice-list-hide" id="wpadmin-notice-list">';
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


// TODO Can we do some URL rewriting so we can figure out which page they are on server side?
function woo_dash_admin_title( $admin_title ) {
	if ( ! woo_dash_is_admin_page() ) {
		return $admin_title;
	}
	return sprintf( __( '%1$s &lsaquo; %2$s &#8212; WooCommerce' ), __( 'Dashboard', 'woo-dash' ), get_bloginfo( 'name' ) );
}
add_filter( 'admin_title',  'woo_dash_admin_title' );

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
