<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\Templates\AbstractTemplate;
use Automattic\WooCommerce\Blocks\Templates\AbstractTemplatePart;
use Automattic\WooCommerce\Blocks\Templates\MiniCartTemplate;
use Automattic\WooCommerce\Blocks\Templates\CartTemplate;
use Automattic\WooCommerce\Blocks\Templates\CheckoutTemplate;
use Automattic\WooCommerce\Blocks\Templates\CheckoutHeaderTemplate;
use Automattic\WooCommerce\Blocks\Templates\OrderConfirmationTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductAttributeTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductCatalogTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductCategoryTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductTagTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductSearchResultsTemplate;
use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplate;

/**
 * BlockTemplatesRegistry class.
 *
 * @internal
 */
class BlockTemplatesRegistry {

	/**
	 * The array of registered templates.
	 *
	 * @var AbstractTemplate[]|AbstractTemplatePart[]
	 */
	private static $templates = array();

	/**
	 * Registers all templates used by WooCommerce.
	 */
	public static function register_templates() {
		if ( BlockTemplateUtils::supports_block_templates( 'wp_template' ) ) {
			$templates = array(
				ProductCatalogTemplate::SLUG       => new ProductCatalogTemplate(),
				ProductCategoryTemplate::SLUG      => new ProductCategoryTemplate(),
				ProductTagTemplate::SLUG           => new ProductTagTemplate(),
				ProductAttributeTemplate::SLUG     => new ProductAttributeTemplate(),
				ProductSearchResultsTemplate::SLUG => new ProductSearchResultsTemplate(),
				CartTemplate::SLUG                 => new CartTemplate(),
				CheckoutTemplate::SLUG             => new CheckoutTemplate(),
				OrderConfirmationTemplate::SLUG    => new OrderConfirmationTemplate(),
				SingleProductTemplate::SLUG        => new SingleProductTemplate(),
			);
		} else {
			$templates = array();
		}
		if ( BlockTemplateUtils::supports_block_templates( 'wp_template_part' ) ) {
			$template_parts = array(
				MiniCartTemplate::SLUG       => new MiniCartTemplate(),
				CheckoutHeaderTemplate::SLUG => new CheckoutHeaderTemplate(),
			);
		} else {
			$template_parts = array();
		}
		self::$templates = array_merge( $templates, $template_parts );
	}

	/**
	 * Returns the template matching the slug
	 *
	 * @param string $template_slug Slug of the template to retrieve.
	 *
	 * @return AbstractTemplate|AbstractTemplatePart|null
	 */
	public static function get_template( $template_slug ) {
		if ( count( self::$templates ) === 0 ) {
			self::register_templates();
		}
		if ( array_key_exists( $template_slug, self::$templates ) ) {
			$registered_template = self::$templates[ $template_slug ];
			return $registered_template;
		}
		return null;
	}
}
