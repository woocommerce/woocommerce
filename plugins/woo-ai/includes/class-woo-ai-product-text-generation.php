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
class Woo_AI_Product_Text_Generation {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_woo_ai_register_script' ) );
		add_action( 'media_buttons', array( $this, 'add_gpt_button' ), 40 );
		add_action( 'media_buttons', array( $this, 'add_short_description_gpt_button' ), 50 );
		add_action( 'edit_form_before_permalink', array( $this, 'add_name_generation_form' ) );
		add_filter( 'the_editor', array( $this, 'add_gpt_form' ), 10, 1 );
	}

	/**
	 * Enqueue the styles and JS
	 */
	public function add_woo_ai_register_script() {
		$script_path       = '/../build/index.js';
		$script_asset_path = dirname( __FILE__ ) . '/../build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_path ),
			);
		$script_url        = plugins_url( $script_path, __FILE__ );

		$script_asset['dependencies'][] = WC_ADMIN_APP; // Add WCA as a dependency to ensure it loads first.

		wp_register_script(
			'woo-ai',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_script( 'woo-ai' );

		if ( class_exists( '\Automattic\Jetpack\Connection\Initial_State' ) ) {
			wp_add_inline_script( 'woo-ai', Connection_Initial_State::render(), 'before' );
		}

		$css_file_version = filemtime( dirname( __FILE__ ) . '/../build/index.css' );

		wp_register_style(
			'woo-ai',
			plugins_url( '/../build/index.css', __FILE__ ),
			// Add any dependencies styles may have, such as wp-components.
			array(
				'wp-components',
			),
			$css_file_version
		);

		wp_enqueue_style( 'woo-ai' );
	}

	/**
	 * Add gpt button to the editor.
	 *
	 * @param String $editor_id Editor Id.
	 */
	public function add_gpt_button( $editor_id ) {
		if ( 'content' !== $editor_id || ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) ) {
			return;
		}

		echo '<div id="woocommerce-ai-app-product-gpt-button"></div>';
	}

	/**
	 * Add gpt button to the editor.
	 *
	 * @param String $editor_id Editor Id.
	 */
	public function add_short_description_gpt_button( $editor_id ) {
		if ( 'excerpt' !== $editor_id || ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) ) {
			return;
		}

		echo '<div id="woocommerce-ai-app-product-short-description-gpt-button"></div>';
	}

	/**
	 * Add the form and button for generating product title suggestions to the editor.
	 */
	public function add_name_generation_form() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		echo '<div id="woocommerce-ai-app-product-name-suggestions"></div>';
	}

	/**
	 * Add gpt form to the editor.
	 *
	 * @param String $content Gpt form content.
	 */
	public function add_gpt_form( $content ) {
		global $post;

		// Check if the current post type is 'product'.
		if ( 'product' === $post->post_type ) {

			// Check if the content contains the specific editor ID.
			$editor_container_id       = 'wp-content-editor-container';
			$editor_container_position = strpos( $content, $editor_container_id );

			if ( false !== $editor_container_position ) {
				$gpt_form =
					'<div id="woocommerce-ai-app-product-gpt-form"></div>';
				$content  = $gpt_form . $content;
			}
		}

		return $content;
	}

}

new Woo_AI_Product_Text_Generation();
