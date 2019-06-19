<?php
/**
 * WooCommerce Package class.
 *
 * Stores information about a core/external package, such as it's version and init callback.
 *
 * @package WooCommerce/Core
 * @since   3.7.0
 */

namespace WooCommerce\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Package class.
 */
class Package {

	/**
	 * Version string for package.
	 *
	 * @var string
	 */
	public $version = '0';

	/**
	 * Callback to init this package.
	 *
	 * @var callable
	 */
	public $callback = '__return_null';

	/**
	 * Path to the package.
	 *
	 * @var string
	 */
	public $path = '';

}
