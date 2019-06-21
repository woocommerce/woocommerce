<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: https://woocommerce.com/
 * Description: An eCommerce toolkit that helps you sell anything. Beautifully.
 * Version: 3.7.0-dev
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

// Define WC_PLUGIN_FILE.
if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
	define( 'WC_PLUGIN_FILE', __FILE__ );
}

/**
 * Load all the packages.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload_packages.php';
if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(  // phpcs:ignore
			esc_html__( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, please refer to this document to set up your development environment: https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment', 'woocommerce' )
		);
	}
	/**
	 * Outputs an admin notice for folks running WooCommerce without having run composer install.
	 */
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php
					printf(
						/* translators: 1: is a link to a support document. 2: closing link */
						esc_html__( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'woocommerce' ),
						'<a href="' . esc_url( 'https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment' ) . '" target="_blank" rel="noopener noreferrer">',
						'</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

// Include the main WooCommerce class.
require __DIR__ . '/includes/class-woocommerce.php';

/**
 * Returns the main instance of WC.
 *
 * @since  2.1
 * @return WooCommerce
 */
function WC() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return WooCommerce::instance();
}

// Global for backwards compatibility.
$GLOBALS['woocommerce'] = WC();
