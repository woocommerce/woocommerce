<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ProductSearchResultsTemplate class.
 *
 * @internal
 */
class ProductSearchResultsTemplate {

	const SLUG        = 'product-search-results';
	const TITLE       = 'Product Search Results';
	const DESCRIPTION = 'Template used to display search results for products.';

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
		add_filter( 'search_template_hierarchy', array( $this, 'update_search_template_hierarchy' ), 10, 3 );
		add_filter( 'get_block_templates', array( $this, 'set_template_info' ) );
	}

	/**
	 * When the search is for products and a block theme is active, render the Product Search Template.
	 *
	 * @param array $templates Templates that match the search hierarchy.
	 */
	public function update_search_template_hierarchy( $templates ) {
		if ( ( is_search() && is_post_type_archive( 'product' ) ) && wc_current_theme_is_fse_theme() ) {
			return [ self::SLUG ];
		}
		return $templates;
	}

	/**
	 * Update Product Search Template info.
	 *
	 * @param array $templates List of templates.
	 */
	public function set_template_info( $templates ) {
		return array_map(
			function ( $template ) {
				if ( self::SLUG !== $template->slug ) {
					return $template;
				}

				$template->title       = self::TITLE;
				$template->description = self::DESCRIPTION;

				return $template;
			},
			$templates
		);
	}
}
