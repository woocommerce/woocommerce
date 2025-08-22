<?php
declare( strict_types=1 );
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\ArchiveProductTemplatesCompatibility;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * ProductTagTemplate class.
 *
 * @internal
 */
class ProductTagTemplate extends AbstractTemplateWithFallback {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'taxonomy-product_tag';

	/**
	 * The template used as a fallback if that one is customized.
	 *
	 * @var string
	 */
	public string $fallback_template = ProductCatalogTemplate::SLUG;

	/**
	 * Whether this is a taxonomy template.
	 *
	 * @var bool
	 */
	public bool $is_taxonomy_template = true;

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Products by Tag', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Displays products filtered by a tag.', 'woocommerce' );
	}

	/**
	 * Renders the default block template from Woo Blocks if no theme templates exist.
	 */
	public function render_block_template() {
		if ( ! is_embed() && is_product_taxonomy() && is_tax( 'product_tag' ) ) {
			$compatibility_layer = new ArchiveProductTemplatesCompatibility();
			$compatibility_layer->init();

			$templates = get_block_templates( array( 'slug__in' => array( self::SLUG ) ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}

			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		}
	}
}
