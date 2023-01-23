<?php
/**
 * WooCommerce New Product Management Experience
 */

namespace Automattic\WooCommerce\Admin\Features;

use \Automattic\WooCommerce\Admin\Features\TransientNotices;
use \Automattic\WooCommerce\Admin\PageController;
use \Automattic\WooCommerce\Internal\Admin\Loader;

/**
 * Loads assets related to the new product management experience page.
 */
class NewProductManagementExperience {

	/**
	 * Option name used to toggle this feature.
	 */
	const TOGGLE_OPTION_NAME = 'woocommerce_new_product_management_enabled';

	/**
	 * Constructor
	 */
	public function __construct() {
		$new_product_experience_param = 'new-product-experience-disabled';
		if ( isset( $_GET[ $new_product_experience_param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https://' : 'http://';

			$url .= isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$url  = preg_replace( '/(&|\?)' . preg_quote( $new_product_experience_param ) . '=[^&]*$/', '', $url );
			$url  = preg_replace( '/(&|\?)' . preg_quote( $new_product_experience_param ) . '=[^&]*&/', '$1', $url );

			wp_safe_redirect( $url );

			TransientNotices::add(
				array(
					'user_id' => get_current_user_id(),
					'id'      => 'new-product-experience-disbled',
					'status'  => 'success',
					'content' => __( '🌟‎ ‎ Thanks for the feedback. We’ll put it to good use!', 'woocommerce' ),
				)
			);
		}
		if ( ! Features::is_enabled( 'new-product-management-experience' ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'get_edit_post_link', array( $this, 'update_edit_product_link' ), 10, 2 );
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

}
