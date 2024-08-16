<?php
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Bootstraps the new posts dashboard page.
 *
 * @package Gutenberg
 */
add_action( 'admin_menu', 'woocommerce_add_new_products_dashboard' );

/**
 * Renders the new posts dashboard page.
 */
function woocommerce_products_dashboard() {
	wp_register_style(
		'wp-gutenberg-posts-dashboard',
		gutenberg_url( 'build/edit-site/posts.css', __FILE__ ),
		array()
	);
    WCAdminAssets::get_instance();
	wp_enqueue_style( 'wp-gutenberg-posts-dashboard' );
    wp_enqueue_script( 'wc-admin-product-editor', WC()->plugin_url() . '/assets/js/admin/product-editor' . $suffix . '.js', array( 'wc-product-editor' ), $version, false );
	wp_add_inline_script( 'wp-edit-site', 'window.wc.productEditor.initializeProductsDashboard( "woocommerce-products-dashboard" );', 'after' );
	wp_enqueue_script( 'wp-edit-site' );

	echo '<div id="woocommerce-products-dashboard"></div>';
}

/**
 * Replaces the default posts menu item with the new posts dashboard.
 */
function woocommerce_add_new_products_dashboard() {
	$gutenberg_experiments = get_option( 'gutenberg-experiments' );
	if ( ! $gutenberg_experiments ) {
		return;
	}
	// We may also want to limit this to GB being enabled and the latest GB version being installed.
    if ( ! \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
        return;
    }
	$ptype_obj = get_post_type_object( 'product' );
	add_submenu_page(
		'woocommerce',
		$ptype_obj->labels->name,
		$ptype_obj->labels->name,
		'manage_woocommerce',
		'woocommerce-products-dashboard',
		'woocommerce_products_dashboard'
	);
}
