<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Payments;

defined( 'ABSPATH' ) || exit;

/**
 * This class is used to initialize the WooPayments plugin.
 */
class Package {
	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Init the package.
	 *
	 * @internal
	 */
	final public static function init() {
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
}
