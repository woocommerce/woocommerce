<?php
declare( strict_types=1 );
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\ArchiveProductTemplatesCompatibility;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * ProductAttributeTemplate class.
 *
 * @internal
 */
class ProductAttributeTemplate extends AbstractTemplateWithFallback {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'taxonomy-product_attribute';

	/**
	 * The template used as a fallback if that one is customized.
	 *
	 * @var string
	 */
	public string $fallback_template = ProductCatalogTemplate::SLUG;

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Products by Attribute', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Displays products filtered by an attribute.', 'woocommerce' );
	}

	/**
	 * Run template-specific logic when the query matches this template.
	 */
	public function render_block_template() {
		$queried_object = get_queried_object();
		if ( is_null( $queried_object ) ) {
			return;
		}

		if ( isset( $queried_object->taxonomy ) && taxonomy_is_product_attribute( $queried_object->taxonomy ) ) {
			$compatibility_layer = new ArchiveProductTemplatesCompatibility();
			$compatibility_layer->init();

			$templates = get_block_templates( array( 'slug__in' => array( self::SLUG ) ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}
		}
	}

	/**
	 * Renders the Product by Attribute template for product attributes taxonomy pages.
	 *
	 * @param array $templates Templates that match the product attributes taxonomy.
	 */
	public function template_hierarchy( $templates ) {
		$queried_object = get_queried_object();

		if ( ! is_null( $queried_object ) && taxonomy_is_product_attribute( $queried_object->taxonomy ) && wp_is_block_theme() ) {
			// If Products by Attribute template has been customized or it's in the
			// theme, we load it first, otherwise we only load the fallback template.
			// If we don't do that, the WC core template would always have priority
			// over the fallback template.
			$slugs = array( $this->fallback_template );

			if (
				BlockTemplateUtils::theme_has_template( self::SLUG ) ||
				BlockTemplateUtils::get_block_templates_from_db( array( self::SLUG ) )
			) {
				$slugs = array( self::SLUG, $this->fallback_template );
			}

			array_splice( $templates, count( $templates ) - 1, 0, $slugs );
		}

		return $templates;
	}
}
