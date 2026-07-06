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

// This plugin is only mounted by .wp-env.test.json for E2E test environments — it does not ship
// in any production WooCommerce build. REST permission callbacks still enforce manage_options.

define( 'WC_EMAIL_TEMPLATE_SYNC_TEST_HELPER_DIR', plugin_dir_path( __FILE__ ) );

require_once WC_EMAIL_TEMPLATE_SYNC_TEST_HELPER_DIR . 'includes/class-template-html-overrides.php';
require_once WC_EMAIL_TEMPLATE_SYNC_TEST_HELPER_DIR . 'includes/class-opted-in-overrides.php';
require_once WC_EMAIL_TEMPLATE_SYNC_TEST_HELPER_DIR . 'includes/class-tracks-recorder.php';
require_once WC_EMAIL_TEMPLATE_SYNC_TEST_HELPER_DIR . 'includes/class-rest-controller.php';

add_action(
	'plugins_loaded',
	static function () {
		( new WC_Email_Template_Sync_Test_Helper\Template_HTML_Overrides() )->register();
		( new WC_Email_Template_Sync_Test_Helper\Opted_In_Overrides() )->register();
		( new WC_Email_Template_Sync_Test_Helper\Tracks_Recorder() )->register();
	},
	20
);

add_action(
	'rest_api_init',
	static function () {
		( new WC_Email_Template_Sync_Test_Helper\REST_Controller() )->register_routes();
	}
);

// Register the fake third-party WC_Email subclass ONLY when an option signals tests want it.
// The class file is lazy-loaded here because it extends WC_Email, which is not defined
// until the woocommerce plugin has loaded — eager-loading at plugin-bootstrap time would
// fatal on PHP class resolution.
add_filter(
	'woocommerce_email_classes',
	static function ( $emails ) {
		if ( 'yes' !== get_option( 'wc_test_fake_third_party_email_enabled', 'no' ) ) {
			return $emails;
		}
		require_once WC_EMAIL_TEMPLATE_SYNC_TEST_HELPER_DIR . 'includes/class-fake-third-party-email.php';
		$emails['WC_Email_Template_Sync_Test_Helper\\Fake_Third_Party_Email'] = new \WC_Email_Template_Sync_Test_Helper\Fake_Third_Party_Email();
		return $emails;
	}
);
