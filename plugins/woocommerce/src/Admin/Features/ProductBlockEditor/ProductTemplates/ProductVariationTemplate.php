<?php
/**
 * ProductVariationTemplate
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * Simple Product Template.
 */
class ProductVariationTemplate extends AbstractProductFormTemplate implements ProductFormTemplateInterface {
	/**
	 * The context name used to identify the editor.
	 */
	const GROUP_IDS = array(
		'GENERAL'   => 'general',
		'PRICING'   => 'pricing',
		'INVENTORY' => 'inventory',
		'SHIPPING'  => 'shipping',
	);

	/**
	 * The option name used check whether the single variation notice has been dismissed.
	 */
	const SINGLE_VARIATION_NOTICE_DISMISSED_OPTION = 'woocommerce_single_variation_notice_dismissed';

	/**
	 * SimpleProductTemplate constructor.
	 */
	public function __construct() {
		$this->add_group_blocks();
		$this->add_general_group_blocks();
		$this->add_pricing_group_blocks();
		$this->add_inventory_group_blocks();
		$this->add_shipping_group_blocks();
	}

	/**
	 * Get the template ID.
	 */
	public function get_id(): string {
		return 'product-variation';
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return __( 'Product Variation Template', 'woocommerce' );
	}

	/**
	 * Get the template description.
	 */
	public function get_description(): string {
		return __( 'Template for the product variation form', 'woocommerce' );
	}

	/**
	 * Adds the group blocks to the template.
	 */
	private function add_group_blocks() {
		$this->add_group(
			[
				'id'         => $this::GROUP_IDS['GENERAL'],
				'order'      => 10,
				'attributes' => [
					'title' => __( 'General', 'woocommerce' ),
				],
			]
		);
		$this->add_group(
			[
				'id'         => $this::GROUP_IDS['PRICING'],
				'order'      => 20,
				'attributes' => [
					'title' => __( 'Pricing', 'woocommerce' ),
				],
			]
		);
		$this->add_group(
			[
				'id'         => $this::GROUP_IDS['INVENTORY'],
				'order'      => 30,
				'attributes' => [
					'title' => __( 'Inventory', 'woocommerce' ),
				],
			]
		);
		$this->add_group(
			[
				'id'         => $this::GROUP_IDS['SHIPPING'],
				'order'      => 40,
				'attributes' => [
					'title' => __( 'Shipping', 'woocommerce' ),
				],
			]
		);
	}

	/**
	 * Adds the general group blocks to the template.
	 */
	private function add_general_group_blocks() {
		$general_group = $this->get_group_by_id( $this::GROUP_IDS['GENERAL'] );
		$general_group->add_block(
			[
				'id'         => 'general-single-variation-notice',
				'blockName'  => 'woocommerce/product-single-variation-notice',
				'order'      => 10,
				'attributes' => [
					'content'       => __( '<strong>You’re editing details specific to this variation.</strong> Some information, like description and images, will be inherited from the main product, <noticeLink><parentProductName/></noticeLink>.', 'woocommerce' ),
					'type'          => 'info',
					'isDismissible' => true,
					'name'          => $this::SINGLE_VARIATION_NOTICE_DISMISSED_OPTION,
				],
			]
		);
		// Basic Details Section.
		$basic_details = $general_group->add_section(
			[
				'id'         => 'product-variation-details-section',
				'order'      => 10,
				'attributes' => [
					'title'       => __( 'Variation details', 'woocommerce' ),
					'description' => __( 'This info will be displayed on the product page, category pages, social media, and search results.', 'woocommerce' ),
				],
			]
		);
		$basic_details->add_block(
			[
				'id'         => 'product-variation-note',
				'blockName'  => 'woocommerce/product-summary-field',
				'order'      => 20,
				'attributes' => [
					'property' => 'description',
					'label'    => __( 'Note <optional />', 'woocommerce' ),
					'helpText' => 'Enter an optional note displayed on the product page when customers select this variation.',
				],
			]
		);
		$basic_details->add_block(
			[
				'id'         => 'product-variation-visibility',
				'blockName'  => 'woocommerce/product-checkbox-field',
				'order'      => 30,
				'attributes' => [
					'property'       => 'status',
					'label'          => __( 'Hide in product catalog', 'woocommerce' ),
					'checkedValue'   => 'private',
					'uncheckedValue' => 'publish',
				],
			]
		);

		// Images section.
		$images_section = $general_group->add_section(
			[
				'id'         => 'product-variation-images-section',
				'order'      => 30,
				'attributes' => [
					'title'       => __( 'Image', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag. */
						__( 'Drag images, upload new ones or select files from your library. For best results, use JPEG files that are 1000 by 1000 pixels or larger. %1$sHow to prepare images?%2$s', 'woocommerce' ),
						'<a href="https://woocommerce.com/posts/how-to-take-professional-product-photos-top-tips" target="_blank" rel="noreferrer">',
						'</a>'
					),
				],
			]
		);
		$images_section->add_block(
			[
				'id'         => 'product-variation-image',
				'blockName'  => 'woocommerce/product-images-field',
				'order'      => 10,
				'attributes' => [
					'property' => 'image',
					'multiple' => false,
				],
			]
		);

		// Downloads section.
		if ( Features::is_enabled( 'product-virtual-downloadable' ) ) {
			$general_group->add_section(
				[
					'id'         => 'product-variation-downloads-section',
					'order'      => 40,
					'attributes' => [
						'title'       => __( 'Downloads', 'woocommerce' ),
						'description' => __( "Add any files you'd like to make available for the customer to download after purchasing, such as instructions or warranty info.", 'woocommerce' ),
					],
				]
			)->add_block(
				[
					'id'        => 'product-variation-downloads',
					'blockName' => 'woocommerce/product-downloads-field',
					'order'     => 10,
				]
			);
		}
	}

	/**
	 * Adds the pricing group blocks to the template.
	 */
	private function add_pricing_group_blocks() {
		$pricing_group = $this->get_group_by_id( $this::GROUP_IDS['PRICING'] );
		$pricing_group->add_block(
			[
				'id'         => 'pricing-single-variation-notice',
				'blockName'  => 'woocommerce/product-single-variation-notice',
				'order'      => 10,
				'attributes' => [
					'content'       => __( '<strong>You’re editing details specific to this variation.</strong> Some information, like description and images, will be inherited from the main product, <noticeLink><parentProductName/></noticeLink>.', 'woocommerce' ),
					'type'          => 'info',
					'isDismissible' => true,
					'name'          => $this::SINGLE_VARIATION_NOTICE_DISMISSED_OPTION,
				],
			]
		);
		// Product Pricing Section.
		$product_pricing_section = $pricing_group->add_section(
			[
				'id'         => 'product-pricing-section',
				'order'      => 20,
				'attributes' => [
					'title'       => __( 'Pricing', 'woocommerce' ),
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
				'order'     => 10,
			]
		);
		$pricing_column_1        = $pricing_columns->add_block(
			[
				'id'         => 'product-pricing-group-pricing-column-1',
				'blockName'  => 'core/column',
				'order'      => 10,
				'attributes' => [
					'templateLock' => 'all',
				],
			]
		);
		$pricing_column_1->add_block(
			[
				'id'         => 'product-pricing-regular-price',
				'blockName'  => 'woocommerce/product-regular-price-field',
				'order'      => 10,
				'attributes' => [
					'name'       => 'regular_price',
					'label'      => __( 'Regular price', 'woocommerce' ),
					'isRequired' => true,
				],
			]
		);
		$pricing_column_2 = $pricing_columns->add_block(
			[
				'id'         => 'product-pricing-group-pricing-column-2',
				'blockName'  => 'core/column',
				'order'      => 20,
				'attributes' => [
					'templateLock' => 'all',
				],
			]
		);
		$pricing_column_2->add_block(
			[
				'id'         => 'product-pricing-sale-price',
				'blockName'  => 'woocommerce/product-sale-price-field',
				'order'      => 10,
				'attributes' => [
					'label' => __( 'Sale price', 'woocommerce' ),
				],
			]
		);
		$product_pricing_section->add_block(
			[
				'id'        => 'product-pricing-schedule-sale-fields',
				'blockName' => 'woocommerce/product-schedule-sale-fields',
				'order'     => 20,
			]
		);

		$product_pricing_section->add_block(
			[
				'id'         => 'product-tax-class',
				'blockName'  => 'woocommerce/product-radio-field',
				'order'      => 40,
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
							'label' => __( 'Same as main product', 'woocommerce' ),
							'value' => 'parent',
						],
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
		$inventory_group = $this->get_group_by_id( $this::GROUP_IDS['INVENTORY'] );
		$inventory_group->add_block(
			[
				'id'         => 'inventory-single-variation-notice',
				'blockName'  => 'woocommerce/product-single-variation-notice',
				'order'      => 10,
				'attributes' => [
					'content'       => __( '<strong>You’re editing details specific to this variation.</strong> Some information, like description and images, will be inherited from the main product, <noticeLink><parentProductName/></noticeLink>.', 'woocommerce' ),
					'type'          => 'info',
					'isDismissible' => true,
					'name'          => $this::SINGLE_VARIATION_NOTICE_DISMISSED_OPTION,
				],
			]
		);
		// Product Inventory Section.
		$product_inventory_section       = $inventory_group->add_section(
			[
				'id'         => 'product-variation-inventory-section',
				'order'      => 20,
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
				'id'    => 'product-variation-inventory-inner-section',
				'order' => 10,
			]
		);
		$product_inventory_inner_section->add_block(
			[
				'id'        => 'product-variation-sku-field',
				'blockName' => 'woocommerce/product-sku-field',
				'order'     => 10,
			]
		);
		$product_inventory_inner_section->add_block(
			[
				'id'         => 'product-variation-track-stock',
				'blockName'  => 'woocommerce/product-toggle-field',
				'order'      => 20,
				'attributes' => [
					'label'        => __( 'Track stock quantity for this product', 'woocommerce' ),
					'property'     => 'manage_stock',
					'disabled'     => 'yes' !== get_option( 'woocommerce_manage_stock' ),
					'disabledCopy' => sprintf(
						/* translators: %1$s: Learn more link opening tag. %2$s: Learn more link closing tag.*/
						__( 'Per your %1$sstore settings%2$s, inventory management is <strong>disabled</strong>.', 'woocommerce' ),
						'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ) . '" target="_blank" rel="noreferrer">',
						'</a>'
					),
				],
			]
		);
		$product_inventory_quantity_conditional = $product_inventory_inner_section->add_block(
			[
				'id'         => 'product-variation-inventory-quantity-conditional-wrapper',
				'blockName'  => 'woocommerce/conditional',
				'order'      => 30,
				'attributes' => [
					'mustMatch' => [
						'manage_stock' => [ true ],
					],
				],
			]
		);
		$product_inventory_quantity_conditional->add_block(
			[
				'id'        => 'product-variation-inventory-quantity',
				'blockName' => 'woocommerce/product-inventory-quantity-field',
				'order'     => 10,
			]
		);
		$product_stock_status_conditional = $product_inventory_section->add_block(
			[
				'id'         => 'product-variation-stock-status-conditional-wrapper',
				'blockName'  => 'woocommerce/conditional',
				'order'      => 20,
				'attributes' => [
					'mustMatch' => [
						'manage_stock' => [ false ],
					],
				],
			]
		);
		$product_stock_status_conditional->add_block(
			[
				'id'         => 'product-variation-stock-status',
				'blockName'  => 'woocommerce/product-radio-field',
				'order'      => 10,
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
	}

	/**
	 * Adds the shipping group blocks to the template.
	 */
	private function add_shipping_group_blocks() {
		$shipping_group = $this->get_group_by_id( $this::GROUP_IDS['SHIPPING'] );
		$shipping_group->add_block(
			[
				'id'         => 'shipping-single-variation-notice',
				'blockName'  => 'woocommerce/product-single-variation-notice',
				'order'      => 10,
				'attributes' => [
					'content'       => __( '<strong>You’re editing details specific to this variation.</strong> Some information, like description and images, will be inherited from the main product, <noticeLink><parentProductName/></noticeLink>.', 'woocommerce' ),
					'type'          => 'info',
					'isDismissible' => true,
					'name'          => $this::SINGLE_VARIATION_NOTICE_DISMISSED_OPTION,
				],
			]
		);
		// Virtual section.
		$shipping_group->add_section(
			[
				'id'    => 'product-variation-virtual-section',
				'order' => 20,
			]
		)->add_block(
			[
				'id'         => 'product-variation-virtual',
				'blockName'  => 'woocommerce/product-toggle-field',
				'order'      => 10,
				'attributes' => [
					'property'       => 'virtual',
					'checkedValue'   => false,
					'uncheckedValue' => true,
					'label'          => __( 'This variation requires shipping or pickup', 'woocommerce' ),
				],
			]
		);
		// Product Shipping Section.
		$product_fee_and_dimensions_section = $shipping_group->add_section(
			[
				'id'         => 'product-variation-fee-and-dimensions-section',
				'order'      => 30,
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
				'id'        => 'product-variation-shipping-class',
				'blockName' => 'woocommerce/product-shipping-class-field',
				'order'     => 10,
			]
		);
		$product_fee_and_dimensions_section->add_block(
			[
				'id'        => 'product-variation-shipping-dimensions',
				'blockName' => 'woocommerce/product-shipping-dimensions-fields',
				'order'     => 20,
			]
		);
	}
}
