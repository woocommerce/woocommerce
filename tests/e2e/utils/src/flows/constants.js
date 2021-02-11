/**
 * External dependencies
 */
const config = require( 'config' );

const baseUrl = config.get( 'url' );

export const WP_ADMIN_LOGIN = baseUrl + 'wp-login.php';
export const WP_ADMIN_DASHBOARD = baseUrl + 'wp-admin';
export const WP_ADMIN_PLUGINS = baseUrl + 'wp-admin/plugins.php';
export const WP_ADMIN_SETUP_WIZARD = baseUrl + 'wp-admin/admin.php?page=wc-admin';
export const WP_ADMIN_ALL_ORDERS_VIEW = baseUrl + 'wp-admin/edit.php?post_type=shop_order';
export const WP_ADMIN_NEW_COUPON = baseUrl + 'wp-admin/post-new.php?post_type=shop_coupon';
export const WP_ADMIN_NEW_ORDER = baseUrl + 'wp-admin/post-new.php?post_type=shop_order';
export const WP_ADMIN_NEW_PRODUCT = baseUrl + 'wp-admin/post-new.php?post_type=product';
export const WP_ADMIN_WC_SETTINGS = baseUrl + 'wp-admin/admin.php?page=wc-settings&tab=';
export const WP_ADMIN_PERMALINK_SETTINGS = baseUrl + 'wp-admin/options-permalink.php';

export const SHOP_PAGE = baseUrl + 'shop';
export const SHOP_PRODUCT_PAGE = baseUrl + '?p=';
export const SHOP_CART_PAGE = baseUrl + 'cart';
export const SHOP_CHECKOUT_PAGE = baseUrl + 'checkout/';
export const SHOP_MY_ACCOUNT_PAGE = baseUrl + 'my-account/';

export const MY_ACCOUNT_ORDERS = baseUrl + 'my-account/orders';
export const MY_ACCOUNT_DOWNLOADS = baseUrl + 'my-account/downloads';
export const MY_ACCOUNT_ADDRESSES = baseUrl + 'my-account/edit-address';
export const MY_ACCOUNT_ACCOUNT_DETAILS = baseUrl + 'my-account/edit-account';
