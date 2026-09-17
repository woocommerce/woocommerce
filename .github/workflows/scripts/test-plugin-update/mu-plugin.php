<?php
/**
 * Plugin Name: Plugin Upgrade Test hacks
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 * Version: 1.0.0
 * Text Domain: woocommerce
 *
 * Used only by the "Plugin Upgrade Test" CI workflow. It lets an
 * unauthenticated request drive /wp-admin/update.php and forces WordPress to
 * see a pending update for a target plugin pointing at a local zip, so the
 * in-place upgrade path can be exercised without contacting wordpress.org.
 *
 * Ported from Jetpack's .github/files/test-plugin-update/mu-plugin.php.
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

// Force user ID 1 as the logged in user.
add_filter(
	'determine_current_user',
	function () {
		return 1;
	}
);

/**
 * Disable the login cookie check.
 */
function wp_validate_auth_cookie() {
	return true;
}

/**
 * Disable the nonce check.
 */
function wp_verify_nonce() {
	return true;
}

// Allow for forcing an "update" of a particular plugin.
add_filter(
	'site_transient_update_plugins',
	function ( $value ) {
		$plugin = get_option( 'fake_plugin_update_plugin' );
		$url    = get_option( 'fake_plugin_update_url' );
		if ( $plugin && $url ) {
			if ( ! is_object( $value ) ) {
				$value = (object) array(
					'response'  => array(),
					'no_update' => array(),
				);
			}
			if ( ! isset( $value->response[ $plugin ] ) ) {
				if ( isset( $value->no_update[ $plugin ] ) ) {
					$value->response[ $plugin ] = $value->no_update[ $plugin ];
					unset( $value->no_update[ $plugin ] );
				} else {
					$value->response[ $plugin ] = (object) array(
						'plugin' => dirname( $plugin ),
						'slug'   => dirname( $plugin ),
					);
				}
			}
			$value->response[ $plugin ]->new_version = '1000000.0.0';
			$value->response[ $plugin ]->package     = $url;
		}
		return $value;
	}
);

// Prevent WooCommerce's first-activation setup-wizard redirect. Activating WC sets the
// _wc_activation_redirect transient, and OnboardingSetupWizard then 302-redirects the very
// next admin request -- including our /wp-admin/update.php upgrade -- before the upgrade runs.
// This filter makes WooCommerce clear the transient and skip the redirect instead.
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );
