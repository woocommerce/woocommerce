<?php
/**
 * WooCommerce Block Editor Feature Endabled
 */

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\Features\TransientNotices;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\Loader;
use WP_Block_Editor_Context;

/**
 * Loads assets related to the new product management experience page.
 */
class BlockEditorFeatureEnabled {

	const FEATURE_ID = 'block-editor-feature-enabled';

	/**
	 * Option name used to toggle this feature.
	 */
	const TOGGLE_OPTION_NAME = 'woocommerce_' . self::FEATURE_ID . '_enabled';

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! Features::is_enabled( 'new-product-management-experience' ) && Features::is_enabled( self::FEATURE_ID ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'get_edit_post_link', array( $this, 'update_edit_product_link' ), 10, 2 );
		}
		if ( Features::is_enabled( self::FEATURE_ID ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'woocommerce_register_post_type_product', array( $this, 'add_rest_base_config' ) );
		}
	}

	/**
	 * Enqueue scripts needed for the product form block editor.
	 */
	public function enqueue_scripts() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}
		$post_type_object     = get_post_type_object( 'product' );
		$block_editor_context = new WP_Block_Editor_Context( array( 'name' => 'core/edit-post' ) );

		$editor_settings = array();
		if ( ! empty( $post_type_object->template ) ) {
			$editor_settings['template']     = $post_type_object->template;
			$editor_settings['templateLock'] = ! empty( $post_type_object->template_lock ) ? $post_type_object->template_lock : false;
		}

		$editor_settings = get_block_editor_settings( $editor_settings, $block_editor_context );

		$script_handle = 'wc-admin-edit-product';
		wp_register_script( $script_handle, '', array(), '0.1.0', true );
		wp_enqueue_script( $script_handle );
		wp_add_inline_script(
			$script_handle,
			'var productBlockEditorSettings = productBlockEditorSettings || ' . wp_json_encode( $editor_settings ) . ';',
			'before'
		);
	}

	/**
	 * Enqueue styles needed for the rich text editor.
	 */
	public function enqueue_styles() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}
		wp_enqueue_style( 'wp-edit-blocks' );
		wp_enqueue_style( 'wp-format-library' );
		wp_enqueue_editor();
		/**
		 * Enqueue any block editor related assets.
		 *
		 * @since 7.1.0
		*/
		do_action( 'enqueue_block_editor_assets' );
	}

	/**
	 * Update the edit product links when the new experience is enabled.
	 *
	 * @param string $link    The edit link.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function update_edit_product_link( $link, $post_id ) {
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $link;
		}

		if ( $product->get_type() === 'simple' ) {
			return admin_url( 'admin.php?page=wc-admin&path=/product/' . $product->get_id() );
		}

		return $link;
	}

	/**
	 * Updates the product endpoint to use WooCommerce REST API.
	 *
	 * @param array $post_args Args for the product post type.
	 * @return array
	 */
	public function add_rest_base_config( $post_args ) {
		$post_args['rest_base']      = 'products';
		$post_args['rest_namespace'] = 'wc/v3';
		return $post_args;
	}
}
