<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin;

/**
 * Displays a non-dismissible warning notice in the block editor when
 * editing the Shop page, mirroring the WordPress core pattern for the
 * Posts page (blog home).
 *
 * @since 10.9.0
 */
class ShopPageEditor {

	/**
	 * Hook into the block editor load.
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'maybe_add_shop_page_notice' ) );
	}

	/**
	 * Enqueue a warning notice when editing the Shop page.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @since 10.9.0
	 */
	public function maybe_add_shop_page_notice(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'page' !== $screen->post_type ) {
			return;
		}

		$shop_page_id = wc_get_page_id( 'shop' );

		if ( $shop_page_id <= 0 ) {
			return;
		}

		global $post;

		if ( ! $post || (int) $post->ID !== $shop_page_id ) {
			return;
		}

		$template_edit_url = admin_url( 'site-editor.php?postType=wp_template&postId=woocommerce%2Fwoocommerce%2F%2Farchive-product' );

		$notice_text = sprintf(
			/* translators: %s: URL to edit the Product Catalog template in the Site Editor */
			__( 'You are currently editing the Shop page. The layout and content of this page are managed by the <a href="%s">Product Catalog template</a>.', 'woocommerce' ),
			esc_url( $template_edit_url )
		);

		wp_add_inline_script(
			'wp-notices',
			sprintf(
				'wp.data.dispatch( "core/notices" ).createWarningNotice( "%s", { isDismissible: false, __unstableHTML: true } )',
				wp_slash( $notice_text )
			),
			'after'
		);
	}
}
