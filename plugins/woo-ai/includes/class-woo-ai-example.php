<?php
/**
 * Beta Tester Plugin example feature class.
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Woo_AI example Feature Class.
 */
class Woo_AI_Example {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ), 15 );
	}

	/**
	 * Register example scripts.
	 */
	public function load_scripts() {
		if ( ! defined( 'WC_ADMIN_APP' ) ) {
			return;
		}

		wp_add_inline_script( WC_ADMIN_APP, 'window.wcPhotoRoomKey = "' . PHOTOROOM_KEY . '" ;', 'before' );
	}

	/**
	 * Register example scripts.
	 */
	public function register_scripts() {
		if ( ! is_admin() ) {
			return;
		}

		$script_path       = '/build/example.js';
		$script_asset_path = dirname( __FILE__ ) . '/../build/example.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_path ),
			);
		$script_url        = Woo_AI::instance()->plugin_url() . $script_path;

		wp_register_script(
			'woo-ai-example',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_script( 'woo-ai-example' );
	}

	/**
	 * Register example page.
	 */
	public function register_page() {
		if ( ! function_exists( 'wc_admin_register_page' ) ) {
			return;
		}

		wc_admin_register_page(
			array(
				'id'         => 'woo-ai-example',
				// phpcs:disable
				'title'      => __( 'Woo AI Example', 'woo-ai' ),
				'path'       => '/woo-ai-example',
				'parent'     => 'woocommerce',
				'capability' => 'read',
			)
		);
	}
}

return new Woo_AI_Example();
