<?php

namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;

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
			$this->template_title       = _x( 'Products by Category', 'Template name', 'woocommerce' );
			$this->template_description = __( 'Displays products filtered by a category.', 'woocommerce' );
			BlockTemplatesRegistry::register_template( $this );
		}
	}
}
