<?php

namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * ProductAttributeTemplate class.
 *
 * @internal
 */
class ProductAttributeTemplate extends AbstractTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	public $slug = 'taxonomy-product_attribute';

	/**
	 * The template used as a fallback if that one is customized.
	 *
	 * @var string
	 */
	public $template_title;

	/**
	 * The description of the template.
	 *
	 * @var string
	 */
	public $template_description;

	/**
	 * The template used as a fallback if that one is customized.
	 *
	 * @var string
	 */
	public $fallback_template = 'archive-product';

	/**
	 * Initialization method.
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'render_block_template' ) );
		add_filter( 'taxonomy_template_hierarchy', array( $this, 'update_taxonomy_template_hierarchy' ), 1, 3 );
	}

	/**
	 * Renders the default block template from Woo Blocks if no theme templates exist.
	 */
	public function render_block_template() {
		$queried_object = get_queried_object();
		if ( is_null( $queried_object ) ) {
			return;
		}

		if ( isset( $queried_object->taxonomy ) && taxonomy_is_product_attribute( $queried_object->taxonomy ) ) {
			$templates = get_block_templates( array( 'slug__in' => array( $this->slug ) ) );

			if ( isset( $templates[0] ) && BlockTemplateUtils::template_has_legacy_template_block( $templates[0] ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}

			add_filter( 'woocommerce_has_block_template', '__return_true', 10, 0 );
		}
	}

	/**
	 * Renders the Product by Attribute template for product attributes taxonomy pages.
	 *
	 * @param array $templates Templates that match the product attributes taxonomy.
	 */
	public function update_taxonomy_template_hierarchy( $templates ) {
		$queried_object = get_queried_object();
		if ( taxonomy_is_product_attribute( $queried_object->taxonomy ) && wc_current_theme_is_fse_theme() ) {
			array_splice( $templates, count( $templates ) - 1, 0, $this->slug );
		}

		return $templates;
	}
}
