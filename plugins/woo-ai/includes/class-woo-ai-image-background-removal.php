<?php
/**
 * Woo AI Image Background Removal Class
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Initial_State as Connection_Initial_State;

/**
 * Class to handle image background removal operations.
 */
class Woo_AI_Image_Background_Removal {

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
		$screen = get_current_screen();

		if ( $screen->base !== 'upload' ) {
			return;
		}

		$script_path       = '/../build/index.js';
		$script_asset_path = dirname( __FILE__ ) . '/../build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array('woo-ai-background-removal-pre'),
				'version'      => filemtime( $script_path ),
			);
		$script_url        = plugins_url( $script_path, __FILE__ );

		wp_register_script(
			'woo-ai',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			false
		);

		wp_enqueue_script( 'woo-ai' );

		if ( class_exists( '\Automattic\Jetpack\Connection\Initial_State' ) ) {
			wp_add_inline_script( 'woo-ai', Connection_Initial_State::render(), 'before' );
		}

		$css_file_version = filemtime( dirname( __FILE__ ) . '/../build/index.css' );

		wp_register_style(
			'woo-ai',
			plugins_url( '/../build/index.css', __FILE__ ),
			array( 'wp-components', ),
			$css_file_version
		);

		wp_enqueue_style( 'woo-ai' );
	}

}

new Woo_AI_Image_Background_Removal();