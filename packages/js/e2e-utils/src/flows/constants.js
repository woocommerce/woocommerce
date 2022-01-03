/**
 * External dependencies
 */
const { config } = require( '@woocommerce/e2e-environment' );
const baseUrl = config.get( 'url' );

/**
 * WordPress core dashboard pages.
 * @type {string}
 */
export const WP_ADMIN_LOGIN = baseUrl + 'wp-login.php';
export const WP_ADMIN_DASHBOARD = baseUrl + 'wp-admin';
export const WP_ADMIN_PLUGINS = baseUrl + 'wp-admin/plugins.php';
export const WP_ADMIN_SETUP_WIZARD = baseUrl + 'wp-admin/admin.php?page=wc-admin';
export const WP_ADMIN_ALL_ORDERS_VIEW = baseUrl + 'wp-admin/edit.php?post_type=shop_order';
export const WP_ADMIN_ALL_PRODUCTS_VIEW = baseUrl + 'wp-admin/edit.php?post_type=product';
export const WP_ADMIN_NEW_COUPON = baseUrl + 'wp-admin/post-new.php?post_type=shop_coupon';
export const WP_ADMIN_NEW_ORDER = baseUrl + 'wp-admin/post-new.php?post_type=shop_order';
export const WP_ADMIN_NEW_PRODUCT = baseUrl + 'wp-admin/post-new.php?post_type=product';
export const WP_ADMIN_WC_SETTINGS = baseUrl + 'wp-admin/admin.php?page=wc-settings&tab=';
export const WP_ADMIN_PERMALINK_SETTINGS = baseUrl + 'wp-admin/options-permalink.php';
export const WP_ADMIN_NEW_SHIPPING_ZONE = baseUrl + 'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new';
export const WP_ADMIN_ALL_USERS_VIEW = baseUrl + 'wp-admin/users.php';

export const SHOP_PAGE = baseUrl + 'shop';
export const SHOP_PRODUCT_PAGE = baseUrl + '?p=';
export const SHOP_CART_PAGE = baseUrl + 'cart';
export const SHOP_CHECKOUT_PAGE = baseUrl + 'checkout/';
export const SHOP_MY_ACCOUNT_PAGE = baseUrl + 'my-account/';

/**
 * Customer account pages.
 * @type {string}
 */
export const MY_ACCOUNT_ORDERS = SHOP_MY_ACCOUNT_PAGE + 'orders';
export const MY_ACCOUNT_DOWNLOADS = SHOP_MY_ACCOUNT_PAGE + 'downloads';
export const MY_ACCOUNT_ADDRESSES = SHOP_MY_ACCOUNT_PAGE + 'edit-address';
export const MY_ACCOUNT_ACCOUNT_DETAILS = SHOP_MY_ACCOUNT_PAGE + 'edit-account';

/**
 * Test control flags.
 * @type {boolean}
 */
export const IS_RETEST_MODE = process.env.E2E_RETEST == '1';
