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
                'parent' => self::GENERAL_GROUP,
                'block'  => array(
                    'woocommerce/product-name-field',
                    array(
                        'name' => 'Product name',
                    ),
                ),
            ),
        );
        $this->add_field(
            array(
                'parent' => self::GENERAL_GROUP,
                'block'  => array(
                    array(
                        'woocommerce/product-summary-field',
                    ),
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