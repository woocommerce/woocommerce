<?php

namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ProductAttributeTemplate class.
 *
 * @internal
 */
class ProductAttributeTemplate {
	const SLUG = 'archive-product';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		add_filter( 'taxonomy_template_hierarchy', array( $this, 'update_taxonomy_template_hierarchy' ), 10, 3 );
	}

	/**
	 * Render the Archive Product Template for product attributes.
	 *
	 * @param array $templates Templates that match the product attributes taxonomy.
	 */
	public function update_taxonomy_template_hierarchy( $templates ) {
		$queried_object = get_queried_object();
		if ( taxonomy_is_product_attribute( $queried_object->taxonomy ) && wc_current_theme_is_fse_theme() ) {
			array_unshift( $templates, self::SLUG );
		}

		return $templates;
	}
}
