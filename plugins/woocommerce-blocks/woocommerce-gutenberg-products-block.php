<?php
/**
 * Plugin Name: WooCommerce Blocks
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: WooCommerce blocks for the Gutenberg editor.
 * Version: 2.5.0-dev
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain:  woo-gutenberg-products-block
 * Requires at least: 5.0
 * Requires PHP: 5.6
 * WC requires at least: 3.6
 * WC tested up to: 3.8
 *
 * @package WooCommerce\Blocks
 * @internal This file is only used when running the REST API as a feature plugin.
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
	return;
}

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

/**
 * Loads the dependency injection container for woocommerce blocks.
 *
 * @param boolean $reset Used to reset the container to a fresh instance.
 *                       Note: this means all dependencies will be reconstructed.
 */
function wc_blocks_container( $reset = false ) {
	static $container;
	if (
		! $container instanceof Automattic\WooCommerce\Blocks\Registry\Container
		|| $reset
	) {
		$container = new Automattic\WooCommerce\Blocks\Registry\Container();
		// register Package.
		$container->register(
			Automattic\WooCommerce\Blocks\Domain\Package::class,
			function ( $container ) {
				// leave for automated version bumping.
				$version = '2.5.0-dev';
				return new Automattic\WooCommerce\Blocks\Domain\Package(
					$version,
					__FILE__
				);
			}
		);
		// register Bootstrap.
		$container->register(
			Automattic\WooCommerce\Blocks\Domain\Bootstrap::class,
			function ( $container ) {
				return new Automattic\WooCommerce\Blocks\Domain\Bootstrap(
					$container
				);
			}
		);
	}
	return $container;
}

add_action( 'plugins_loaded', 'wc_blocks_bootstrap' );
/**
 * Boostrap WooCommerce Blocks App
 */
function wc_blocks_bootstrap() {
	// initialize bootstrap.
	wc_blocks_container()->get(
		Automattic\WooCommerce\Blocks\Domain\Bootstrap::class
	);
}
