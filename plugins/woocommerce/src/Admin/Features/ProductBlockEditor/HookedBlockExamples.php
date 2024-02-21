<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * HookedBlockExamples.
 */
class HookedBlockExamples {

    public function init() {
        add_filter( 'hooked_block_types', array( $this, 'add_sku_field' ), 10, 4 );
        add_filter( 'hooked_block_types', array( $this, 'add_custom_product_tab_1' ), 10, 4 );
        add_filter( 'hooked_block_woocommerce/product-tab', array( $this, 'add_custom_product_tab_1_data' ), 10, 5 );
        add_filter( 'hooked_block_types', array( $this, 'add_custom_product_tab_2' ), 10, 4 );
        add_filter( 'hooked_block_woocommerce/product-tab', array( $this, 'add_custom_product_tab_2_data' ), 10, 5 );
        add_filter( 'hooked_block_types', array( $this, 'add_price_block_1' ), 10, 4 );
        add_filter( 'hooked_block_types', array( $this, 'add_price_block_2' ), 10, 4 );
        add_filter( 'hooked_block_woocommerce/product-regular-price-field', array( $this, 'add_price_block_2_data' ), 10, 5 );
        add_filter( 'hooked_block_types', array( $this, 'add_block_to_hooked_block' ), 10, 4 );
    }

    /**
     * Add SKU field.
     *
     * This hook works because it is a unique block that doesn't require attributes and
     * is attached to another unique field that is not repeated.
     */
    public function add_sku_field( $hooked_blocks, $position, $anchor_block, $context ) {
        if ( 
            'woocommerce/product-name-field' === $anchor_block &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $hooked_blocks[] = 'woocommerce/product-sku-field';
        }
    
        return $hooked_blocks;
    }
    
    /**
     * Add the product tab intended to be "Custom tab 1"
     *
     * This gets added, but later needs to be hooked to include attributes.
     */
    public function add_custom_product_tab_1( $hooked_blocks, $position, $anchor_block, $context ) {
        if ( 
            'woocommerce/product-tab' === $anchor_block &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $hooked_blocks[] = 'woocommerce/product-tab';
        }
    
        return $hooked_blocks;
    }
    
    /**
     * Add the data for the previously hooked "Custom tab 1"
     *
     * Despite requiring a number of checks, this one works a intended.
     */
    public function add_custom_product_tab_1_data( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context ) {
        // Check if the block already has attrs to avoid altering other hooked blocks.
        if ( ! empty( $parsed_hooked_block['attrs'] ) ) {
            return $parsed_hooked_block;
        }
    
        // Prevent this block from hooking after all product tabs.
        if (
            ! isset( $parsed_anchor_block['blockName'] ) ||
            'woocommerce/product-tab' !== $parsed_anchor_block['blockName'] ||
            ! isset( $parsed_anchor_block['attrs']['id'] ) ||
            'pricing' !== $parsed_anchor_block['attrs']['id']
        ) {
            return null;
        }
    
        $parsed_hooked_block['attrs']['id'] = 'custom_tab_1';
        $parsed_hooked_block['attrs']['title'] = 'Custom Tab 1';
        return $parsed_hooked_block;
    }
    
    /**
     * Add the product tab intended to be "Custom tab 1"
     *
     * This gets added, but later needs to be hooked to include attributes.
     */
    public function add_custom_product_tab_2( $hooked_blocks, $position, $anchor_block, $context ) {
        if ( 
            'woocommerce/product-tab' === $anchor_block &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $hooked_blocks[] = 'woocommerce/product-tab';
        }
    
        return $hooked_blocks;
    }
    
    /**
     * Add the data for the previously hooked "Custom tab 2"
     *
     * This callback will return early since attributes will always
     * be set by the `add_custom_product_tab_1_data` callback first.
     */
    public function add_custom_product_tab_2_data( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context ) {
        // Check if the block already has attrs to avoid altering other hooked blocks.
        if ( ! empty( $parsed_hooked_block['attrs'] ) ) {
            return $parsed_hooked_block;
        }
    
        // Prevent this block from hooking after all product tabs.
        if (
            ! isset( $parsed_anchor_block['blockName'] ) ||
            'woocommerce/product-tab' !== $parsed_anchor_block['blockName'] ||
            ! isset( $parsed_anchor_block['attrs']['id'] ) ||
            'pricing' !== $parsed_anchor_block['attrs']['id']
        ) {
            return null;
        }
    
        $parsed_hooked_block['attrs']['id'] = 'custom_tab_2';
        $parsed_hooked_block['attrs']['title'] = 'Custom Tab 2';
        return $parsed_hooked_block;
    }

    /**
     * Add price block 1.
     *
     * This price block does not have attributes and will later be overwritten by a 2nd price block's attributes.
     */
    public function add_price_block_1( $hooked_blocks, $position, $anchor_block, $context ) {
        if ( 
            'woocommerce/product-name-field' === $anchor_block &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $hooked_blocks[] = 'woocommerce/product-regular-price-field';
        }
    
        return $hooked_blocks;
    }

    /**
     * Add price block 2.
     *
     * This price block does not have attributes and will later be overwritten by a 2nd price block's attributes.
     */
    public function add_price_block_2( $hooked_blocks, $position, $anchor_block, $context ) {
        if ( 
            'woocommerce/product-name-field' === $anchor_block &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $hooked_blocks[] = 'woocommerce/product-regular-price-field';
        }
    
        return $hooked_blocks;
    }

    /**
     * Add the data for the previously hooked "price block 2"
     *
     * This correctly sets data for price block 2 but also errantly sets
     * attributes for price block 1 since it does not contain the optional attributes.
     */
    public function add_price_block_2_data( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context ) {
        // Check if the block already has attrs to avoid altering other hooked blocks.
        if ( ! empty( $parsed_hooked_block['attrs'] ) ) {
            return $parsed_hooked_block;
        }
    
        // Prevent this block from hooking after all product tabs.
        if (
            isset( $parsed_anchor_block['blockName'] ) &&
            'woocommerce/product-name-field' !== $parsed_anchor_block['blockName']
        ) {
            return null;
        }
    
        $parsed_hooked_block['attrs']['label'] = 'Price block 2';
        return $parsed_hooked_block;
    }

    /**
     * Add a block to a previously hooked block.
     * 
     * This will fail since block hooks don't run on blocks that were also hooked in.
     */
    public function add_block_to_hooked_block(  $hooked_blocks, $position, $anchor_block, $context  ) {
        if ( 
            'woocommerce/product-regular-price-field' === $anchor_block &&
            'after' === $position &&
            'product-form' === $context->area
        ) {
            $hooked_blocks[] = 'woocommerce/product-sale-price-field';
        }
    
        return $hooked_blocks;
    }
}