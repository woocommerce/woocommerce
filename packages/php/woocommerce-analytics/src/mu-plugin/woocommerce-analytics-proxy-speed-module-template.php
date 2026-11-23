<?php
/**
 * Plugin Name: WooCommerce Analytics - Proxy Speed Module
 * Description: Speeds up WooCommerce Analytics' proxy by handling requests at MU-plugin stage and exiting early.
 * Plugin URI: https://woocommerce.com
 * Author: WooCommerce
 * Version: {{VERSION}}
 * Author URI: https://woocommerce.com
 *
 * Text Domain: woocommerce-analytics
 *
 * Loads the package and lets Proxy_Speed_Module answer the request, so the logic updates with the package.
 *
 * @package automattic/woocommerce-analytics
 */

defined( 'ABSPATH' ) || exit;

( static function () {
	// Cheap prefilter so other requests never load the package; Proxy_Speed_Module checks the path strictly.
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path   = explode( '?', $uri, 2 )[0];
	if ( 'POST' !== $method || false === strpos( $path, 'woocommerce-analytics/v1/track' ) ) {
		return;
	}

	// Injected during installation.
	$autoload_path = '{{AUTOLOADER_PATH}}';

	if ( false !== strpos( $autoload_path, '{{' ) ) {
		error_log( 'WooCommerce Analytics Proxy Speed Module: Autoloader path placeholder was not replaced during installation.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return;
	}

	if ( ! file_exists( $autoload_path ) ) {
		error_log( 'WooCommerce Analytics Proxy Speed Module: Autoloader file not found at: ' . $autoload_path ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return;
	}

	try {
		require_once $autoload_path;
	} catch ( \Throwable $e ) {
		error_log( 'WooCommerce Analytics Proxy Speed Module: Failed to load autoloader: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return;
	}

	// The autoloader resolves the highest version across active plugins, which can be older than this file.
	if ( ! class_exists( '\Automattic\Woocommerce_Analytics\Proxy_Speed_Module' ) ) {
		error_log( 'WooCommerce Analytics Proxy Speed Module: the loaded package predates Proxy_Speed_Module.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return;
	}

	\Automattic\Woocommerce_Analytics\Proxy_Speed_Module::maybe_serve();
} )();
