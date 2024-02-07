<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;

/**
 * ProductSearchResultsTemplate class.
 *
 * @internal
 */
class ProductSearchResultsTemplate extends AbstractTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'product-search-results';

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
	 * Initialization method.
	 */
	public function init() {
		$this->template_title       = _x( 'Product Search Results', 'Template name', 'woocommerce' );
		$this->template_description = __( 'Displays search results for your store.', 'woocommerce' );

		add_filter( 'search_template_hierarchy', array( $this, 'update_search_template_hierarchy' ), 10, 3 );
	}

	/**
	 * When the search is for products and a block theme is active, render the Product Search Template.
	 *
	 * @param array $templates Templates that match the search hierarchy.
	 */
	public function update_search_template_hierarchy( $templates ) {
		if ( ( is_search() && is_post_type_archive( 'product' ) ) && wc_current_theme_is_fse_theme() ) {
			array_unshift( $templates, self::SLUG );
		}
		return $templates;
	}
}
