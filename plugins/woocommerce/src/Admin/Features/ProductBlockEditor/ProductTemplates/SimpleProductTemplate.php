<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

/**
 * Simple product template.
 */
class SimpleProductTemplate implements ProductTemplateInterface {

	/**
	 * Get the name of the template.
	 *
	 * @return string Template name
	 */
    public function get_name() {
        return 'simple';
    }

    /**
	 * Get the template layout.
	 *
	 * @return array Array of blocks
	 */
    public function get_template() {
        return array(
            array(
                'woocommerce/product-tab',
                array(
                    'id'    => 'general',
                    'title' => __( 'General', 'woocommerce' ),
                    'order' => 10,
                ),
                array(
                    array(
                        'woocommerce/product-section',
                        array(
                            'title'       => __( 'Basic details', 'woocommerce' ),
                            'description' => __( 'This info will be displayed on the product page, category pages, social media, and search results.', 'woocommerce' ),
                        ),
                        array(
                            array(
                                'woocommerce/product-name-field',
                                array(
                                    'name' => 'Product name',
                                ),
                            ),
                            array(
                                'woocommerce/product-summary-field',
                            ),
                            array(
                                'core/columns',
                                array(),
                                array(
                                    array(
                                        'core/column',
                                        array(
                                            'templateLock' => 'all',
                                        ),
                                        array(
                                            array(
                                                'woocommerce/product-regular-price-field',
                                                array(
                                                    'name'  => 'regular_price',
                                                    'label' => __( 'List price', 'woocommerce' ),
                                                    'help'  => __( 'Manage more settings in <PricingTab>Pricing.</PricingTab>', 'woocommerce' ),
                                                ),
                                            ),
                                        ),
                                    ),
                                    array(
                                        'core/column',
                                        array(
                                            'templateLock' => 'all',
                                        ),
                                        array(
                                            array(
                                                'woocommerce/product-sale-price-field',
                                                array(
                                                    'label' => __( 'Sale price', 'woocommerce' ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'woocommerce/product-section',
                        array(
                            'title'       => __( 'Description', 'woocommerce' ),
                            'description' => __( 'What makes this product unique? What are its most important features? Enrich the product page by adding rich content using blocks.', 'woocommerce' ),
                        ),
                        array(
                            array(
                                'woocommerce/product-description-field',
                            ),
                        ),
                    ),
                    array(
                        'woocommerce/product-section',
                        array(
                            'title'       => __( 'Images', 'woocommerce' ),
                            'description' => sprintf(
                                /* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag.*/
                                __( 'Drag images, upload new ones or select files from your library. For best results, use JPEG files that are 1000 by 1000 pixels or larger. %1$sHow to prepare images?%2$s', 'woocommerce' ),
                                '<a href="http://woocommerce.com/#" target="_blank" rel="noreferrer">',
                                '</a>'
                            ),
                        ),
                        array(
                            array(
                                'woocommerce/product-images-field',
                                array(
                                    'images' => array(),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'woocommerce/product-section',
                        array(
                            'title'       => __( 'Organization & visibility', 'woocommerce' ),
                            'description' => __( 'Help customers find this product by assigning it to categories or featuring it across your sales channels.', 'woocommerce' ),
                        ),
                        array(
                            array(
                                'woocommerce/product-category-field',
                                array(
                                    'name' => 'categories',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'woocommerce/product-section',
                        array(
                            'title'       => __( 'Attributes', 'woocommerce' ),
                            'description' => sprintf(
                                /* translators: %1$s: Attributes guide link opening tag. %2$s: Attributes guide link closing tag.*/
                                __( 'Add descriptive pieces of information that customers can use to filter and search for this product. %1$sLearn more%2$s', 'woocommerce' ),
                                '<a href="https://woocommerce.com/document/managing-product-taxonomies/#product-attributes" target="_blank" rel="noreferrer">',
                                '</a>'
                            ),
                        ),
                        array(
                            array(
                                'woocommerce/product-attributes-field',
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'woocommerce/product-tab',
                array(
                    'id'    => 'pricing',
                    'title' => __( 'Pricing', 'woocommerce' ),
                    'order' => 20,
                ),
                array(
                    array(
                        'woocommerce/product-section',
                        array(
                            'title'       => __( 'Pricing', 'woocommerce' ),
                            'description' => sprintf(
                                /* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag.*/
                                __( 'Set a competitive price, put the product on sale, and manage tax calculations. %1$sHow to price your product?%2$s', 'woocommerce' ),
                                '<a href="https://woocommerce.com/posts/how-to-price-products-strategies-expert-tips/" target="_blank" rel="noreferrer">',
                                '</a>'
                            ),
                        ),
                        array(
                            array(
                                'core/columns',
                                array(),
                                array(
                                    array(
                                        'core/column',
                                        array(
                                            'templateLock' => 'all',
                                        ),
                                        array(
                                            array(
                                                'woocommerce/product-regular-price-field',
                                                array(
                                                    'name'  => 'regular_price',
                                                    'label' => __( 'List price', 'woocommerce' ),
                                                ),
                                            ),
                                        ),
                                    ),
                                    array(
                                        'core/column',
                                        array(
                                            'templateLock' => 'all',
                                        ),
                                        array(
                                            array(
                                                'woocommerce/product-sale-price-field',
                                                array(
                                                    'label' => __( 'Sale price', 'woocommerce' ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            array(
                                'woocommerce/product-schedule-sale-fields',
                            ),
                            array(
                                'woocommerce/product-radio-field',
                                array(
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
                            ),
                            array(
                                'woocommerce/product-collapsible',
                                array(
                                    'toggleText'       => __( 'Advanced', 'woocommerce' ),
                                    'initialCollapsed' => true,
                                    'persistRender'    => true,
                                ),
                                array(
                                    array(
                                        'woocommerce/product-radio-field',
                                        array(
                                            'title'    => __( 'Tax class', 'woocommerce' ),
                                            'description' => sprintf(
                                                /* translators: %1$s: Learn more link opening tag. %2$s: Learn more link closing tag.*/
                                                __( 'Apply a tax rate if this product qualifies for tax reduction or exemption. %1$sLearn more%2$s.', 'woocommerce' ),
                                                '<a href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/#shipping-tax-class" target="_blank" rel="noreferrer">',
                                                '</a>'
                                            ),
                                            'property' => 'tax_class',
                                            'options'  => array(
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
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'woocommerce/product-tab',
                array(
                    'id'    => 'inventory',
                    'title' => __( 'Inventory', 'woocommerce' ),
                    'order' => 30,
                ),
                array(
                    array(
                        'woocommerce/product-section',
                        array(
                            'title'       => __( 'Inventory', 'woocommerce' ),
                            'description' => sprintf(
                                /* translators: %1$s: Inventory settings link opening tag. %2$s: Inventory settings link closing tag.*/
                                __( 'Set up and manage inventory for this product, including status and available quantity. %1$sManage store inventory settings%2$s', 'woocommerce' ),
                                '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ) . '" target="_blank" rel="noreferrer">',
                                '</a>'
                            ),
                        ),
                        array(
                            array(
                                'woocommerce/product-sku-field',
                            ),
                            array(
                                'woocommerce/product-toggle-field',
                                array(
                                    'label'    => __( 'Track stock quantity for this product', 'woocommerce' ),
                                    'property' => 'manage_stock',
                                    'disabled' => 'yes' !== get_option( 'woocommerce_manage_stock' ),
                                ),
                            ),
                            array(
                                'woocommerce/conditional',
                                array(
                                    'mustMatch' => array(
                                        'manage_stock' => array( true ),
                                    ),
                                ),
                                array(
                                    array(
                                        'woocommerce/product-inventory-quantity-field',
                                    ),
                                ),
                            ),
                            array(
                                'woocommerce/conditional',
                                array(
                                    'mustMatch' => array(
                                        'manage_stock' => array( false ),
                                    ),
                                ),
                                array(
                                    array(
                                        'woocommerce/product-radio-field',
                                        array(
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
                                    ),
                                ),
                            ),
                            array(
                                'woocommerce/product-collapsible',
                                array(
                                    'toggleText'       => __( 'Advanced', 'woocommerce' ),
                                    'initialCollapsed' => true,
                                    'persistRender'    => true,
                                ),
                                array(
                                    array(
                                        'woocommerce/conditional',
                                        array(
                                            'mustMatch' => array(
                                                'manage_stock' => array( true ),
                                            ),
                                        ),
                                        array(
                                            array(
                                                'woocommerce/product-radio-field',
                                                array(
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
                                            ),
                                            array(
                                                'woocommerce/product-inventory-email-field',
                                            ),
                                        ),
                                    ),
                                    array(
                                        'woocommerce/product-checkbox-field',
                                        array(
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
                                    ),

                                ),
                            ),

                        ),
                    ),
                ),

            ),
            array(
                'woocommerce/product-tab',
                array(
                    'id'    => 'shipping',
                    'title' => __( 'Shipping', 'woocommerce' ),
                    'order' => 40,
                ),
                array(
                    array(
                        'woocommerce/product-section',
                        array(
                            'title'       => __( 'Fees & dimensions', 'woocommerce' ),
                            'description' => sprintf(
                                /* translators: %1$s: How to get started? link opening tag. %2$s: How to get started? link closing tag.*/
                                __( 'Set up shipping costs and enter dimensions used for accurate rate calculations. %1$sHow to get started?%2$s.', 'woocommerce' ),
                                '<a href="https://woocommerce.com/posts/how-to-calculate-shipping-costs-for-your-woocommerce-store/" target="_blank" rel="noreferrer">',
                                '</a>'
                            ),
                        ),
                        array(
                            array(
                                'woocommerce/product-shipping-class-field',
                            ),
                            array(
                                'woocommerce/product-shipping-dimensions-fields',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }
}