<?php
/**
 * Returns information about the package and handles init.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.2';

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {
		\Automattic\WooCommerce\RestApi\Server::instance()->init();
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}
}
