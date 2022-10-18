<?php
/**
 * WooCommerce New Product Management Experience
 */

namespace Automattic\WooCommerce\Admin\Features;

use \Automattic\WooCommerce\Internal\Admin\Loader;

/**
 * Loads assets related to the new product management experience page.
 */
class NewProductManagementExperience {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue styles needed for the rich text editor.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'wp-edit-post' );
		wp_enqueue_style( 'wp-format-library' );
		wp_enqueue_editor();
		/**
		 * Enqueue any block editor related assets.
		 *
		 * @since 7.1.0
		*/
		do_action( 'enqueue_block_editor_assets' );
	}

}
