<?php
/**
 * Product Block Editor abstract base product template.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

abstract class BaseProductTemplate {
    use CoreProductTemplate;

    /**
     * Block tree root.
     */
    const ROOT = 'root';

    /**
     * Index for the child blocks.
     */
    const CHILD_BLOCKS_INDEX = 2;

    /**
     * The block template.
     */
    protected $template = array();

    /**
     * Set up the template.
     */
    public function __construct() {
        $this->add_tab( $this->get_general_tab_args() );
        $this->add_section( $this->get_basic_section_args() );
        $this->add_field( array( 'general', 'basic-details' ), $this->get_name_field_args() );
        
        $test_block = array(
            'my-test-block'
        );
        // $this->add_field( 'root', $test_block );
        $this->add_field( 'general', $test_block );
        $this->add_field( array( 'general', 'basic-details' ), $test_block );
    }

    /**
     * Add a field to the existing template.
     */
    protected function add_field( $parent, $block ) {
        $this->insert_block( $parent, $block );
    }

    private function insert_block( $parent, $block ) {
        $id = isset( $block[1]['id'] ) ? $block[1]['id'] : null;

        if ( $parent === self::ROOT ) {
            $insertion_point = &$this->template;
        } elseif ( is_array( $parent ) ) {
            $insertion_point = &$this->template[ $parent[0] ][ self::CHILD_BLOCKS_INDEX ];
            for ( $i = 1; $i < count( $parent ); $i++ ) {
                $insertion_point = &$insertion_point[ $parent[ $i ] ][ self::CHILD_BLOCKS_INDEX ];
            }
        } else {
            $insertion_point = &$this->template[ $parent ][ self::CHILD_BLOCKS_INDEX ];
        }

        $index = $id ? $id : count( $insertion_point );

        $insertion_point[ $index ] = $block;
    }

    /**
     * Add a tab to the template.
     */
    protected function add_tab( $args = array() ) {
        $args = wp_parse_args( $args, array( 'order' => 10 ) );

        $tab = array(
            'woocommerce/product-tab',
            array(
                'id'    => $args['id'],
                'title' => $args['title'],
                'order' => $args['order'],
            ),
            array(),
        );

        $this->add_field( self::ROOT, $tab );
    }

    /**
     * Add a section to the template.
     */
    protected function add_section( $args = array() ) {
        $args = wp_parse_args( $args, array( 'order' => 10 ) );

        $tab = array(
            'woocommerce/product-section',
            array(
                'id'          => $args['id'],
                'title'       => $args['title'],
                'description' => $args['description'],
                'order'       => $args['order'],
            ),
            array(),
        );

        $this->add_field( $args['parent'], $tab );
    }

    /**
     * Get the template.
     */
    public function get_template() {
        // @todo Remove associative array keys.
        // @todo Reorder array based on order property.
        return $this->template;
    }
}