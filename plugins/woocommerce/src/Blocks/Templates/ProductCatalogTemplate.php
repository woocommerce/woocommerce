<?php

namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\ArchiveProductTemplatesCompatibility;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * ProductCatalogTemplate class.
 *
 * @internal
 */
class ProductCatalogTemplate extends AbstractTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'archive-product';

	/**
	 * Initialization method.
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'render_block_template' ) );
		add_filter( 'current_theme_supports-block-templates', array( $this, 'remove_block_template_support_for_shop_page' ) );
		add_action( 'admin_init', array( $this, 'redirect_shop_page_to_product_catalog_template' ) );
	}

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Product Catalog', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Displays your products.', 'woocommerce' );
	}

	/**
	 * Run template-specific logic when the query matches this template.
	 */
	public function render_block_template() {
		if ( ! is_embed() && ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) && ! is_search() ) {
			$compatibility_layer = new ArchiveProductTemplatesCompatibility();
			$compatibility_layer->init();

			$templates = get_block_templates( array( 'slug__in' => array( self::SLUG ) ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}
		}
	}

	/**
	 * Remove the template panel from the Sidebar of the Shop page because
	 * the Site Editor handles it.
	 *
	 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/6278
	 *
	 * @param bool $is_support Whether the active theme supports block templates.
	 *
	 * @return bool
	 */
	public function remove_block_template_support_for_shop_page( $is_support ) {
		global $pagenow, $post;

		if (
			is_admin() &&
			'post.php' === $pagenow &&
			function_exists( 'wc_get_page_id' ) &&
			is_a( $post, 'WP_Post' ) &&
			wc_get_page_id( 'shop' ) === $post->ID
		) {
			return false;
		}

		return $is_support;
	}

	/**
	 * Returns the Site Editor URL for the Product Catalog template if the current
	 * request is editing the Shop page on a block theme.
	 *
	 * @since 10.7.0
	 *
	 * @return string|null The Site Editor URL, or null if conditions are not met.
	 */
	public function get_product_catalog_template_editor_url() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( 'edit' !== ( $_GET['action'] ?? '' ) || empty( $_GET['post'] ) ) {
			return null;
		}

		$post_id      = absint( $_GET['post'] );
		$shop_page_id = wc_get_page_id( 'shop' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $post_id !== $shop_page_id ) {
			return null;
		}

		if ( ! wp_is_block_theme() || ! current_user_can( 'edit_theme_options' ) ) {
			return null;
		}

		$template_source = BlockTemplateUtils::theme_has_template( self::SLUG )
			? wp_get_theme()->get_stylesheet()
			: BlockTemplateUtils::PLUGIN_SLUG;

		return add_query_arg(
			array(
				'postId'   => $template_source . '//' . self::SLUG,
				'postType' => 'wp_template',
				'canvas'   => 'edit',
			),
			admin_url( 'site-editor.php' )
		);
	}

	/**
	 * Redirects the Shop page editor to the Product Catalog template in the Site Editor.
	 *
	 * @since 10.7.0
	 *
	 * @return void
	 */
	public function redirect_shop_page_to_product_catalog_template() {
		$redirect_url = $this->get_product_catalog_template_editor_url();

		if ( $redirect_url ) {
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}
}
