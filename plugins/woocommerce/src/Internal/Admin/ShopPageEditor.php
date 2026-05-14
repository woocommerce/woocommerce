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
		add_filter( 'display_post_states', array( $this, 'add_shop_page_state' ), 10, 2 );
		add_filter( 'theme_page_templates', array( $this, 'hide_shop_page_templates' ), 10, 4 );
		add_filter( 'block_editor_settings_all', array( $this, 'lock_shop_page_template_selector' ), 10, 2 );
		add_action( 'admin_bar_menu', array( $this, 'add_shop_page_edit_link' ), 80 );
	}

	/**
	 * Enqueue a warning notice when editing the Shop page.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @since 10.9.0
	 */
	public function maybe_add_shop_page_notice(): void {
		if ( ! $this->is_shop_page_block_editor() ) {
			return;
		}

		$shop_page_id = wc_get_page_id( 'shop' );

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

	/**
	 * Add a Shop Page state label in the page list table and editor header.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @since 10.9.0
	 *
	 * @param array<string, string> $post_states  Post states.
	 * @param \WP_Post              $post         Post object.
	 * @return array<string, string>
	 */
	public function add_shop_page_state( array $post_states, \WP_Post $post ): array {
		if ( $this->is_shop_page_id( (int) $post->ID ) ) {
			$post_states['woocommerce_shop_page'] = _x( 'Shop page', 'page label', 'woocommerce' );
		}

		return $post_states;
	}

	/**
	 * Remove page template choices for the Shop page, matching the Posts page behavior.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @since 10.9.0
	 *
	 * @param array<string, string> $page_templates  Page templates.
	 * @param \WP_Theme             $theme           Theme object.
	 * @param \WP_Post|null         $post            Post object.
	 * @param string                $post_type       Post type.
	 * @return array<string, string>
	 */
	public function hide_shop_page_templates( array $page_templates, \WP_Theme $theme, ?\WP_Post $post, string $post_type ): array {
		if ( 'page' === $post_type && $post && $this->is_shop_page_id( (int) $post->ID ) ) {
			return array();
		}

		return $page_templates;
	}

	/**
	 * Disable the block editor template selector for the Shop page.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @since 10.9.0
	 *
	 * @param array<string, mixed>     $editor_settings Block editor settings.
	 * @param \WP_Block_Editor_Context $editor_context  Block editor context.
	 * @return array<string, mixed>
	 */
	public function lock_shop_page_template_selector( array $editor_settings, \WP_Block_Editor_Context $editor_context ): array {
		if ( ! empty( $editor_context->post ) && $this->is_shop_page_id( (int) $editor_context->post->ID ) ) {
			$editor_settings['availableTemplates'] = array();
		}

		return $editor_settings;
	}

	/**
	 * Add an admin bar link to edit the Shop page from the frontend.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @since 10.9.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function add_shop_page_edit_link( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( is_admin() || ! is_shop() ) {
			return;
		}

		$shop_page_id = wc_get_page_id( 'shop' );

		if ( $shop_page_id <= 0 || ! current_user_can( 'edit_post', $shop_page_id ) ) {
			return;
		}

		$edit_link = get_edit_post_link( $shop_page_id );

		if ( ! $edit_link ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'edit',
				'title' => __( 'Edit page', 'woocommerce' ),
				'href'  => $edit_link,
			)
		);
	}

	/**
	 * Check if the current block editor screen edits the Shop page.
	 *
	 * @return bool
	 */
	private function is_shop_page_block_editor(): bool {
		$screen = get_current_screen();

		if ( ! $screen || 'page' !== $screen->post_type ) {
			return false;
		}

		global $post;

		return $post instanceof \WP_Post && $this->is_shop_page_id( (int) $post->ID );
	}

	/**
	 * Check whether a post ID is the configured Shop page.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_shop_page_id( int $post_id ): bool {
		$shop_page_id = wc_get_page_id( 'shop' );

		return $shop_page_id > 0 && $post_id === $shop_page_id;
	}
}
