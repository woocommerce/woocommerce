<?php
/**
 * SimpleProductTemplate
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\AbstractBlockTemplate;

/**
 * Simple Product Template.
 */
class SimpleProductTemplate extends AbstractBlockTemplate implements ProductFormTemplateInterface {

	/**
	 * SimpleProductTemplate constructor.
	 */
	public function __construct() {
		$this->add_general_group_blocks();
		$this->add_organization_group_blocks();
		$this->add_pricing_group_blocks();
		$this->add_inventory_group_blocks();
		$this->add_shipping_group_blocks();
		if ( Features::is_enabled( 'product-variation-management' ) ) {
			$this->add_variation_group_blocks();
		}
	}

	/**
	 * Get the template ID.
	 */
	public function get_id(): string {
		return 'simple-product';
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return __( 'Simple Product Template', 'woocommerce' );
	}

	/**
	 * Get the template description.
	 */
	public function get_description(): string {
		return __( 'Template for the simple product form', 'woocommerce' );
	}

	/**
	 * Get a group block by ID.
	 *
	 * @param string $group_id The group block ID.
	 */
	public function get_group_by_id( string $group_id ): ?GroupInterface {
		return $this->get_block( $group_id );
	}

	/**
	 * Get a section block by ID.
	 *
	 * @param string $section_id The section block ID.
	 */
	public function get_section_by_id( string $section_id ): ?SectionInterface {
		return $this->get_block( $section_id );
	}

	/**
	 * Get a block by ID.
	 *
	 * @param string $block_id The block block ID.
	 */
	public function get_block_by_id( string $block_id ): ?BlockInterface {
		return $this->get_block( $block_id );
	}

	/**
	 * Add a custom block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_group( array $block_config ): GroupInterface {
		$block = new Group( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}

	/**
	 * Adds the general group blocks to the template.
	 */
	private function add_general_group_blocks() {
		$general_group = $this->add_group(
			[
				'id'         => 'general',
				'order'      => 10,
				'attributes' => [
					'id'    => 'general',
					'title' => __( 'General', 'woocommerce' ),
				],
			]
		);
		// Basic Details Section.
		$basic_details = $general_group->add_section(
			[
				'id'         => 'basic-details',
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
			]
		);
		$pricing_columns  = $basic_details->add_block(
			[
				'id'        => 'product-pricing-columns',
				'blockName' => 'core/columns',
			]
		);
		$pricing_column_1 = $pricing_columns->add_block(
			[
				'id'         => 'product-pricing-column-1',
				'blockName'  => 'core/column',
				'attributes' => [
					'templateLock' => 'all',
				],
			]
		);
		$pricing_column_1->add_block(
			[
				'id'         => 'product-regular-price',
				'blockName'  => 'woocommerce/product-regular-price-field',
				'attributes' => [
					'name'  => 'regular_price',
					'label' => __( 'List price', 'woocommerce' ),
					'help'  => __( 'Manage more settings in <PricingTab>Pricing.</PricingTab>', 'woocommerce' ),
				],
			]
		);
		$pricing_column_2 = $pricing_columns->add_block(
			[
				'id'         => 'product-pricing-column-2',
				'blockName'  => 'core/column',
				'attributes' => [
					'templateLock' => 'all',
				],
			]
		);
		$pricing_column_2->add_block(
			[
				'id'         => 'product-sale-price',
				'blockName'  => 'woocommerce/product-sale-price-field',
				'attributes' => [
					'label' => __( 'Sale price', 'woocommerce' ),
				],
			]
		);

		// Description section.
		$description_section = $general_group->add_section(
			[
				'id'         => 'product-description-section',
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
			]
		);
		// Images section.
		$images_section = $general_group->add_section(
			[
				'id'         => 'product-images-section',
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
				'attributes' => [
					'images' => [],
				],
			]
		);
	}

	/**
	 * Adds the organization group blocks to the template.
	 */
	private function add_organization_group_blocks() {
		$organization_group = $this->add_group(
			[
				'id'         => 'organization',
				'order'      => 15,
				'attributes' => [
					'id'    => 'organization',
					'title' => __( 'Organization', 'woocommerce' ),
				],
			]
		);
		// Product Catalog Section.
		$product_catalog_section = $organization_group->add_section(
			[
				'id'         => 'product-catalog-section',
				'attributes' => [
					'title' => __( 'Product catalog', 'woocommerce' ),
				],
			]
		);
		$product_catalog_section->add_block(
			[
				'id'         => 'product-categories',
				'blockName'  => 'woocommerce/product-category-field',
				'attributes' => [
					'name' => 'categories',
				],
			]
		);
		$product_catalog_section->add_block(
			[
				'id'         => 'product-catalog-search-visibility',
				'blockName'  => 'woocommerce/product-catalog-visibility-field',
				'attributes' => [
					'label'      => __( 'Hide in product catalog', 'woocommerce' ),
					'visibility' => 'search',
				],
			]
		);
		$product_catalog_section->add_block(
			[
				'id'         => 'product-catalog-catalog-visibility',
				'blockName'  => 'woocommerce/product-catalog-visibility-field',
				'attributes' => [
					'label'      => __( 'Hide from search results', 'woocommerce' ),
					'visibility' => 'catalog',
				],
			]
		);
		$product_catalog_section->add_block(
			[
				'id'         => 'product-enable-product-reviews',
				'blockName'  => 'woocommerce/product-checkbox-field',
				'attributes' => [
					'label'    => __( 'Enable product reviews', 'woocommerce' ),
					'property' => 'reviews_allowed',
				],
			]
		);
		$product_catalog_section->add_block(
			[
				'id'         => 'product-post-password',
				'blockName'  => 'woocommerce/product-password-field',
				'attributes' => [
					'label' => __( 'Require a password', 'woocommerce' ),
				],
			]
		);
		// Attributes section.
		$product_catalog_section = $organization_group->add_section(
			[
				'id'         => 'product-attributes-section',
				'attributes' => [
					'title' => __( 'Attributes', 'woocommerce' ),
				],
			]
		);
		$product_catalog_section->add_block(
			[
				'id'        => 'product-attributes',
				'blockName' => 'woocommerce/product-attributes-field',
			]
		);
	}

	/**
	 * Adds the pricing group blocks to the template.
	 */
	private function add_pricing_group_blocks() {
		$pricing_group = $this->add_group(
			[
				'id'         => 'pricing',
				'order'      => 20,
				'attributes' => [
					'id'    => 'pricing',
					'title' => __( 'Pricing', 'woocommerce' ),
				],
			]
		);
		$pricing_group->add_block(
			[
				'id'         => 'product_variation_notice_pricing_tab',
				'blockName'  => 'woocommerce/product-has-variations-notice',
				'attributes' => [
					'id'         => 'wc-product-notice-has-options',
					'content'    => __( 'This product has options, such as size or color. You can now manage each variation\'s price and other details individually.', 'woocommerce' ),
					'buttonText' => __( 'Go to Variations', 'woocommerce' ),
					'type'       => 'info',
				],
			]
		);
		// Product Pricing Section.
		$product_pricing_section = $pricing_group->add_section(
			[
				'id'         => 'product-pricing-section',
				'attributes' => [
					'description' => sprintf(
					/* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag.*/
						__( 'Set a competitive price, put the product on sale, and manage tax calculations. %1$sHow to price your product?%2$s', 'woocommerce' ),
						'<a href="https://woocommerce.com/posts/how-to-price-products-strategies-expert-tips/" target="_blank" rel="noreferrer">',
						'</a>'
					),
					'blockGap'    => 'unit-40',
				],
			]
		);
		$pricing_columns         = $product_pricing_section->add_block(
			[
				'id'        => 'product-pricing-group-pricing-columns',
				'blockName' => 'core/columns',
			]
		);
		$pricing_column_1        = $pricing_columns->add_block(
			[
				'id'         => 'product-pricing-group-pricing-column-1',
				'blockName'  => 'core/column',
				'attributes' => [
					'templateLock' => 'all',
				],
			]
		);
		$pricing_column_1->add_block(
			[
				'id'         => 'product-pricing-regular-price',
				'blockName'  => 'woocommerce/product-regular-price-field',
				'attributes' => [
					'name'  => 'regular_price',
					'label' => __( 'List price', 'woocommerce' ),
				],
			]
		);
		$pricing_column_2 = $pricing_columns->add_block(
			[
				'id'         => 'product-pricing-group-pricing-column-2',
				'blockName'  => 'core/column',
				'attributes' => [
					'templateLock' => 'all',
				],
			]
		);
		$pricing_column_2->add_block(
			[
				'id'         => 'product-pricing-sale-price',
				'blockName'  => 'woocommerce/product-sale-price-field',
				'attributes' => [
					'label' => __( 'Sale price', 'woocommerce' ),
				],
			]
		);
		$product_pricing_section->add_block(
			[
				'id'        => 'product-pricing-schedule-sale-fields',
				'blockName' => 'woocommerce/product-schedule-sale-fields',
			]
		);
		$product_pricing_section->add_block(
			[
				'id'         => 'product-sale-tax',
				'blockName'  => 'woocommerce/product-radio-field',
				'attributes' => [
					'title'    => __( 'Charge sales tax on', 'woocommerce' ),
					'property' => 'tax_status',
					'options'  => [
						[
							'label' => __( 'Product and shipping', 'woocommerce' ),
							'value' => 'taxable',
						],
						[
							'label' => __( 'Only shipping', 'woocommerce' ),
							'value' => 'shipping',
						],
						[
							'label' => __( "Don't charge tax", 'woocommerce' ),
							'value' => 'none',
						],
					],
				],
			]
		);
		$pricing_advanced_block = $product_pricing_section->add_block(
			[
				'id'         => 'product-pricing-advanced',
				'blockName'  => 'woocommerce/product-collapsible',
				'attributes' => [
					'toggleText'       => __( 'Advanced', 'woocommerce' ),
					'initialCollapsed' => true,
					'persistRender'    => true,
				],
			]
		);
		$pricing_advanced_block->add_block(
			[
				'id'         => 'product-tax-class',
				'blockName'  => 'woocommerce/product-radio-field',
				'attributes' => [
					'title'       => __( 'Tax class', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: Learn more link opening tag. %2$s: Learn more link closing tag.*/
						__( 'Apply a tax rate if this product qualifies for tax reduction or exemption. %1$sLearn more%2$s.', 'woocommerce' ),
						'<a href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/#shipping-tax-class" target="_blank" rel="noreferrer">',
						'</a>'
					),
					'property'    => 'tax_class',
					'options'     => [
						[
							'label' => __( 'Standard', 'woocommerce' ),
							'value' => '',
						],
						[
							'label' => __( 'Reduced rate', 'woocommerce' ),
							'value' => 'reduced-rate',
						],
						[
							'label' => __( 'Zero rate', 'woocommerce' ),
							'value' => 'zero-rate',
						],
					],
				],
			]
		);
	}

	/**
	 * Adds the inventory group blocks to the template.
	 */
	private function add_inventory_group_blocks() {
		$inventory_group = $this->add_group(
			[
				'id'         => 'inventory',
				'order'      => 30,
				'attributes' => [
					'id'    => 'inventory',
					'title' => __( 'Inventory', 'woocommerce' ),
				],
			]
		);
		$inventory_group->add_block(
			[
				'id'         => 'product_variation_notice_inventory_tab',
				'blockName'  => 'woocommerce/product-has-variations-notice',
				'attributes' => [
					'id'         => 'wc-product-notice-has-options',
					'content'    => __( 'This product has options, such as size or color. You can now manage each variation\'s price and other details individually.', 'woocommerce' ),
					'buttonText' => __( 'Go to Variations', 'woocommerce' ),
					'type'       => 'info',
				],
			]
		);
		// Product Pricing Section.
		$product_inventory_section       = $inventory_group->add_section(
			[
				'id'         => 'product-inventory-section',
				'attributes' => [
					'title'       => __( 'Inventory', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: Inventory settings link opening tag. %2$s: Inventory settings link closing tag.*/
						__( 'Set up and manage inventory for this product, including status and available quantity. %1$sManage store inventory settings%2$s', 'woocommerce' ),
						'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ) . '" target="_blank" rel="noreferrer">',
						'</a>'
					),
					'blockGap'    => 'unit-40',
				],
			]
		);
		$product_inventory_inner_section = $product_inventory_section->add_section(
			[
				'id' => 'product-inventory-inner-section',
			]
		);
		$product_inventory_inner_section->add_block(
			[
				'id'        => 'product-sku-field',
				'blockName' => 'woocommerce/product-sku-field',
			]
		);
		$product_inventory_inner_section->add_block(
			[
				'id'         => 'product-track-stock',
				'blockName'  => 'woocommerce/product-toggle-field',
				'attributes' => [
					'label'    => __( 'Track stock quantity for this product', 'woocommerce' ),
					'property' => 'manage_stock',
					'disabled' => 'yes' !== get_option( 'woocommerce_manage_stock' ),
				],
			]
		);
		$product_inventory_quantity_conditional = $product_inventory_inner_section->add_block(
			[
				'id'         => 'product-inventory-quantity-conditional-wrapper',
				'blockName'  => 'woocommerce/conditional',
				'attributes' => [
					'mustMatch' => [
						'manage_stock' => [ true ],
					],
				],
			]
		);
		$product_inventory_quantity_conditional->add_block(
			[
				'id'        => 'product-inventory-quantity',
				'blockName' => 'woocommerce/product-inventory-quantity-field',
			]
		);
		$product_stock_status_conditional = $product_inventory_section->add_block(
			[
				'id'         => 'product-stock-status-conditional-wrapper',
				'blockName'  => 'woocommerce/conditional',
				'attributes' => [
					'mustMatch' => [
						'manage_stock' => [ false ],
					],
				],
			]
		);
		$product_stock_status_conditional->add_block(
			[
				'id'         => 'product-stock-status',
				'blockName'  => 'woocommerce/product-radio-field',
				'attributes' => [
					'title'    => __( 'Stock status', 'woocommerce' ),
					'property' => 'stock_status',
					'options'  => [
						[
							'label' => __( 'In stock', 'woocommerce' ),
							'value' => 'instock',
						],
						[
							'label' => __( 'Out of stock', 'woocommerce' ),
							'value' => 'outofstock',
						],
						[
							'label' => __( 'On backorder', 'woocommerce' ),
							'value' => 'onbackorder',
						],
					],
				],
			]
		);
		$product_inventory_advanced         = $product_inventory_section->add_block(
			[
				'id'         => 'product-inventory-advanced',
				'blockName'  => 'woocommerce/product-collapsible',
				'attributes' => [
					'toggleText'       => __( 'Advanced', 'woocommerce' ),
					'initialCollapsed' => true,
					'persistRender'    => true,
				],
			]
		);
		$product_inventory_advanced_wrapper = $product_inventory_advanced->add_block(
			[
				'blockName'  => 'woocommerce/product-section',
				'attributes' => [
					'blockGap' => 'unit-40',
				],
			]
		);
		$product_out_of_stock_conditional   = $product_inventory_advanced_wrapper->add_block(
			[
				'id'         => 'product-out-of-stock-conditional-wrapper',
				'blockName'  => 'woocommerce/conditional',
				'attributes' => [
					'mustMatch' => [
						'manage_stock' => [ true ],
					],
				],
			]
		);
		$product_out_of_stock_conditional->add_block(
			[
				'id'         => 'product-out-of-stock',
				'blockName'  => 'woocommerce/product-radio-field',
				'attributes' => [
					'title'    => __( 'When out of stock', 'woocommerce' ),
					'property' => 'backorders',
					'options'  => [
						[
							'label' => __( 'Allow purchases', 'woocommerce' ),
							'value' => 'yes',
						],
						[
							'label' => __(
								'Allow purchases, but notify customers',
								'woocommerce'
							),
							'value' => 'notify',
						],
						[
							'label' => __( "Don't allow purchases", 'woocommerce' ),
							'value' => 'no',
						],
					],
				],
			]
		);
		$product_out_of_stock_conditional->add_block(
			[
				'id'        => 'product-inventory-email',
				'blockName' => 'woocommerce/product-inventory-email-field',
			]
		);

		$product_inventory_advanced_wrapper->add_block(
			[
				'id'         => 'product-limit-purchase',
				'blockName'  => 'woocommerce/product-checkbox-field',
				'attributes' => [
					'title'    => __(
						'Restrictions',
						'woocommerce'
					),
					'label'    => __(
						'Limit purchases to 1 item per order',
						'woocommerce'
					),
					'property' => 'sold_individually',
					'tooltip'  => __(
						'When checked, customers will be able to purchase only 1 item in a single order. This is particularly useful for items that have limited quantity, like art or handmade goods.',
						'woocommerce'
					),
				],
			]
		);
	}

	/**
	 * Adds the shipping group blocks to the template.
	 */
	private function add_shipping_group_blocks() {
		$shipping_group = $this->add_group(
			[
				'id'         => 'shipping',
				'order'      => 40,
				'attributes' => [
					'id'    => 'shipping',
					'title' => __( 'Shipping', 'woocommerce' ),
				],
			]
		);
		$shipping_group->add_block(
			[
				'id'         => 'product_variation_notice_shipping_tab',
				'blockName'  => 'woocommerce/product-has-variations-notice',
				'attributes' => [
					'id'         => 'wc-product-notice-has-options',
					'content'    => __( 'This product has options, such as size or color. You can now manage each variation\'s price and other details individually.', 'woocommerce' ),
					'buttonText' => __( 'Go to Variations', 'woocommerce' ),
					'type'       => 'info',
				],
			]
		);
		// Product Pricing Section.
		$product_fee_and_dimensions_section = $shipping_group->add_section(
			[
				'id'         => 'product-fee-and-dimensions-section',
				'attributes' => [
					'title'       => __( 'Fees & dimensions', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: How to get started? link opening tag. %2$s: How to get started? link closing tag.*/
						__( 'Set up shipping costs and enter dimensions used for accurate rate calculations. %1$sHow to get started?%2$s.', 'woocommerce' ),
						'<a href="https://woocommerce.com/posts/how-to-calculate-shipping-costs-for-your-woocommerce-store/" target="_blank" rel="noreferrer">',
						'</a>'
					),
				],
			]
		);
		$product_fee_and_dimensions_section->add_block(
			[
				'id'        => 'product-shipping-class',
				'blockName' => 'woocommerce/product-shipping-class-field',
			]
		);
		$product_fee_and_dimensions_section->add_block(
			[
				'id'        => 'product-shipping-dimensions',
				'blockName' => 'woocommerce/product-shipping-dimensions-fields',
			]
		);
	}

	/**
	 * Adds the variation group blocks to the template.
	 */
	private function add_variation_group_blocks() {
		$variation_group  = $this->add_group(
			[
				'id'         => 'variations',
				'order'      => 50,
				'attributes' => [
					'id'    => 'variations',
					'title' => __( 'Variations', 'woocommerce' ),
				],
			]
		);
		$variation_fields = $variation_group->add_block(
			[
				'id'         => 'product_variation-field-group',
				'blockName'  => 'woocommerce/product-variations-fields',
				'attributes' => [
					'description' => sprintf(
					/* translators: %1$s: Sell your product in multiple variations like size or color. strong opening tag. %2$s: Sell your product in multiple variations like size or color. strong closing tag.*/
						__( '%1$sSell your product in multiple variations like size or color.%2$s Get started by adding options for the buyers to choose on the product page.', 'woocommerce' ),
						'<strong>',
						'</strong>'
					),
				],
			]
		);
		$variation_fields->add_block(
			[
				'id'         => 'product-variation-options',
				'blockName'  => 'woocommerce/product-variations-options-field',
				'attributes' => [
					'title' => __( 'Variation options', 'woocommerce' ),
				],
			]
		);
		$variation_section = $variation_fields->add_block(
			[
				'id'         => 'product-variation-section',
				'blockName'  => 'woocommerce/product-section',
				'attributes' => [
					'title' => __( 'Variations', 'woocommerce' ),
				],
			]
		);

		$variation_section->add_block(
			[
				'id'        => 'product-variation-items',
				'blockName' => 'woocommerce/product-variation-items-field',
			]
		);
	}
}
