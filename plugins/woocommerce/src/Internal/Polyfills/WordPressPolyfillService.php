<?php
/**
 * WordPressPolyfillService class file.
 */

namespace Automattic\WooCommerce\Internal\Polyfills;

use Automattic\WooCommerce\Internal\Polyfills\BlockSupports\WordPressLayoutPolyfill;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * The service for registering WordPress polyfills.
 */
class WordPressPolyfillService implements RegisterHooksInterface {
	/**
	 * The polyfills to register.
	 *
	 * @var string[]
	 */
	protected $polyfills = array(
		WordPressLayoutPolyfill::class,
	);

	/**
	 * Registers the service.
	 */
	public function register() {
		global $wp_version;
		$gutenberg_version = defined( 'GUTENBERG_VERSION' ) ? GUTENBERG_VERSION : null;

		foreach ( $this->polyfills as $polyfill ) {
			$polyfill_instance = new $polyfill();
			$polyfill_instance->register( $wp_version, $gutenberg_version );
		}
	}
}
