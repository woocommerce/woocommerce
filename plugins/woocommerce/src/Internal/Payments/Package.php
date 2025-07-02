<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Payments;

defined( 'ABSPATH' ) || exit;

/**
 * This class is used to initialize the email editor package.
 *
 * It is a wrapper around the Automattic\WooCommerce\EmailEditor\Package class and
 * ensures that the email editor package is only initialized if the block editor feature flag is enabled.
 */
class Package {
	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Package active.
	 *
	 * @var bool
	 */
	private static $package_active = true;

	/**
	 * Init the package.
	 *
	 * @internal
	 */
	final public static function init() {
		// self::initialize();
		require_once __DIR__ . '/../../../packages/woocommerce-payments/woocommerce-payments.php';
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return '1.0.0';
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ . '/../../../packages/woocommerce-payments' );
	}

	/**
	 * Initialize the email editor integration by fetching the class from the container.
	 *
	 * @return void
	 */
	public static function initialize() {
		require_once __DIR__ . '/../../../packages/woocommerce-payments/woocommerce-payments.php';
	}
}
