<?php
/**
 * Product Block Editor reusable template pieces.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\Patterns\General;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\Patterns\Pricing;

trait BaseProductTemplate {

    use General;
    use Pricing;

    /**
     * Add all base template patterns.
     */
    protected function add_base_template() {
        $this->add_general_pattern();
        $this->add_pricing_pattern();
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