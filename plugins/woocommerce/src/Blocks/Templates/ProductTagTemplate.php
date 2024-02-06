<?php

namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;

/**
 * ProductTagTemplate class.
 *
 * @internal
 */
class ProductTagTemplate extends AbstractTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'taxonomy-product_tag';

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
			$this->template_title       = _x( 'Products by Tag', 'Template name', 'woocommerce' );
			$this->template_description = __( 'Displays products filtered by a tag.', 'woocommerce' );
			BlockTemplatesRegistry::register_template( $this );
		}
	}
}
