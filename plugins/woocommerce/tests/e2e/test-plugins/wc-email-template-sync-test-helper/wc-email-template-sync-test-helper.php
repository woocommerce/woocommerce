<?php
/**
 * Plugin Name: WC Email Template Sync Test Helper
 * Description: E2E test fixture for RSM-146. Option-driven filters and REST endpoints used by Playwright tests. Dormant unless its driving options are set.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: WooCommerce
 *
 * @package WC_Email_Template_Sync_Test_Helper
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// This plugin is only mounted by .wp-env.e2e.json for E2E test environments — it does not ship
// in any production WooCommerce build. REST permission callbacks still enforce manage_options.

define( 'WC_EMAIL_TEMPLATE_SYNC_TEST_HELPER_DIR', plugin_dir_path( __FILE__ ) );

require_once WC_EMAIL_TEMPLATE_SYNC_TEST_HELPER_DIR . 'includes/class-template-html-overrides.php';
require_once WC_EMAIL_TEMPLATE_SYNC_TEST_HELPER_DIR . 'includes/class-rest-controller.php';

add_action(
	'plugins_loaded',
	static function () {
		( new WC_Email_Template_Sync_Test_Helper\Template_HTML_Overrides() )->register();
	},
	20
);

add_action(
	'rest_api_init',
	static function () {
		( new WC_Email_Template_Sync_Test_Helper\REST_Controller() )->register_routes();
	}
);
