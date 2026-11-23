<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration -- Preserve scalar coercion for compatibility with extension-provided data.

namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use Automattic\WooCommerce\Blocks\Package;

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
		add_filter( 'get_block_templates', array( $this, 'handle_get_block_templates_for_post_content' ), 20, 3 );
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
	 * Ensure WordPress can read the first Product Catalog template at index 0.
	 * See https://core.trac.wordpress.org/ticket/62407
	 *
	 * @internal
	 *
	 * @param \WP_Block_Template[] $templates Available templates.
	 * @param array                $query Template query arguments.
	 * @param string               $template_type Template type.
	 * @return \WP_Block_Template[]
	 */
	public static function handle_get_block_templates_for_post_content( $templates, $query, $template_type ) {
		if (
			'wp_template' === $template_type &&
			is_array( $templates ) &&
			is_array( $query['slug__in'] ?? null ) &&
			in_array( self::SLUG, $query['slug__in'], true )
		) {
			return array_values( $templates );
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
			Package::container()->get( BlockTemplatesRegistry::class )->init_archive_compatibility();
		}
	}

	/**
	 * Hide Shop page template controls for classic themes.
	 *
	 * @deprecated 11.3.0
	 *
	 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/6278
	 *
	 * @param bool $is_support Whether the active theme supports block templates.
	 * @return void
	 */
	public function remove_block_template_support_for_shop_page( $is_support ) {
		wc_deprecated_function( __METHOD__, '11.3.0' );
	}
}
