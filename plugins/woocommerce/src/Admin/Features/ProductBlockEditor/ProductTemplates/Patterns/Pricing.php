<?php
/**
 * Product Block Editor template "Pricing" group
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\Patterns;

trait Pricing {

    /**
     * Add pricing group and fields.
     */
    protected function add_pricing_pattern() {
        $this->add_group(
            array(
                'id'    => self::PRICING_GROUP,
                'title' => __( 'Pricing', 'woocommerce' ),
                'order' => 20,
            )
        );
        $this->add_pricing_section();
    }

    /**
     * Add pricing section.
     */
    protected function add_pricing_section() {
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
                'attrs'       => array(
                    'blockGap'    => 'unit-40',
                ),
            )
        );
        $this->add_section(
            array(
                'id'          => self::BASIC_PRICING_SECTION,
                'parent'      => self::PRICING_GROUP,
            )
        );
        $this->add_field(
            array(
                'id'        => 'basic-pricing-columns',
                'parent'    => self::BASIC_PRICING_SECTION,
                'blockName' => 'core/columns',
                'attrs'     => array(
                    'columns' => 2,
                ),
            )
        );
        $this->add_field(
            array(
                'id'        => 'basic-pricing-column-1',
                'parent'    => 'basic-pricing-columns',
                'blockName' => 'core/column',
                'attrs'     => array(
                    'templateLock' => 'all',
                ),
            )
        );
        $this->add_field(
            array(
                'id'        => 'basic-pricing-column-2',
                'parent'    => 'basic-pricing-columns',
                'blockName' => 'core/column',
                'attrs'     => array(
                    'templateLock' => 'all',
                ),
            )
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-regular-price-field',
                'parent'    => 'basic-pricing-column-1',
                'attrs'     => array(
                    'name'  => 'regular_price',
                    'label' => __( 'List price', 'woocommerce' ),
                ),
            )
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-sale-price-field',
                'parent'    => 'basic-pricing-column-2',
                'attrs'     => array(
                    'label' => __( 'Sale price', 'woocommerce' ),
                ),
            )
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-radio-field',
                'parent'    => self::BASIC_PRICING_SECTION,
                'attrs'     => array(
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
        $this->add_field(
            array(
                'id'        => self::BASIC_PRICING_SECTION . '/tax',
                'blockName' => 'woocommerce/product-collapsible',
                'parent'    => self::BASIC_PRICING_SECTION,
                'attrs'     => array(
                    'toggleText'       => __( 'Advanced', 'woocommerce' ),
                    'initialCollapsed' => true,
                    'persistRender'    => true,
                ),
            )
        );
        $this->add_field(
            array(
                'blockName' => 'woocommerce/product-radio-field',
                'parent'    => self::BASIC_PRICING_SECTION . '/tax',
                'attrs'     => array(
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
        );
    }

}