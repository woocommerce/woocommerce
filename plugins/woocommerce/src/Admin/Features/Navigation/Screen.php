<?php
/**
 * WooCommerce Navigation Screen
 *
 * @package Woocommerce Navigation
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use Automattic\WooCommerce\Admin\Features\Navigation\Init;

/**
 * Contains logic for the WooCommerce Navigation menu.
 */
class Screen {
	/**
	 * Class instance.
	 *
	 * @var Screen instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	final public static function instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Init.
	 */
	public function init() {
		return;
	}

	/**
	 * Adds a screen ID to the list of screens that use the navigtion.
	 * Finds the parent if none is given to grab the correct screen ID.
	 *
	 * @param string      $callback Callback or URL for page.
	 * @param string|null $parent   Parent screen ID.
	 */
	public static function add_screen( $callback, $parent = null ) {
		Init::deprecation_notice( 'Screen::add_screen' );

		return;
	}

	/**
	 * Register post type for use in WooCommerce Navigation screens.
	 *
	 * @param string $post_type Post type to add.
	 */
	public static function register_post_type( $post_type ) {
		Init::deprecation_notice( 'Screen::register_post_type' );

		return;
	}

	/**
	 * Register taxonomy for use in WooCommerce Navigation screens.
	 *
	 * @param string $taxonomy Taxonomy to add.
	 */
	public static function register_taxonomy( $taxonomy ) {
		Init::deprecation_notice( 'Screen::register_taxonomy' );

		return;
	}
}
