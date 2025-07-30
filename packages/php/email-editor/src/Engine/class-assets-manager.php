<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

/**
 * Class responsible for managing email editor assets.
 */
class Assets_Manager {
	/**
	 * Initialize the assets manager.
	 */
	public function initialize(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Enqueue admin styles that are needed by the email editor.
	 */
	public function enqueue_admin_styles(): void {
		// Calling action that loads registered blockTypes.
		do_action( 'enqueue_block_editor_assets' );

		// Load CSS from Post Editor.
		wp_enqueue_style( 'wp-edit-post' );
		// Load CSS for the format library - used for example in popover.
		wp_enqueue_style( 'wp-format-library' );
		// Enqueue CSS containing --wp--preset variables.
		wp_enqueue_global_styles_css_custom_properties();

		// Enqueue media library scripts.
		wp_enqueue_media();
	}
}
