<?php
/**
 * Product Block Editor reusable template pieces.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

trait BaseProductTemplate {

    /**
     * Add all base template groups.
     */
    protected function add_base_template() {
        $this->add_general_group();
        $this->add_pricing_group();
    }

    /**
     * Add general group and fields.
     */
    protected function add_general_group() {
        $this->add_group(
            array(
                'id'    => self::GENERAL_GROUP,
                'title' => __( 'General', 'woocommerce' ),
                'order' => 10,
            )
        );
        $this->add_section(
            array(
                'id'          => self::BASIC_DETAILS_SECTION,
                'title'       => __( 'Basic details', 'woocommerce' ),
                'description' => __( 'This info will be displayed on the product page, category pages, social media, and search results.', 'woocommerce' ),
                'parent'      => self::GENERAL_GROUP,
            )
        );
        $this->add_field(
            array(
                'parent'    => self::BASIC_DETAILS_SECTION,
                'blockName' => 'woocommerce/product-name-field',
                'attrs'     => array(
                    'name'  => 'Product name',
                ),
            ),
        );
        $this->add_field(
            array(
                'parent'    => self::BASIC_DETAILS_SECTION,
                'blockName' => 'woocommerce/product-summary-field',
            ),
        );
        $this->add_field(
            array(
                'id'        => 'pricing-columns',
                'parent'    => self::BASIC_DETAILS_SECTION,
                'blockName' => 'core/columns',
                'attrs'     => array(
                    'columns' => 2,
                ),
            ),
        );
        $this->add_field(
            array(
                'id'        => 'pricing-column-1',
                'parent'    => 'pricing-columns',
                'blockName' => 'core/column',
                'attrs'     => array(
                    'templateLock' => 'all',
                ),
            ),
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-regular-price-field',
                'parent'    => 'pricing-column-1',
                'attrs'     => array(
                    'name'  => 'regular_price',
                    'label' => __( 'List price', 'woocommerce' ),
                    'help'  => __( 'Manage more settings in <PricingTab>Pricing.</PricingTab>', 'woocommerce' ),
                ),
            ),
        );
        $this->add_field(
            array(
                'id'        => 'pricing-column-2',
                'parent'    => 'pricing-columns',
                'blockName' => 'core/column',
                'attrs'     => array(
                    'templateLock' => 'all',
                ),
            ),
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-sale-price-field',
                'parent'    => 'pricing-column-2',
                'attrs'     => array(
                    'label' => __( 'Sale price', 'woocommerce' ),
                ),
            ),
        );
    }

    /**
     * Add pricing group and fields.
     */
    protected function add_pricing_group() {
        $this->add_group(
            array(
                'id'    => self::PRICING_GROUP,
                'title' => __( 'Pricing', 'woocommerce' ),
                'order' => 20,
            )
        );
        $this->add_section(
            array(
                'id'          => self::PRICING_SECTION,
                'title'       => __( 'Pricing', 'woocommerce' ),
                'description' => sprintf(
                    /* translators: %1$s: Images guide link opening tag. %2$s: Images guide link closing tag.*/
                    __( 'Set a competitive price, put the product on sale, and manage tax calculations. %1$sHow to price your product?%2$s', 'woocommerce' ),
                    '<a href="https://woocommerce.com/posts/how-to-price-products-strategies-expert-tips/" target="_blank" rel="noreferrer">',
                    '</a>'
                ),
                'parent'      => self::PRICING_GROUP,
            )
        );
    }

}