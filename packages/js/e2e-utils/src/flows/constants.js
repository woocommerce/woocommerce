/**
 * External dependencies
 */
const config = require( 'config' );
const baseUrl = config.get( 'url' );

/**
 * WordPress core dashboard pages.
 * @type {string}
 */
export const WP_ADMIN_LOGIN = baseUrl + 'wp-login.php';
export const WP_ADMIN_DASHBOARD = baseUrl + 'wp-admin/';
export const WP_ADMIN_WP_UPDATES = WP_ADMIN_DASHBOARD + 'update-core.php';
export const WP_ADMIN_PLUGINS = WP_ADMIN_DASHBOARD + 'plugins.php';
export const WP_ADMIN_PLUGIN_INSTALL = WP_ADMIN_DASHBOARD + 'plugin-install.php';
export const WP_ADMIN_PERMALINK_SETTINGS = WP_ADMIN_DASHBOARD + 'options-permalink.php';
export const WP_ADMIN_ALL_USERS_VIEW = WP_ADMIN_DASHBOARD + 'users.php';

/**
 * WooCommerce core post type pages.
 * @type {string}
 */
export const WP_ADMIN_POST_TYPE = WP_ADMIN_DASHBOARD + 'edit.php?post_type=';
export const WP_ADMIN_NEW_POST_TYPE = WP_ADMIN_DASHBOARD + 'post-new.php?post_type=';
export const WP_ADMIN_ALL_COUPONS_VIEW = WP_ADMIN_POST_TYPE + 'shop_coupon';
export const WP_ADMIN_NEW_COUPON = WP_ADMIN_NEW_POST_TYPE + 'shop_coupon';
export const WP_ADMIN_ALL_ORDERS_VIEW = WP_ADMIN_POST_TYPE + 'shop_order';
export const WP_ADMIN_NEW_ORDER = WP_ADMIN_NEW_POST_TYPE + 'shop_order';
export const WP_ADMIN_ALL_PRODUCTS_VIEW = WP_ADMIN_POST_TYPE + 'product';
export const WP_ADMIN_NEW_PRODUCT = WP_ADMIN_NEW_POST_TYPE + 'product';
export const WP_ADMIN_IMPORT_PRODUCTS = WP_ADMIN_ALL_PRODUCTS_VIEW + '&page=product_importer';

/**
 * WooCommerce settings pages.
 * @type {string}
 */
export const WP_ADMIN_PLUGIN_PAGE = WP_ADMIN_DASHBOARD + 'admin.php?page=';
export const WP_ADMIN_WC_HOME = WP_ADMIN_PLUGIN_PAGE + 'wc-admin';
export const WP_ADMIN_SETUP_WIZARD = WP_ADMIN_WC_HOME + '&path=%2Fsetup-wizard';
export const WP_ADMIN_ANALYTICS_PAGES = WP_ADMIN_WC_HOME + '&path=%2Fanalytics%2F';
export const WP_ADMIN_WC_SETTINGS = WP_ADMIN_PLUGIN_PAGE + 'wc-settings&tab=';
export const WP_ADMIN_NEW_SHIPPING_ZONE = WP_ADMIN_WC_SETTINGS + 'shipping&zone_id=new';
export const WP_ADMIN_WC_EXTENSIONS = WP_ADMIN_PLUGIN_PAGE + 'wc-addons';
export const WP_ADMIN_WC_HELPER = WP_ADMIN_PLUGIN_PAGE + 'wc-addons&section=helper';

/**
 * Shop pages.
 * @type {string}
 */
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
