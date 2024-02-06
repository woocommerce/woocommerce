<?php

namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;

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
	 * Constructor.
	 */
	public function __construct() {
		if ( wc_current_theme_is_fse_theme() ) {
			$this->template_title       = _x( 'Product Catalog', 'Template name', 'woocommerce' );
			$this->template_description = __( 'Displays your products.', 'woocommerce' );
			BlockTemplatesRegistry::register_template( $this );
			$this->init();
		}
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		add_filter( 'post_type_archive_title', array( $this, 'update_product_archive_title' ), 10, 2 );
	}

	/**
	 * Update the product archive title to "Shop".
	 *
	 * @param string $post_type_name Post type 'name' label.
	 * @param string $post_type      Post type.
	 *
	 * @return string
	 */
	public function update_product_archive_title( $post_type_name, $post_type ) {
		if (
			function_exists( 'is_shop' ) &&
			is_shop() &&
			'product' === $post_type
		) {
			return __( 'Shop', 'woocommerce' );
		}

		return $post_type_name;
	}
}
