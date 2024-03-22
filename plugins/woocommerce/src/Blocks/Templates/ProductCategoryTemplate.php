<?php

namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * ProductCategoryTemplate class.
 *
 * @internal
 */
class ProductCategoryTemplate extends AbstractTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'taxonomy-product_cat';

	/**
	 * The template used as a fallback if that one is customized.
	 *
	 * @var string
	 */
	public $fallback_template = ProductCatalogTemplate::SLUG;

	/**
	 * Initialization method.
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'render_block_template' ) );
	}

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Products by Category', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Displays products filtered by a category.', 'woocommerce' );
	}

	/**
	 * Renders the default block template from Woo Blocks if no theme templates exist.
	 */
	public function render_block_template() {
		if ( ! is_embed() && is_product_taxonomy() && is_tax( 'product_cat' ) ) {
			$templates = get_block_templates( array( 'slug__in' => array( self::SLUG ) ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}

			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		}
	}
}
