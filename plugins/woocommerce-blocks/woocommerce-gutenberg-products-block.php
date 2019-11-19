<?php
/**
 * Plugin Name: WooCommerce Blocks
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: WooCommerce blocks for the Gutenberg editor.
 * Version: 2.5.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain:  woo-gutenberg-products-block
 * Requires at least: 5.0
 * Requires PHP: 5.6
 * WC requires at least: 3.7
 * WC tested up to: 3.8
 *
 * @package WooCommerce\Blocks
 * @internal This file is only used when running the REST API as a feature plugin.
 */

defined( 'ABSPATH' ) || exit;

$minimum_wp_version = '5.0';

/**
 * Whether notices must be displayed in the current page (plugins and WooCommerce pages).
 *
 * @since 2.5.0
 */
function should_display_compatibility_notices() {
	$current_screen = get_current_screen();

	if ( ! isset( $current_screen ) ) {
		return false;
	}

	$is_plugins_page     =
		property_exists( $current_screen, 'id' ) &&
		'plugins' === $current_screen->id;
	$is_woocommerce_page =
		property_exists( $current_screen, 'parent_base' ) &&
		'woocommerce' === $current_screen->parent_base;

	return $is_plugins_page || $is_woocommerce_page;
}

if ( version_compare( $GLOBALS['wp_version'], $minimum_wp_version, '<' ) ) {
	/**
	 * Outputs for an admin notice about running WooCommerce Blocks on outdated WordPress.
	 *
	 * @since 2.5.0
	 */
	function woocommerce_blocks_admin_unsupported_wp_notice() {
		if ( should_display_compatibility_notices() ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'WooCommerce Blocks requires a more recent version of WordPress and has been paused. Please update WordPress to continue enjoying WooCommerce Blocks.', 'woo-gutenberg-products-block' ); ?></p>
			</div>
			<?php
		}
	}
	add_action( 'admin_notices', 'woocommerce_blocks_admin_unsupported_wp_notice' );
	return;
}

define( 'WC_BLOCKS_PLUGIN_FILE', __FILE__ );

/**
 * Autoload packages.
 *
 * The package autoloader includes version information which prevents classes in this feature plugin
 * conflicting with WooCommerce core.
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
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the WooCommerce Blocks feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'woo-gutenberg-products-block' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}
	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: 1: composer command. 2: plugin directory */
						esc_html__( 'Your installation of the WooCommerce Blocks feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'woo-gutenberg-products-block' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

add_action( 'plugins_loaded', array( '\Automattic\WooCommerce\Blocks\Package', 'init' ) );

