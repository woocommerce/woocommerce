<?php
/**
 * Woo AI Product Text Generation Class
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Initial_State as Connection_Initial_State;

/**
 * Settings Class.
 */
class Woo_AI_New_Product_AI_Assistant {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_woo_ai_register_script' ) );
	}

	/**
	 * Enqueue the styles and JS
	 */
	public function add_woo_ai_register_script() {
		$script_path       = '/../build/product-editor-extension.js';
		$script_asset_path = dirname( __FILE__ ) . '/../build/product-editor-extension.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_path ),
			);
		$script_url        = plugins_url( $script_path, __FILE__ );

		error_log( '$script_url: ' . print_r( $script_url, true ) );

		$script_asset['dependencies'][] = WC_ADMIN_APP; // Add WCA as a dependency to ensure it loads first.

		wp_register_script(
			'woo-ai-product-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_script( 'woo-ai-product-editor' );

		if ( class_exists( '\Automattic\Jetpack\Connection\Initial_State' ) ) {
			wp_add_inline_script( 'woo-ai-product-editor', Connection_Initial_State::render(), 'before' );
		}

		$css_file_version = filemtime( dirname( __FILE__ ) . '/../build/product-editor-extension.css' );

		wp_register_style(
			'woo-ai-product-editor',
			plugins_url( '/../build/product-editor-extension.css', __FILE__ ),
			// Add any dependencies styles may have, such as wp-components.
			array(
				'wp-components',
			),
			$css_file_version
		);

		wp_enqueue_style( 'woo-ai-product-editor' );
	}
}

new Woo_AI_New_Product_AI_Assistant();
