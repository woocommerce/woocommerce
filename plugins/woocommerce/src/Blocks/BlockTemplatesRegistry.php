<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\Templates\AbstractTemplate;
use Automattic\WooCommerce\Blocks\Templates\AbstractTemplatePart;
use Automattic\WooCommerce\Blocks\Templates\MiniCartTemplate;
use Automattic\WooCommerce\Blocks\Templates\CartTemplate;
use Automattic\WooCommerce\Blocks\Templates\CheckoutTemplate;
use Automattic\WooCommerce\Blocks\Templates\CheckoutHeaderTemplate;
use Automattic\WooCommerce\Blocks\Templates\ComingSoonTemplate;
use Automattic\WooCommerce\Blocks\Templates\ComingSoonEntireSiteTemplate;
use Automattic\WooCommerce\Blocks\Templates\ComingSoonStoreOnlyTemplate;
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
	private $templates = array();

	/**
	 * Initialization method.
	 */
	public function init() {
		if ( BlockTemplateUtils::supports_block_templates( 'wp_template' ) ) {
			$templates = array(
				ProductCatalogTemplate::SLUG       => new ProductCatalogTemplate(),
				ProductCategoryTemplate::SLUG      => new ProductCategoryTemplate(),
				ProductTagTemplate::SLUG           => new ProductTagTemplate(),
				ProductAttributeTemplate::SLUG     => new ProductAttributeTemplate(),
				ProductSearchResultsTemplate::SLUG => new ProductSearchResultsTemplate(),
				CartTemplate::SLUG                 => new CartTemplate(),
				CheckoutTemplate::SLUG             => new CheckoutTemplate(),
				ComingSoonTemplate::SLUG           => new ComingSoonTemplate(),
				OrderConfirmationTemplate::SLUG    => new OrderConfirmationTemplate(),
				SingleProductTemplate::SLUG        => new SingleProductTemplate(),
			);
		} else {
			$templates = array(
				ComingSoonTemplate::SLUG => new ComingSoonTemplate(),
			);
		}
		if ( BlockTemplateUtils::supports_block_templates( 'wp_template_part' ) ) {
			$template_parts = array(
				MiniCartTemplate::SLUG       => new MiniCartTemplate(),
				CheckoutHeaderTemplate::SLUG => new CheckoutHeaderTemplate(),
			);
		} else {
			$template_parts = array();
		}
		$this->templates = array_merge( $templates, $template_parts );

		// Init all templates.
		foreach ( $this->templates as $template ) {
			$template->init();
		}
	}

	/**
	 * Returns the template matching the slug
	 *
	 * @param string $template_slug Slug of the template to retrieve.
	 *
	 * @return AbstractTemplate|AbstractTemplatePart|null
	 */
	public function get_template( $template_slug ) {
		if ( array_key_exists( $template_slug, $this->templates ) ) {
			$registered_template = $this->templates[ $template_slug ];
			return $registered_template;
		}
		return null;
	}
}
