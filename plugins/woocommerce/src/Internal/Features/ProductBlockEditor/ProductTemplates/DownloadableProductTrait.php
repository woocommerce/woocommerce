<?php
/**
 * DownloadableProductTrait
 */

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\GroupInterface;

/**
 * Downloadable Product Trait.
 */
trait DownloadableProductTrait {
	/**
	 * Adds downloadable blocks to the given parent block.
	 *
	 * @param GroupInterface $parent_block The parent block.
	 */
	private function add_downloadable_product_blocks( $parent_block ) {
		// Downloads section.
		if ( Features::is_enabled( 'product-virtual-downloadable' ) ) {
			$product_downloads_section_group = $parent_block->add_section(
				array(
					'id'             => 'product-downloads-section-group',
					'order'          => 50,
					'attributes'     => array(
						'blockGap' => 'unit-40',
					),
					'hideConditions' => array(
						array(
							'expression' => 'postType === "product" && editedProduct.type !== "simple"',
						),
					),
				)
			);

			$product_downloads_section_group->add_block(
				array(
					'id'         => 'product-downloadable',
					'blockName'  => 'woocommerce/product-checkbox-field',
					'order'      => 10,
					'attributes' => array(
						'property' => 'downloadable',
						'label'    => __( 'Include downloads', 'woocommerce' ),
					),
				)
			);

			$product_downloads_section_group->add_subsection(
				array(
					'id'             => 'product-downloads-section',
					'order'          => 20,
					'attributes'     => array(
						'title'       => __( 'Downloads', 'woocommerce' ),
						'description' => sprintf(
							/* translators: %1$s: Downloads settings link opening tag. %2$s: Downloads settings link closing tag. */
							__( 'Add any files you\'d like to make available for the customer to download after purchasing, such as instructions or warranty info. Store-wide updates can be managed in your %1$sproduct settings%2$s.', 'woocommerce' ),
							'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=downloadable' ) . '" target="_blank" rel="noreferrer">',
							'</a>'
						),
					),
					'hideConditions' => array(
						array(
							'expression' => 'editedProduct.downloadable !== true',
						),
					),
				)
			)->add_block(
				array(
					'id'        => 'product-downloads',
					'blockName' => 'woocommerce/product-downloads-field',
					'order'     => 10,
				)
			);
		}
	}
}
