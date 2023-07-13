<?php
/**
 * Product Block Editor abstract product template.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

abstract class AbstractProductTemplate extends WooCommerceBlockTemplate {

    /**
     * Add a field to the existing template.
     */
    public function add_field( $args ) {
        if ( ! isset( $args['parent'] ) ) {
            throw new Exception( __( 'Product template field requires a parent ID.', 'woocommerce' ) );
        }

        $this->add_block( $args );
    }

    /**
     * Add a group to the template.
     */
    public function add_group( $args = array() ) {
        $group = array_merge(
            $args,
            array(
                'blockName'    => 'woocommerce/product-tab',
                'attrs'        => array(
                    'id'    => $args['id'],
                    'title' => $args['title'],
                    'order' => $args['order'] ?? 10,
                ),
            )
        );

        $this->add_block( $group );
    }

    /**
     * Add a section to the template.
     */
    public function add_section( $args = array() ) {
        $section = array_merge(
            $args,
            array(
                'blockName'    => 'woocommerce/product-section',
                'attrs'        => array(
                    'title'       => $args['title'],
                    'description' => $args['description'],
                ),
            )
        );

        $this->add_block( $section );
    }

}