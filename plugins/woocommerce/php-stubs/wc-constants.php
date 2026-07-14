<?php
/**
 * Stub file for WooCommerce constants.
 *
 * These constants are defined at runtime via WooCommerce::define() which PHPStan
 * cannot statically analyze. This stub provides the definitions so PHPStan can
 * properly validate code that uses these constants.
 *
 * @see \WooCommerce::define_constants()
 * @see \Automattic\WooCommerce\Internal\Admin\FeaturePlugin::define_constants()
 */

// Core WooCommerce constants (defined in class-woocommerce.php).
define( 'WC_ABSPATH', '' );
define( 'WC_PLUGIN_BASENAME', '' );
define( 'WC_VERSION', '' );
define( 'WOOCOMMERCE_VERSION', '' );
define( 'WC_ROUNDING_PRECISION', 6 );
define( 'WC_DISCOUNT_ROUNDING_MODE', 2 );
define( 'WC_TAX_ROUNDING_MODE', 1 );
define( 'WC_DELIMITER', '|' );
define( 'WC_SESSION_CACHE_GROUP', 'wc_session_id' );
define( 'WC_TEMPLATE_DEBUG_MODE', false );
define( 'WC_LOG_DIR', '' );
define( 'WC_NOTICE_MIN_PHP_VERSION', '7.2' );
define( 'WC_NOTICE_MIN_WP_VERSION', '5.2' );
define( 'WC_PHP_MIN_REQUIREMENTS_NOTICE', '' );
define( 'WC_SSR_PLUGIN_UPDATE_RELEASE_VERSION_TYPE', 'none' );

// Admin constants (defined in FeaturePlugin.php).
define( 'WC_ADMIN_ABSPATH', '' );
define( 'WC_ADMIN_APP', '' );
define( 'WC_ADMIN_DIST_CSS_FOLDER', '' );
define( 'WC_ADMIN_DIST_JS_FOLDER', '' );
define( 'WC_ADMIN_IMAGES_FOLDER_URL', '' );
define( 'WC_ADMIN_PLUGIN_FILE', '' );
