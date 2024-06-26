<?php
add_action( 'admin_menu', function() {
	add_management_page(
		'WooCommerce Admin Test Helper',
		'WCA Test Helper',
		'install_plugins',
		'woocommerce-admin-test-helper',
		function() {
			?><div id="woocommerce-admin-test-helper-app-root"></div><?php
		}
	);
} );

add_action( 'wp_loaded', function() {
	require_once __DIR__ . '/vendor/autoload.php';
	require( 'api/api.php' );
} );

add_filter( 'woocommerce_admin_get_feature_config', function( $feature_config ) {
	$feature_config['beta-tester-slotfill-examples'] = false;
    $custom_feature_values = get_option( 'wc_admin_helper_feature_values', array() );
    foreach ( $custom_feature_values as $feature => $value ) {
        if ( isset(  $feature_config[$feature] ) ) {
            $feature_config[$feature] = $value;
        }
    }
	return $feature_config;
} );
