<?php
declare( strict_types = 1 );

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
		add_filter( 'get_block_templates', array( $this, 'handle_get_block_templates' ), 20, 3 );
		add_filter( 'get_post_metadata', array( $this, 'handle_get_post_metadata' ), 10, 4 );
	}

	/**
	 * Use the Product Catalog template for the Shop page without changing its saved template.
	 *
	 * @internal
	 *
	 * @param mixed  $value    Short-circuited metadata value, or null.
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Metadata key.
	 * @param bool   $single   Whether to return a single value.
	 * @return mixed
	 */
	public function handle_get_post_metadata( $value, $post_id, $meta_key, $single ) {
		if (
			null === $value &&
			'_wp_page_template' === $meta_key &&
			wp_is_block_theme() &&
			wc_get_page_id( 'shop' ) === $post_id
		) {
			return $single ? self::SLUG : array( self::SLUG );
		}

		return $value;
	}

	/**
	 * Limit the Shop page's editor template choices to the Product Catalog.
	 *
	 * @internal
	 *
	 * @param \WP_Block_Template[] $templates Available templates.
	 * @param array                $query Template query arguments.
	 * @param string               $template_type Template type.
	 * @return \WP_Block_Template[]
	 */
	public function handle_get_block_templates( $templates, $query, $template_type ) {
		if (
			'wp_template' === $template_type &&
			is_array( $query ) &&
			isset( $query['slug'] ) &&
			is_string( $query['slug'] ) &&
			'' !== $query['slug'] &&
			get_post_field( 'post_name', wc_get_page_id( 'shop' ) ) === $query['slug'] &&
			wp_is_block_theme()
		) {
			// Query without the page slug to preserve customized WooCommerce templates' precedence.
			$catalog_templates = get_block_templates( array( 'slug__in' => array( self::SLUG ) ), 'wp_template' );
			foreach ( $catalog_templates as $template ) {
				if ( $template instanceof \WP_Block_Template && self::SLUG === $template->slug ) {
					return array( $template );
				}
			}
		}

		return $templates;
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
		}
	}

	/**
	 * Hide Shop page template controls for classic themes.
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
			! wp_is_block_theme() &&
			function_exists( 'wc_get_page_id' ) &&
			is_a( $post, 'WP_Post' ) &&
			wc_get_page_id( 'shop' ) === $post->ID
		) {
			return false;
		}

		return $is_support;
	}
}
