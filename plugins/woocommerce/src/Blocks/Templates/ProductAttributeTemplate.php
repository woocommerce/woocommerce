<?php

namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;

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
	const SLUG = 'taxonomy-product_attribute';

	/**
	 * The title of the template.
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
	public $fallback_template = ProductCatalogTemplate::SLUG;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( wc_current_theme_is_fse_theme() ) {
			$this->template_title       = _x( 'Products by Attribute', 'Template name', 'woocommerce' );
			$this->template_description = __( 'Displays products filtered by an attribute.', 'woocommerce' );
			BlockTemplatesRegistry::register_template( $this );
			$this->init();
		}
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		add_filter( 'taxonomy_template_hierarchy', array( $this, 'update_taxonomy_template_hierarchy' ), 1, 3 );
	}

	/**
	 * Renders the Product by Attribute template for product attributes taxonomy pages.
	 *
	 * @param array $templates Templates that match the product attributes taxonomy.
	 */
	public function update_taxonomy_template_hierarchy( $templates ) {
		$queried_object = get_queried_object();
		if ( taxonomy_is_product_attribute( $queried_object->taxonomy ) && wc_current_theme_is_fse_theme() ) {
			array_splice( $templates, count( $templates ) - 1, 0, self::SLUG );
		}

		return $templates;
	}
}
