<?php
/**
 * General
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\Templates;

class GeneralBlocksHelper {
    /**
     * Group ID.
     */
    const ID = 'general';

    public function __construct( $template ) {
        $this->template = $template;
        $this->add_group();
        $this->add_blocks();
    }

    /**
     * Add the group.
     */
    private function add_group() {
        $this->template->add_group(
            [
                'id'         => self::ID,
                'order'      => 10,
                'attributes' => [
                    'title' => __( 'General', 'woocommerce' ),
                ],
            ]
        );
    }

    /**
	 * Adds the general group blocks to the template.
	 */
	private function add_blocks() {
		$general_group = $this->template->get_group_by_id( self::ID );
		// Basic Details Section.
		$basic_details = $general_group->add_section(
			[
				'id'         => 'basic-details',
				'order'      => 10,
				'attributes' => [
					'title'       => __( 'Basic details', 'woocommerce' ),
					'description' => __( 'This info will be displayed on the product page, category pages, social media, and search results.', 'woocommerce' ),
				],
			]
		);
		$basic_details->add_block(
			[
				'id'         => 'product-name',
				'blockName'  => 'woocommerce/product-name-field',
				'order'      => 10,
				'attributes' => [
					'name'      => 'Product name',
					'autoFocus' => true,
				],
			]
		);
		$basic_details->add_block(
			[
				'id'        => 'product-summary',
				'blockName' => 'woocommerce/product-summary-field',
				'order'     => 20,
			]
		);
		$pricing_columns  = $basic_details->add_block(
			[
				'id'        => 'product-pricing-columns',
				'blockName' => 'core/columns',
				'order'     => 30,
			]
		);
		$pricing_column_1 = $pricing_columns->add_block(
			[
				'id'         => 'product-pricing-column-1',
				'blockName'  => 'core/column',
				'order'      => 10,
				'attributes' => [
					'templateLock' => 'all',
				],
			]
		);
		$pricing_column_1->add_block(
			[
				'id'         => 'product-regular-price',
				'blockName'  => 'woocommerce/product-regular-price-field',
				'order'      => 10,
				'attributes' => [
					'name'  => 'regular_price',
					'label' => __( 'List price', 'woocommerce' ),
					/* translators: PricingTab: This is a link tag to the pricing tab. */
					'help'  => __( 'Manage more settings in <PricingTab>Pricing.</PricingTab>', 'woocommerce' ),
				],
			]
		);
		$pricing_column_2 = $pricing_columns->add_block(
			[
				'id'         => 'product-pricing-column-2',
				'blockName'  => 'core/column',
				'order'      => 20,
				'attributes' => [
					'templateLock' => 'all',
				],
			]
		);
		$pricing_column_2->add_block(
			[
				'id'         => 'product-sale-price',
				'blockName'  => 'woocommerce/product-sale-price-field',
				'order'      => 10,
				'attributes' => [
					'label' => __( 'Sale price', 'woocommerce' ),
				],
			]
		);

		// Description section.
		$description_section = $general_group->add_section(
			[
				'id'         => 'product-description-section',
				'order'      => 20,
				'attributes' => [
					'title'       => __( 'Description', 'woocommerce' ),
					'description' => __( 'What makes this product unique? What are its most important features? Enrich the product page by adding rich content using blocks.', 'woocommerce' ),
				],
			]
		);
		$description_section->add_block(
			[
				'id'        => 'product-description',
				'blockName' => 'woocommerce/product-description-field',
				'order'     => 10,
			]
		);
		// Images section.
		$images_section = $general_group->add_section(
			[
				'id'         => 'product-images-section',
				'order'      => 30,
				'attributes' => [
					'title'       => __( 'Images', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag. */
						__( 'Drag images, upload new ones or select files from your library. For best results, use JPEG files that are 1000 by 1000 pixels or larger. %1$sHow to prepare images?%2$s', 'woocommerce' ),
						'<a href="http://woocommerce.com/#" target="_blank" rel="noreferrer">',
						'</a>'
					),
				],
			]
		);
		$images_section->add_block(
			[
				'id'         => 'product-images',
				'blockName'  => 'woocommerce/product-images-field',
				'order'      => 10,
				'attributes' => [
					'images' => [],
				],
			]
		);
	}
}