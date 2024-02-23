<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * HookedBlockExamples.
 */
class HookedBlockExamples {

    public function init() {
        add_filter( 'inserted_blocks', array( $this, 'add_sku_field' ), 10, 4 );
        add_filter( 'inserted_blocks', array( $this, 'add_custom_product_tab_1' ), 10, 4 );
        add_filter( 'inserted_blocks', array( $this, 'add_custom_product_tab_2' ), 10, 4 );
        add_filter( 'inserted_blocks', array( $this, 'add_price_block_1' ), 10, 4 );
        add_filter( 'inserted_blocks', array( $this, 'add_price_block_2' ), 10, 4 );
        add_filter( 'inserted_blocks', array( $this, 'add_block_to_hooked_block' ), 10, 4 );
    }

    /**
     * Add SKU field.
     *
     * This hook works because it is a unique block that doesn't require attributes and
     * is attached to another unique field that is not repeated.
     */
    public function add_sku_field( $inserted_blocks, $anchor_block, $position, $context ) {
        if ( 
            'woocommerce/product-name-field' === $anchor_block['blockName'] &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $inserted_blocks[] = array(
                'blockName' => 'woocommerce/product-sku-field'
            );
        }
    
        return $inserted_blocks;
    }
    
    /**
     * Add the product tab intended to be "Custom tab 1"
     *
     * This gets added, but later needs to be hooked to include attributes.
     */
    public function add_custom_product_tab_1( $inserted_blocks, $anchor_block, $position, $context ) {
        if ( 
            'woocommerce/product-tab' === $anchor_block['blockName'] &&
            'pricing' === $anchor_block['attrs']['id'] &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $inserted_blocks[] = array(
                'blockName' => 'woocommerce/product-tab',
                'attrs'     => array(
                    'id'    => 'custom_tab_1',
                    'title' => 'Custom Tab 1',
                )
            );
        }
    
        return $inserted_blocks;
    }
    
    /**
     * Add the product tab intended to be "Custom tab 2"
     *
     * This gets added, but later needs to be hooked to include attributes.
     */
    public function add_custom_product_tab_2( $inserted_blocks, $anchor_block, $position, $context ) {
        if ( 
            'woocommerce/product-tab' === $anchor_block['blockName'] &&
            'pricing' === $anchor_block['attrs']['id'] &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $inserted_blocks[] = array(
                'blockName' => 'woocommerce/product-tab',
                'attrs'     => array(
                    'id'    => 'custom_tab_2',
                    'title' => 'Custom Tab 2',
                )
            );
        }
    
        return $inserted_blocks;
    }

    /**
     * Add price block 1.
     *
     * This price block does not have attributes and will later be overwritten by a 2nd price block's attributes.
     */
    public function add_price_block_1( $inserted_blocks, $anchor_block, $position, $context ) {
        if ( 
            'woocommerce/product-name-field' === $anchor_block['blockName'] &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $inserted_blocks[] = array(
                'blockName' => 'woocommerce/product-regular-price-field',
            );
        }
    
        return $inserted_blocks;
    }

    /**
     * Add price block 2.
     *
     * This price block does not have attributes and will later be overwritten by a 2nd price block's attributes.
     */
    public function add_price_block_2( $inserted_blocks, $anchor_block, $position, $context ) {
        if ( 
            'woocommerce/product-name-field' === $anchor_block['blockName'] &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $inserted_blocks[] = array(
                'blockName' => 'woocommerce/product-regular-price-field',
                'attrs'     => array(
                    'label' => 'Price block 2',
                )
            );
        }
    
        return $inserted_blocks;
    }

    /**
     * Add a block to a previously hooked block.
     * 
     * This will fail since block hooks don't run on blocks that were also hooked in.
     */
    public function add_block_to_hooked_block( $inserted_blocks, $anchor_block, $position, $context ) {
        if ( 
            'woocommerce/product-regular-price-field' === $anchor_block['blockName'] &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $inserted_blocks[] = array(
                'blockName' => 'woocommerce/product-sale-price-field',
            );
        }
    
        return $inserted_blocks;
    }
}