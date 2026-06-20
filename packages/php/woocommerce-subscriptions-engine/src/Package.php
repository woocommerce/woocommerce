<?php
/**
 * Main package class for the WooCommerce Subscriptions Engine.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine;

use Automattic\WooCommerce\SubscriptionsEngine\Integration\Bootstrap;

defined( 'ABSPATH' ) || exit;

/**
 * Package entry point.
 *
 * The engine is a library bundled into WooCommerce core and consumed by the
 * Lite and Premium packages; it is not a standalone, independently activated
 * plugin. Consumers call {@see self::init()} during their own boot to wire the
 * integration layer.
 */
final class Package {

	/**
	 * Package version.
	 */
	const VERSION = '0.0.1';

	/**
	 * Boot the package's integration layer.
	 */
	public static function init(): void {
		Bootstrap::init();
	}

	/**
	 * Return the version of the package.
	 */
	public static function get_version(): string {
		return self::VERSION;
	}

	/**
	 * Return the absolute path to the package root.
	 */
	public static function get_path(): string {
		return dirname( __DIR__ );
	}
}
