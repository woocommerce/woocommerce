<?php
/**
 * SimpleProductTemplate
 */

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;

/**
 * Simple Product Template.
 */
class SimpleProductTemplate extends AbstractProductFormTemplate implements ProductFormTemplateInterface {
	/**
	 * The context name used to identify the editor.
	 */
	const GROUP_IDS = array(
		'GENERAL'      => 'general',
		'ORGANIZATION' => 'organization',
		'PRICING'      => 'pricing',
		'INVENTORY'    => 'inventory',
		'SHIPPING'     => 'shipping',
		'VARIATIONS'   => 'variations',
	);

	/**
	 * SimpleProductTemplate constructor.
	 */
	public function __construct() {
		$this->add_group_blocks();
		$this->add_general_group_blocks();
		$this->add_organization_group_blocks();
		$this->add_pricing_group_blocks();
		$this->add_inventory_group_blocks();
		$this->add_shipping_group_blocks();
		$this->add_variation_group_blocks();
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
	 * Adds the group blocks to the template.
	 */
	private function add_group_blocks() {
		$this->add_group(
			array(
				'id'         => $this::GROUP_IDS['GENERAL'],
				'order'      => 10,
				'attributes' => array(
					'title' => __( 'General', 'woocommerce' ),
				),
			)
		);
		$this->add_group(
			array(
				'id'         => $this::GROUP_IDS['ORGANIZATION'],
				'order'      => 15,
				'attributes' => array(
					'title' => __( 'Organization', 'woocommerce' ),
				),
			)
		);
		$this->add_group(
			array(
				'id'         => $this::GROUP_IDS['PRICING'],
				'order'      => 20,
				'attributes' => array(
					'title' => __( 'Pricing', 'woocommerce' ),
				),
			)
		);
		$this->add_group(
			array(
				'id'         => $this::GROUP_IDS['INVENTORY'],
				'order'      => 30,
				'attributes' => array(
					'title' => __( 'Inventory', 'woocommerce' ),
				),
			)
		);
		$this->add_group(
			array(
				'id'         => $this::GROUP_IDS['SHIPPING'],
				'order'      => 40,
				'attributes' => array(
					'title' => __( 'Shipping', 'woocommerce' ),
				),
			)
		);
		if ( Features::is_enabled( 'product-variation-management' ) ) {
			$this->add_group(
				array(
					'id'         => $this::GROUP_IDS['VARIATIONS'],
					'order'      => 50,
					'attributes' => array(
						'title' => __( 'Variations', 'woocommerce' ),
					),
				)
			);
		}
	}

	/**
	 * Adds the general group blocks to the template.
	 */
	private function add_general_group_blocks() {
		$general_group = $this->get_group_by_id( $this::GROUP_IDS['GENERAL'] );
		$general_group->add_block(
			array(
				'id'         => 'product_variation_notice_general_tab',
				'blockName'  => 'woocommerce/product-has-variations-notice',
				'order'      => 10,
				'attributes' => array(
					'content'    => __( 'This product has options, such as size or color. You can manage each variation\'s images, downloads, and other details individually.', 'woocommerce' ),
					'buttonText' => __( 'Go to Variations', 'woocommerce' ),
					'type'       => 'info',
				),
			)
		);
		// Basic Details Section.
		$basic_details = $general_group->add_section(
			array(
				'id'         => 'basic-details',
				'order'      => 10,
				'attributes' => array(
					'title'       => __( 'Basic details', 'woocommerce' ),
					'description' => __( 'This info will be displayed on the product page, category pages, social media, and search results.', 'woocommerce' ),
				),
			)
		);
		$basic_details->add_block(
			array(
				'id'         => 'product-name',
				'blockName'  => 'woocommerce/product-name-field',
				'order'      => 10,
				'attributes' => array(
					'name'      => 'Product name',
					'autoFocus' => true,
				),
			)
		);
		$basic_details->add_block(
			array(
				'id'         => 'product-summary',
				'blockName'  => 'woocommerce/product-summary-field',
				'order'      => 20,
				'attributes' => array(
					'property' => 'short_description',
				),
			)
		);
		$pricing_columns  = $basic_details->add_block(
			array(
				'id'        => 'product-pricing-columns',
				'blockName' => 'core/columns',
				'order'     => 30,
			)
		);
		$pricing_column_1 = $pricing_columns->add_block(
			array(
				'id'         => 'product-pricing-column-1',
				'blockName'  => 'core/column',
				'order'      => 10,
				'attributes' => array(
					'templateLock' => 'all',
				),
			)
		);
		$pricing_column_1->add_block(
			array(
				'id'         => 'product-regular-price',
				'blockName'  => 'woocommerce/product-regular-price-field',
				'order'      => 10,
				'attributes' => array(
					'name'  => 'regular_price',
					'label' => __( 'List price', 'woocommerce' ),
					/* translators: PricingTab: This is a link tag to the pricing tab. */
					'help'  => __( 'Manage more settings in <PricingTab>Pricing.</PricingTab>', 'woocommerce' ),
				),
			)
		);
		$pricing_column_2 = $pricing_columns->add_block(
			array(
				'id'         => 'product-pricing-column-2',
				'blockName'  => 'core/column',
				'order'      => 20,
				'attributes' => array(
					'templateLock' => 'all',
				),
			)
		);
		$pricing_column_2->add_block(
			array(
				'id'         => 'product-sale-price',
				'blockName'  => 'woocommerce/product-sale-price-field',
				'order'      => 10,
				'attributes' => array(
					'label' => __( 'Sale price', 'woocommerce' ),
				),
			)
		);

		// Description section.
		$description_section = $general_group->add_section(
			array(
				'id'         => 'product-description-section',
				'order'      => 20,
				'attributes' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'description' => __( 'What makes this product unique? What are its most important features? Enrich the product page by adding rich content using blocks.', 'woocommerce' ),
				),
			)
		);
		$description_section->add_block(
			array(
				'id'        => 'product-description',
				'blockName' => 'woocommerce/product-description-field',
				'order'     => 10,
			)
		);

		// External/Affiliate section.
		if ( Features::is_enabled( 'product-external-affiliate' ) ) {
			$buy_button_section = $general_group->add_section(
				array(
					'id'             => 'product-buy-button-section',
					'order'          => 30,
					'attributes'     => array(
						'title'       => __( 'Buy button', 'woocommerce' ),
						'description' => __( 'Add a link and choose a label for the button linked to a product sold elsewhere.', 'woocommerce' ),
					),
					'hideConditions' => array(
						array(
							'expression' => 'editedProduct.type !== "external"',
						),
					),
				)
			);

			$buy_button_section->add_block(
				array(
					'id'         => 'product-external-url',
					'blockName'  => 'woocommerce/product-text-field',
					'order'      => 10,
					'attributes' => array(
						'property'    => 'external_url',
						'label'       => __( 'Link to the external product', 'woocommerce' ),
						'placeholder' => __( 'Enter the external URL to the product', 'woocommerce' ),
						'suffix'      => true,
						'type'        => array(
							'value'   => 'url',
							'message' => __( 'Link to the external product is an invalid URL.', 'woocommerce' ),
						),
						'required'    => __( 'Link to the external product is required.', 'woocommerce' ),
					),
				)
			);

			$button_text_columns = $buy_button_section->add_block(
				array(
					'id'        => 'product-button-text-columns',
					'blockName' => 'core/columns',
					'order'     => 20,
				)
			);

			$button_text_columns->add_block(
				array(
					'id'        => 'product-button-text-column1',
					'blockName' => 'core/column',
					'order'     => 10,
				)
			)->add_block(
				array(
					'id'         => 'product-button-text',
					'blockName'  => 'woocommerce/product-text-field',
					'order'      => 10,
					'attributes' => array(
						'property' => 'button_text',
						'label'    => __( 'Buy button text', 'woocommerce' ),
					),
				)
			);

			$button_text_columns->add_block(
				array(
					'id'        => 'product-button-text-column2',
					'blockName' => 'core/column',
					'order'     => 20,
				)
			);
		}

		// Images section.
		$images_section = $general_group->add_section(
			array(
				'id'         => 'product-images-section',
				'order'      => 40,
				'attributes' => array(
					'title'       => __( 'Images', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag. */
						__( 'Drag images, upload new ones or select files from your library. For best results, use JPEG files that are 1000 by 1000 pixels or larger. %1$sHow to prepare images?%2$s', 'woocommerce' ),
						'<a href="https://woo.com/posts/how-to-take-professional-product-photos-top-tips" target="_blank" rel="noreferrer">',
						'</a>'
					),
				),
			)
		);
		$images_section->add_block(
			array(
				'id'         => 'product-images',
				'blockName'  => 'woocommerce/product-images-field',
				'order'      => 10,
				'attributes' => array(
					'images'   => array(),
					'property' => 'images',
				),
			)
		);
		// Downloads section.
		if ( Features::is_enabled( 'product-virtual-downloadable' ) ) {
			$general_group->add_section(
				array(
					'id'             => 'product-downloads-section',
					'order'          => 50,
					'attributes'     => array(
						'title'       => __( 'Downloads', 'woocommerce' ),
						'description' => __( "Add any files you'd like to make available for the customer to download after purchasing, such as instructions or warranty info.", 'woocommerce' ),
					),
					'hideConditions' => array(
						array(
							'expression' => 'editedProduct.type !== "simple"',
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

	/**
	 * Adds the organization group blocks to the template.
	 */
	private function add_organization_group_blocks() {
		$organization_group = $this->get_group_by_id( $this::GROUP_IDS['ORGANIZATION'] );
		// Product Catalog Section.
		$product_catalog_section = $organization_group->add_section(
			array(
				'id'         => 'product-catalog-section',
				'order'      => 10,
				'attributes' => array(
					'title'       => __( 'Product catalog', 'woocommerce' ),
					'description' => __( 'Help customers find this product by assigning it to categories, adding extra details, and managing its visibility in your store and other channels.', 'woocommerce' ),
				),
			)
		);
		$product_catalog_section->add_block(
			array(
				'id'         => 'product-categories',
				'blockName'  => 'woocommerce/product-taxonomy-field',
				'order'      => 10,
				'attributes' => array(
					'slug'               => 'product_cat',
					'property'           => 'categories',
					'label'              => __( 'Categories', 'woocommerce' ),
					'createTitle'        => __( 'Create new category', 'woocommerce' ),
					'dialogNameHelpText' => __( 'Shown to customers on the product page.', 'woocommerce' ),
					'parentTaxonomyText' => __( 'Parent category', 'woocommerce' ),
				),
			)
		);
		$product_catalog_section->add_block(
			array(
				'id'         => 'product-tags',
				'blockName'  => 'woocommerce/product-tag-field',
				'attributes' => array(
					'name' => 'tags',
				),
			)
		);
		$product_catalog_section->add_block(
			array(
				'id'         => 'product-catalog-search-visibility',
				'blockName'  => 'woocommerce/product-catalog-visibility-field',
				'order'      => 20,
				'attributes' => array(
					'label'      => __( 'Hide in product catalog', 'woocommerce' ),
					'visibility' => 'search',
				),
			)
		);
		$product_catalog_section->add_block(
			array(
				'id'         => 'product-catalog-catalog-visibility',
				'blockName'  => 'woocommerce/product-catalog-visibility-field',
				'order'      => 30,
				'attributes' => array(
					'label'      => __( 'Hide from search results', 'woocommerce' ),
					'visibility' => 'catalog',
				),
			)
		);
		$product_catalog_section->add_block(
			array(
				'id'         => 'product-enable-product-reviews',
				'blockName'  => 'woocommerce/product-checkbox-field',
				'order'      => 40,
				'attributes' => array(
					'label'    => __( 'Enable product reviews', 'woocommerce' ),
					'property' => 'reviews_allowed',
				),
			)
		);
		$product_catalog_section->add_block(
			array(
				'id'         => 'product-post-password',
				'blockName'  => 'woocommerce/product-password-field',
				'order'      => 50,
				'attributes' => array(
					'label' => __( 'Require a password', 'woocommerce' ),
				),
			)
		);
		// Attributes section.
		$product_catalog_section = $organization_group->add_section(
			array(
				'id'         => 'product-attributes-section',
				'order'      => 20,
				'attributes' => array(
					'title'       => __( 'Attributes', 'woocommerce' ),
					'description' => __( 'Add descriptive pieces of information that customers can use to filter and search for this product. <a href="https://woo.com/document/managing-product-taxonomies/#product-attributes" target="_blank" rel="noreferrer">Learn more</a>.', 'woocommerce' ),
				),
			)
		);
		$product_catalog_section->add_block(
			array(
				'id'        => 'product-attributes',
				'blockName' => 'woocommerce/product-attributes-field',
				'order'     => 10,
			)
		);
	}

	/**
	 * Adds the pricing group blocks to the template.
	 */
	private function add_pricing_group_blocks() {
		$pricing_group = $this->get_group_by_id( $this::GROUP_IDS['PRICING'] );
		$pricing_group->add_block(
			array(
				'id'         => 'pricing-has-variations-notice',
				'blockName'  => 'woocommerce/product-has-variations-notice',
				'order'      => 10,
				'attributes' => array(
					'content'    => __( 'This product has options, such as size or color. You can now manage each variation\'s price and other details individually.', 'woocommerce' ),
					'buttonText' => __( 'Go to Variations', 'woocommerce' ),
					'type'       => 'info',
				),
			)
		);
		// Product Pricing Section.
		$product_pricing_section = $pricing_group->add_section(
			array(
				'id'         => 'product-pricing-section',
				'order'      => 20,
				'attributes' => array(
					'title'       => __( 'Pricing', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag.*/
						__( 'Set a competitive price, put the product on sale, and manage tax calculations. %1$sHow to price your product?%2$s', 'woocommerce' ),
						'<a href="https://woo.com/posts/how-to-price-products-strategies-expert-tips/" target="_blank" rel="noreferrer">',
						'</a>'
					),
					'blockGap'    => 'unit-40',
				),
			)
		);
		$pricing_columns         = $product_pricing_section->add_block(
			array(
				'id'        => 'product-pricing-group-pricing-columns',
				'blockName' => 'core/columns',
				'order'     => 10,
			)
		);
		$pricing_column_1        = $pricing_columns->add_block(
			array(
				'id'         => 'product-pricing-group-pricing-column-1',
				'blockName'  => 'core/column',
				'order'      => 10,
				'attributes' => array(
					'templateLock' => 'all',
				),
			)
		);
		$pricing_column_1->add_block(
			array(
				'id'         => 'product-pricing-regular-price',
				'blockName'  => 'woocommerce/product-regular-price-field',
				'order'      => 10,
				'attributes' => array(
					'name'  => 'regular_price',
					'label' => __( 'List price', 'woocommerce' ),
				),
			)
		);
		$pricing_column_2 = $pricing_columns->add_block(
			array(
				'id'         => 'product-pricing-group-pricing-column-2',
				'blockName'  => 'core/column',
				'order'      => 20,
				'attributes' => array(
					'templateLock' => 'all',
				),
			)
		);
		$pricing_column_2->add_block(
			array(
				'id'         => 'product-pricing-sale-price',
				'blockName'  => 'woocommerce/product-sale-price-field',
				'order'      => 10,
				'attributes' => array(
					'label' => __( 'Sale price', 'woocommerce' ),
				),
			)
		);
		$product_pricing_section->add_block(
			array(
				'id'        => 'product-pricing-schedule-sale-fields',
				'blockName' => 'woocommerce/product-schedule-sale-fields',
				'order'     => 20,
			)
		);
		$product_pricing_section->add_block(
			array(
				'id'         => 'product-sale-tax',
				'blockName'  => 'woocommerce/product-radio-field',
				'order'      => 30,
				'attributes' => array(
					'title'    => __( 'Charge sales tax on', 'woocommerce' ),
					'property' => 'tax_status',
					'options'  => array(
						array(
							'label' => __( 'Product and shipping', 'woocommerce' ),
							'value' => 'taxable',
						),
						array(
							'label' => __( 'Only shipping', 'woocommerce' ),
							'value' => 'shipping',
						),
						array(
							'label' => __( "Don't charge tax", 'woocommerce' ),
							'value' => 'none',
						),
					),
				),
			)
		);
		$pricing_advanced_block = $product_pricing_section->add_block(
			array(
				'id'         => 'product-pricing-advanced',
				'blockName'  => 'woocommerce/product-collapsible',
				'order'      => 40,
				'attributes' => array(
					'toggleText'       => __( 'Advanced', 'woocommerce' ),
					'initialCollapsed' => true,
					'persistRender'    => true,
				),
			)
		);
		$pricing_advanced_block->add_block(
			array(
				'id'         => 'product-tax-class',
				'blockName'  => 'woocommerce/product-radio-field',
				'order'      => 10,
				'attributes' => array(
					'title'       => __( 'Tax class', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: Learn more link opening tag. %2$s: Learn more link closing tag.*/
						__( 'Apply a tax rate if this product qualifies for tax reduction or exemption. %1$sLearn more%2$s.', 'woocommerce' ),
						'<a href="https://woo.com/document/setting-up-taxes-in-woocommerce/#shipping-tax-class" target="_blank" rel="noreferrer">',
						'</a>'
					),
					'property'    => 'tax_class',
					'options'     => array(
						array(
							'label' => __( 'Standard', 'woocommerce' ),
							'value' => '',
						),
						array(
							'label' => __( 'Reduced rate', 'woocommerce' ),
							'value' => 'reduced-rate',
						),
						array(
							'label' => __( 'Zero rate', 'woocommerce' ),
							'value' => 'zero-rate',
						),
					),
				),
			)
		);
	}

	/**
	 * Adds the inventory group blocks to the template.
	 */
	private function add_inventory_group_blocks() {
		$inventory_group = $this->get_group_by_id( $this::GROUP_IDS['INVENTORY'] );
		$inventory_group->add_block(
			array(
				'id'         => 'product_variation_notice_inventory_tab',
				'blockName'  => 'woocommerce/product-has-variations-notice',
				'order'      => 10,
				'attributes' => array(
					'content'    => __( 'This product has options, such as size or color. You can now manage each variation\'s inventory and other details individually.', 'woocommerce' ),
					'buttonText' => __( 'Go to Variations', 'woocommerce' ),
					'type'       => 'info',
				),
			)
		);
		// Product Pricing Section.
		$product_inventory_section       = $inventory_group->add_section(
			array(
				'id'         => 'product-inventory-section',
				'order'      => 20,
				'attributes' => array(
					'title'       => __( 'Inventory', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: Inventory settings link opening tag. %2$s: Inventory settings link closing tag.*/
						__( 'Set up and manage inventory for this product, including status and available quantity. %1$sManage store inventory settings%2$s', 'woocommerce' ),
						'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ) . '" target="_blank" rel="noreferrer">',
						'</a>'
					),
					'blockGap'    => 'unit-40',
				),
			)
		);
		$product_inventory_inner_section = $product_inventory_section->add_section(
			array(
				'id'    => 'product-inventory-inner-section',
				'order' => 10,
			)
		);
		$product_inventory_inner_section->add_block(
			array(
				'id'        => 'product-sku-field',
				'blockName' => 'woocommerce/product-sku-field',
				'order'     => 10,
			)
		);
		$product_inventory_inner_section->add_block(
			array(
				'id'             => 'product-track-stock',
				'blockName'      => 'woocommerce/product-toggle-field',
				'order'          => 20,
				'attributes'     => array(
					'label'        => __( 'Track stock quantity for this product', 'woocommerce' ),
					'property'     => 'manage_stock',
					'disabled'     => 'yes' !== get_option( 'woocommerce_manage_stock' ),
					'disabledCopy' => sprintf(
						/* translators: %1$s: Learn more link opening tag. %2$s: Learn more link closing tag.*/
						__( 'Per your %1$sstore settings%2$s, inventory management is <strong>disabled</strong>.', 'woocommerce' ),
						'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ) . '" target="_blank" rel="noreferrer">',
						'</a>'
					),
				),
				'hideConditions' => Features::is_enabled( 'product-external-affiliate' ) ? array(
					array(
						'expression' => 'editedProduct.type === "external"',
					),
				) : null,
			)
		);
		$product_inventory_quantity_conditional = $product_inventory_inner_section->add_block(
			array(
				'id'         => 'product-inventory-quantity-conditional-wrapper',
				'blockName'  => 'woocommerce/conditional',
				'order'      => 30,
				'attributes' => array(
					'mustMatch' => array(
						'manage_stock' => array( true ),
					),
				),
			)
		);
		$product_inventory_quantity_conditional->add_block(
			array(
				'id'        => 'product-inventory-quantity',
				'blockName' => 'woocommerce/product-inventory-quantity-field',
				'order'     => 10,
			)
		);
		$product_stock_status_conditional = $product_inventory_section->add_block(
			array(
				'id'         => 'product-stock-status-conditional-wrapper',
				'blockName'  => 'woocommerce/conditional',
				'order'      => 20,
				'attributes' => array(
					'mustMatch' => array(
						'manage_stock' => array( false ),
					),
				),
			)
		);
		$product_stock_status_conditional->add_block(
			array(
				'id'         => 'product-stock-status',
				'blockName'  => 'woocommerce/product-radio-field',
				'order'      => 10,
				'attributes' => array(
					'title'    => __( 'Stock status', 'woocommerce' ),
					'property' => 'stock_status',
					'options'  => array(
						array(
							'label' => __( 'In stock', 'woocommerce' ),
							'value' => 'instock',
						),
						array(
							'label' => __( 'Out of stock', 'woocommerce' ),
							'value' => 'outofstock',
						),
						array(
							'label' => __( 'On backorder', 'woocommerce' ),
							'value' => 'onbackorder',
						),
					),
				),
			)
		);
		$product_inventory_advanced         = $product_inventory_section->add_block(
			array(
				'id'         => 'product-inventory-advanced',
				'blockName'  => 'woocommerce/product-collapsible',
				'order'      => 30,
				'attributes' => array(
					'toggleText'       => __( 'Advanced', 'woocommerce' ),
					'initialCollapsed' => true,
					'persistRender'    => true,
				),
			)
		);
		$product_inventory_advanced_wrapper = $product_inventory_advanced->add_block(
			array(
				'blockName'  => 'woocommerce/product-section',
				'order'      => 10,
				'attributes' => array(
					'blockGap' => 'unit-40',
				),
			)
		);
		$product_out_of_stock_conditional   = $product_inventory_advanced_wrapper->add_block(
			array(
				'id'         => 'product-out-of-stock-conditional-wrapper',
				'blockName'  => 'woocommerce/conditional',
				'order'      => 10,
				'attributes' => array(
					'mustMatch' => array(
						'manage_stock' => array( true ),
					),
				),
			)
		);
		$product_out_of_stock_conditional->add_block(
			array(
				'id'         => 'product-out-of-stock',
				'blockName'  => 'woocommerce/product-radio-field',
				'order'      => 10,
				'attributes' => array(
					'title'    => __( 'When out of stock', 'woocommerce' ),
					'property' => 'backorders',
					'options'  => array(
						array(
							'label' => __( 'Allow purchases', 'woocommerce' ),
							'value' => 'yes',
						),
						array(
							'label' => __(
								'Allow purchases, but notify customers',
								'woocommerce'
							),
							'value' => 'notify',
						),
						array(
							'label' => __( "Don't allow purchases", 'woocommerce' ),
							'value' => 'no',
						),
					),
				),
			)
		);
		$product_out_of_stock_conditional->add_block(
			array(
				'id'        => 'product-inventory-email',
				'blockName' => 'woocommerce/product-inventory-email-field',
				'order'     => 20,
			)
		);

		$product_inventory_advanced_wrapper->add_block(
			array(
				'id'         => 'product-limit-purchase',
				'blockName'  => 'woocommerce/product-checkbox-field',
				'order'      => 20,
				'attributes' => array(
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
				),
			)
		);
	}

	/**
	 * Adds the shipping group blocks to the template.
	 */
	private function add_shipping_group_blocks() {
		$shipping_group = $this->get_group_by_id( $this::GROUP_IDS['SHIPPING'] );
		$shipping_group->add_block(
			array(
				'id'         => 'product_variation_notice_shipping_tab',
				'blockName'  => 'woocommerce/product-has-variations-notice',
				'order'      => 10,
				'attributes' => array(
					'content'    => __( 'This product has options, such as size or color. You can now manage each variation\'s shipping settings and other details individually.', 'woocommerce' ),
					'buttonText' => __( 'Go to Variations', 'woocommerce' ),
					'type'       => 'info',
				),
			)
		);
		// Virtual section.
		if ( Features::is_enabled( 'product-virtual-downloadable' ) ) {
			$shipping_group->add_section(
				array(
					'id'             => 'product-virtual-section',
					'order'          => 10,
					'hideConditions' => array(
						array(
							'expression' => 'editedProduct.type !== "simple"',
						),
					),
				)
			)->add_block(
				array(
					'id'         => 'product-virtual',
					'blockName'  => 'woocommerce/product-toggle-field',
					'order'      => 10,
					'attributes' => array(
						'property'       => 'virtual',
						'checkedValue'   => false,
						'uncheckedValue' => true,
						'label'          => __( 'This product requires shipping or pickup', 'woocommerce' ),
						'uncheckedHelp'  => __( 'This product will not trigger your customer\'s shipping calculator in cart or at checkout. This product also won\'t require your customers to enter their shipping details at checkout. <a href="https://woo.com/document/managing-products/#adding-a-virtual-product" target="_blank" rel="noreferrer">Read more about virtual products</a>.', 'woocommerce' ),
					),
				)
			);
		}
		// Product Shipping Section.
		$product_fee_and_dimensions_section = $shipping_group->add_section(
			array(
				'id'         => 'product-fee-and-dimensions-section',
				'order'      => 20,
				'attributes' => array(
					'title'       => __( 'Fees & dimensions', 'woocommerce' ),
					'description' => sprintf(
					/* translators: %1$s: How to get started? link opening tag. %2$s: How to get started? link closing tag.*/
						__( 'Set up shipping costs and enter dimensions used for accurate rate calculations. %1$sHow to get started?%2$s.', 'woocommerce' ),
						'<a href="https://woo.com/posts/how-to-calculate-shipping-costs-for-your-woocommerce-store/" target="_blank" rel="noreferrer">',
						'</a>'
					),
				),
			)
		);
		$product_fee_and_dimensions_section->add_block(
			array(
				'id'        => 'product-shipping-class',
				'blockName' => 'woocommerce/product-shipping-class-field',
				'order'     => 10,
			)
		);
		$product_fee_and_dimensions_section->add_block(
			array(
				'id'        => 'product-shipping-dimensions',
				'blockName' => 'woocommerce/product-shipping-dimensions-fields',
				'order'     => 20,
			)
		);
	}

	/**
	 * Adds the variation group blocks to the template.
	 */
	private function add_variation_group_blocks() {
		$variation_group = $this->get_group_by_id( $this::GROUP_IDS['VARIATIONS'] );
		if ( ! $variation_group ) {
			return;
		}
		$variation_fields          = $variation_group->add_block(
			array(
				'id'         => 'product_variation-field-group',
				'blockName'  => 'woocommerce/product-variations-fields',
				'order'      => 10,
				'attributes' => array(
					'description' => sprintf(
					/* translators: %1$s: Sell your product in multiple variations like size or color. strong opening tag. %2$s: Sell your product in multiple variations like size or color. strong closing tag.*/
						__( '%1$sSell your product in multiple variations like size or color.%2$s Get started by adding options for the buyers to choose on the product page.', 'woocommerce' ),
						'<strong>',
						'</strong>'
					),
				),
			)
		);
		$variation_options_section = $variation_fields->add_block(
			array(
				'id'         => 'product-variation-options-section',
				'blockName'  => 'woocommerce/product-section',
				'order'      => 10,
				'attributes' => array(
					'title'       => __( 'Variation options', 'woocommerce' ),
					'description' => __( 'Add and manage attributes used for product options, such as size and color.', 'woocommerce' ),
				),
			)
		);
		$variation_options_section->add_block(
			array(
				'id'        => 'product-variation-options',
				'blockName' => 'woocommerce/product-variations-options-field',
			)
		);
		$variation_section = $variation_fields->add_block(
			array(
				'id'         => 'product-variation-section',
				'blockName'  => 'woocommerce/product-section',
				'order'      => 20,
				'attributes' => array(
					'title'       => __( 'Variations', 'woocommerce' ),
					'description' => __( 'Manage individual product combinations created from options.', 'woocommerce' ),
				),
			)
		);

		$variation_section->add_block(
			array(
				'id'        => 'product-variation-items',
				'blockName' => 'woocommerce/product-variation-items-field',
				'order'     => 10,
			)
		);
	}
}
