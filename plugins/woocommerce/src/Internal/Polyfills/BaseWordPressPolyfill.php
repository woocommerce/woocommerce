<?php
/**
 * BaseWordPressPolyfill class file.
 */

namespace Automattic\WooCommerce\Internal\Compatibility;

/**
 * Base class for WordPress polyfills.
 */
abstract class BaseWordPressPolyfill {
	/**
	 * The current WordPress version.
	 *
	 * @var string
	 */
	protected string $wordpress_version;

	/**
	 * The current Gutenberg version if it is active.
	 *
	 * @var null|string
	 */
	protected ?string $gutenberg_version;

	/**
	 * Registers the WordPress compatibility override.
	 *
	 * @param string      $wordpress_version The current WordPress version.
	 * @param null|string $gutenberg_version The current Gutenberg version if it is active.
	 */
	final public function register( string $wordpress_version, ?string $gutenberg_version ) {
		$this->wordpress_version = $wordpress_version;
		$this->gutenberg_version = $gutenberg_version;
		add_action( 'plugins_loaded', array( $this, 'add_polyfill' ) );
	}

	/**
	 * Adds compatibility for an unsupported WordPress feature.
	 */
	abstract public function add_polyfill();
}
