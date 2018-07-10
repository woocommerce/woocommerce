<?php
/**
 * Retrieves the root plugin path.
 *
 * @param  string $file Optional relative path of the desired file.
 *
 * @return string Root path to the gutenberg custom fields plugin.
 */
function wc_admin_dir_path( $file = '' ) {
	return plugin_dir_path( dirname(__FILE__ ) ) . $file;
}

/**
 * Retrieves a URL to a file in the gutenberg custom fields plugin.
 *
 * @param  string $path Relative path of the desired file.
 *
 * @return string       Fully qualified URL pointing to the desired file.
 */
function wc_admin_url( $path ) {
	return plugins_url( $path, dirname( __FILE__ ) );
}

/**
 * Returns the current screen ID.
 * This is slightly different from WP's get_current_screen, in that it attaches an action,
 * so certain pages like 'add new' pages can have different breadcrumbs or handling.
 * It also catches some more unique dynamic pages like taxonomy/attribute management.
 *
 * Format: {$current_screen->action}-{$current_screen->action}, or just {$current_screen->action} if no action is found
 *
 * @return string Current screen ID.
 */
function wc_admin_get_current_screen_id() {
	$current_screen = get_current_screen();
	if ( ! $current_screen ) {
		return false;
	}
	$current_screen_id = $current_screen->action ? $current_screen->action . '-' . $current_screen->id : $current_screen->id;

	if ( ! empty( $_GET['taxonomy' ] ) && ! empty( $_GET['post_type'] ) && 'product' === $_GET['post_type'] ) {
		$current_screen_id = 'product_page_product_attributes';
	}

	return $current_screen_id;
}

/**
 * Returns breadcrumbs for the current page.
 *
 * TODO When merging to core, we should explore a better API for defining breadcrumbs, instead of just defining an array.
 */
function wc_admin_get_embed_breadcrumbs() {
	$current_screen_id = wc_admin_get_current_screen_id();

	// If a page has a tab, we can append that to the screen ID and show another pagination level
	$pages_with_tabs = array(
		'wc-reports'  => 'orders',
		'wc-settings' => 'general',
		'wc-status'   => 'status',
	);
	$tab = '';
	if ( ! empty( $_GET['page' ] ) ) {
		if ( in_array( $_GET['page'], array_keys( $pages_with_tabs ) ) ) {
			$tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] . '-' : $pages_with_tabs[ $_GET['page'] ] . '-';
		}
	}

	$breadcrumbs = apply_filters( 'wc_admin_get_breadcrumbs', array(
		'edit-shop_order' => __( 'Orders', 'wc-admin' ),
		'add-shop_order'  => array(
			array( '/edit.php?post_type=shop_order', __( 'Orders', 'wc-admin' ) ),
			__( 'Add New', 'wc-admin' )
		),
		'shop_order'     => array(
			array( '/edit.php?post_type=shop_order', __( 'Orders', 'wc-admin' ) ),
			__( 'Edit Order', 'wc-admin' )
		),
		'edit-shop_coupon' => __( 'Coupons', 'wc-admin' ),
		'add-shop_coupon'  => array(
			array( 'edit.php?post_type=shop_coupon', __( 'Coupons', 'wc-admin' ) ),
			__( 'Add New', 'wc-admin' )
		),
		'shop_coupon' => array(
			array( 'edit.php?post_type=shop_coupon', __( 'Coupons', 'wc-admin' ) ),
			__( 'Edit Coupon', 'wc-admin' )
		),
		'woocommerce_page_wc-reports' => array(
			array( 'admin.php?page=wc-reports', __( 'Reports', 'wc-admin' ) )
		),
		'orders-woocommerce_page_wc-reports' => array(
			array( 'admin.php?page=wc-reports', __( 'Reports', 'wc-admin' ) ),
			__( 'Orders', 'wc-admin' )
		),
		'customers-woocommerce_page_wc-reports' => array(
			array( 'admin.php?page=wc-reports', __( 'Reports', 'wc-admin' ) ),
			__( 'Customers', 'wc-admin' )
		),
		'stock-woocommerce_page_wc-reports' => array(
			array( 'admin.php?page=wc-reports', __( 'Reports', 'wc-admin' ) ),
			__( 'Stock', 'wc-admin' )
		),
		'taxes-woocommerce_page_wc-reports' => array(
			array( 'admin.php?page=wc-reports', __( 'Reports', 'wc-admin' ) ),
			__( 'Taxes', 'wc-admin' )
		),
		'woocommerce_page_wc-settings' => array(
			array( 'admin.php?page=wc-settings', __( 'Settings', 'wc-admin' ) )
		),
		'general-woocommerce_page_wc-settings' => array(
			array( 'admin.php?page=wc-settings', __( 'Settings', 'wc-admin' ) ),
			__( 'General', 'wc-admin' ),
		),
		'products-woocommerce_page_wc-settings' => array(
			array( 'admin.php?page=wc-settings', __( 'Settings', 'wc-admin' ) ),
			__( 'Products', 'wc-admin' ),
		),
		'tax-woocommerce_page_wc-settings' => array(
			array( 'admin.php?page=wc-settings', __( 'Settings', 'wc-admin' ) ),
			__( 'Tax', 'wc-admin' ),
		),
		'shipping-woocommerce_page_wc-settings' => array(
			array( 'admin.php?page=wc-settings', __( 'Settings', 'wc-admin' ) ),
			__( 'Shipping', 'wc-admin' ),
		),
		'checkout-woocommerce_page_wc-settings' => array(
			array( 'admin.php?page=wc-settings', __( 'Settings', 'wc-admin' ) ),
			__( 'Payments', 'wc-admin' ),
		),
		'email-woocommerce_page_wc-settings' => array(
			array( 'admin.php?page=wc-settings', __( 'Settings', 'wc-admin' ) ),
			__( 'Emails', 'wc-admin' ),
		),
		'advanced-woocommerce_page_wc-settings' => array(
			array( 'admin.php?page=wc-settings', __( 'Settings', 'wc-admin' ) ),
			__( 'Advanced', 'wc-admin' ),
		),
		'woocommerce_page_wc-status'  => array(
			__( 'Status', 'wc-admin' ),
		),
		'status-woocommerce_page_wc-status'  => array(
			array( 'admin.php?page=wc-status', __( 'Status', 'wc-admin' ) ),
			__( 'System Status', 'wc-admin' ),
		),
		'tools-woocommerce_page_wc-status'  => array(
			array( 'admin.php?page=wc-status', __( 'Status', 'wc-admin' ) ),
			__( 'Tools', 'wc-admin' ),
		),
		'logs-woocommerce_page_wc-status'  => array(
			array( 'admin.php?page=wc-status', __( 'Status', 'wc-admin' ) ),
			__( 'Logs', 'wc-admin' ),
		),
		'connect-woocommerce_page_wc-status'  => array(
			array( 'admin.php?page=wc-status', __( 'Status', 'wc-admin' ) ),
			__( 'WooCommerce Services Status', 'wc-admin' ),
		),
		'woocommerce_page_wc-addons' => __( 'Extensions', 'wc-admin' ),
		'edit-product' => __( 'Products', 'wc-admin' ),
		'product_page_product_importer'  => array(
			array( 'edit.php?post_type=product', __( 'Products', 'wc-admin' ) ),
			__( 'Import', 'wc-admin' ),
		),
		'product_page_product_exporter' =>  array(
			array( 'edit.php?post_type=product', __( 'Products', 'wc-admin' ) ),
			__( 'Export', 'wc-admin' ),
		),
		'add-product' => array(
			array( 'edit.php?post_type=product', __( 'Products', 'wc-admin' ) ),
			__( 'Add New', 'wc-admin' ),
		),
		'product' => array(
			array( 'edit.php?post_type=product', __( 'Products', 'wc-admin' ) ),
			__( 'Edit Product', 'wc-admin' ),
		),
		'edit-product_cat' => array(
			array( 'edit.php?post_type=product', __( 'Products', 'wc-admin' ) ),
			__( 'Categories', 'wc-admin' ),
		),
		'edit-product_tag' => array(
			array( 'edit.php?post_type=product', __( 'Products', 'wc-admin' ) ),
			__( 'Tags', 'wc-admin' ),
		),
		'product_page_product_attributes' => array(
			array( 'edit.php?post_type=product', __( 'Products', 'wc-admin' ) ),
			__( 'Attributes', 'wc-admin' ),
		),
	) );


	if ( ! empty ( $breadcrumbs[ $tab . $current_screen_id ] ) ) {
		return $breadcrumbs[ $tab . $current_screen_id ];
	} else if ( ! empty( $breadcrumbs[ $current_screen_id ] ) ) {
		return $breadcrumbs[ $current_screen_id ];
	} else {
		return '';
	}
}

/**
  * `wc_admin_get_embed_enabled_screen_ids`,  `wc_admin_get_embed_enabled_plugin_screen_ids`,
  * `wc_admin_get_embed_enabled_screen_ids` should be considered temporary functions for the feature plugin.
  *  This is separate from WC's screen_id functions so that extensions explictly have to opt-in to the feature plugin.
  *  TODO When merging to core, we should explore a better API for opting into the new header for extensions.
 */
function wc_admin_get_embed_enabled_core_screen_ids() {
	$screens = array(
		'edit-shop_order',
		'shop_order',
		'add-shop_order',
		'edit-shop_coupon',
		'shop_coupon',
		'add-shop_coupon',
		'woocommerce_page_wc-reports',
		'woocommerce_page_wc-settings',
		'woocommerce_page_wc-status',
		'woocommerce_page_wc-addons',
		'edit-product',
		'product_page_product_importer',
		'product_page_product_exporter',
		'add-product',
		'product',
		'edit-product_cat',
		'edit-product_tag',
		'product_page_product_attributes',
	);
	return apply_filters( 'wc_admin_get_embed_enabled_core_screens_ids', $screens );
}

/**
 * If any extensions want to show the new header, they can register their screen ids.
 * Separate so extensions can register support for the feature plugin separately.
 */
function wc_admin_get_embed_enabled_plugin_screen_ids() {
	$screens = array();
	return apply_filters( 'wc_admin_get_embed_enabled_plugin_screens_ids', $screens );
}

/**
 * Returns core and plugin screen IDs for a list of screens the new header should be enabled on.
 */
function wc_admin_get_embed_enabled_screen_ids() {
	return array_merge( wc_admin_get_embed_enabled_core_screen_ids(), wc_admin_get_embed_enabled_plugin_screen_ids() );
}

/**
 * Return an object defining the currecy options for the site's current currency
 *
 * @return  array  Settings for the current currency {
 *     Array of settings.
 *
 *     @type string $code       Currency code.
 *     @type string $precision  Number of decimals.
 *     @type string $symbol     Symbol for currency.
 * }
 */
function wc_admin_currency_settings() {
	$code = get_woocommerce_currency();

	return apply_filters(
		'wc_currency_settings', array(
			'code'      => $code,
			'precision' => wc_get_price_decimals(),
			'symbol'    => get_woocommerce_currency_symbol( $code ),
		)
	);
}
