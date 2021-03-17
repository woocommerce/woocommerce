const config = require( 'config' );
const baseUrl = config.get( 'url' );

export const WP_ADMIN_LOGIN = baseUrl + 'wp-login.php';
export const WP_ADMIN_DASHBOARD = baseUrl + 'wp-admin';
export const WP_ADMIN_PLUGINS = baseUrl + 'wp-admin/plugins.php';
export const WP_ADMIN_SETUP_WIZARD =
	baseUrl + 'wp-admin/admin.php?page=wc-setup';
export const WP_ADMIN_ALL_ORDERS_VIEW =
	baseUrl + 'wp-admin/edit.php?post_type=shop_order';
export const WP_ADMIN_NEW_COUPON =
	baseUrl + 'wp-admin/post-new.php?post_type=shop_coupon';
export const WP_ADMIN_NEW_ORDER =
	baseUrl + 'wp-admin/post-new.php?post_type=shop_order';
export const WP_ADMIN_NEW_PRODUCT =
	baseUrl + 'wp-admin/post-new.php?post_type=product';
export const WP_ADMIN_WC_SETTINGS =
	baseUrl + 'wp-admin/admin.php?page=wc-settings&tab=';
export const WP_ADMIN_PERMALINK_SETTINGS =
	baseUrl + 'wp-admin/options-permalink.php';

export const WP_ADMIN_START_PROFILE_WIZARD =
	baseUrl + 'wp-admin/admin.php?page=wc-admin&path=/setup-wizard';

export const WC_ADMIN_HOME = baseUrl + 'wp-admin/admin.php?page=wc-admin';
