<?php
/**
 * Woo Wizard Class
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Initial_State as Connection_Initial_State;

/**
 * Settings Class.
 */
class Woo_AI_Woo_Wizard {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_woo_ai_assistant_register_script' ) );
	}

	/**
	 * Enqueue the styles and JS
	 */
	public function add_woo_ai_assistant_register_script() {
		$script_path       = '/../build/woo-ai-assistant.js';
		$script_asset_path = dirname( __FILE__ ) . '/../build/woo-ai-assistant.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_path ),
			);
		$script_url        = plugins_url( $script_path, __FILE__ );

		$script_asset['dependencies'][] = WC_ADMIN_APP; // Add WCA as a dependency to ensure it loads first.

		wp_register_script(
			'woo-ai-assistant',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_script( 'woo-ai-assistant' );

		if ( class_exists( '\Automattic\Jetpack\Connection\Initial_State' ) ) {
			wp_add_inline_script( 'woo-ai', Connection_Initial_State::render(), 'before' );
		}
        $css_file_version = filemtime( dirname( __FILE__ ) . '/../build/woo-ai-assistant.css' );

		wp_register_style(
			'woo-ai-assistant',
			plugins_url( '/../build/woo-ai-assistant.css', __FILE__ ),
			// Add any dependencies styles may have, such as wp-components.
			array(
				'wp-components',
			),
			$css_file_version
		);

		wp_enqueue_style( 'woo-ai-assistant' );
	}

}

new Woo_AI_Woo_Wizard();
