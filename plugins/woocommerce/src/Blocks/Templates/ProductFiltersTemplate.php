<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * ProductFiltersTemplate class.
 *
 * @internal
 */
class ProductFiltersTemplate extends AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'product-filters';

	/**
	 * The template part area where the template part belongs.
	 *
	 * @var string
	 */
	public $template_area = 'uncategorized';

	/**
	 * Initialization method.
	 */
	public function init() {
		add_filter( 'get_block_type_variations', array( $this, 'register_block_type_variation' ), 10, 2 );
	}

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Product Filters (Experimental)', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __(
			'This is the template part for the product filters displayed on different pages across your store.',
			'woocommerce'
		);
	}

	/**
	 * Add variation for this template part to make it available in the block inserter.
	 *
	 * @param array         $variations Array of registered variations for a block type.
	 * @param WP_Block_Type $block_type The full block type object.
	 */
	public function register_block_type_variation( $variations, $block_type ) {
		if ( 'core/template-part' !== $block_type->name ) {
			return $variations;
		}

		// If template part is modified, Core will pick it up and register a variation
		// for it. Check if the variation already exists before adding it.
		foreach ( $variations as $variation ) {
			if ( ! empty( $variation['attributes']['slug'] ) && 'product-filters' === $variation['attributes']['slug'] ) {
					return $variations;
			}
		}

		$theme = 'woocommerce/woocommerce';
		// Check if current theme overrides this template part.
		if ( BlockTemplateUtils::theme_has_template_part( 'product-filters' ) ) {
			$theme = wp_get_theme()->get( 'TextDomain' );
		}

		$variations[] = array(
			'name'        => 'file_' . self::SLUG,
			'title'       => $this->get_template_title(),
			'description' => true,
			'attributes'  => array(
				'slug'  => self::SLUG,
				'theme' => $theme,
				'area'  => $this->template_area,
			),
			'scope'       => array( 'inserter' ),
			'icon'        => 'layout',
		);
		return $variations;
	}
}
