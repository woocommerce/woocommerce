<?php
/**
 * Product Block Editor abstract product template.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

abstract class AbstractProductTemplate {

    /**
     * Block tree root.
     */
    const ROOT = 'root';

    /**
     * Index for the block properties.
     */
    const BLOCK_PROPERTIES_INDEX = 1;

    /**
     * Index for the child blocks.
     */
    const BLOCK_CHILDREN_INDEX = 2;

    /**
     * Property for the internal order.
     */
    const ORDER_PROPERTY = '_order';

    /**
     * The block template.
     */
    protected $template = array();

    /**
     * Cache of reference to inserted blocks location in tree.
     */
    protected $cache = array();

    /**
     * Set up the template.
     */
    public function __construct() {
        $this->add_group( array(
            'id'    => 'general2',
            'title' => __( 'General2', 'woocommerce' ),
            'order' => 20,
        ) );
        $this->add_group( $this->get_general_tab_args() );

        $this->add_section( $this->get_basic_section_args() );
        $this->add_field(
            array(
                'parent' => 'basic-details',
                'block'  => $this->get_name_field_args(),
                'order'  => '20',
            )
        );
        $this->add_field(
            array(
                'parent' => 'basic-details',
                'block'  => array( 'test-block' ),
                'order'  => '10',
            )
        );
    }

    /**
     * Add a field to the existing template.
     */
    protected function add_field( $field ) {
        $args   = wp_parse_args( $field, array( 'order' => 10 ) );
        $id     = $args['id'] ?? null;
        $block  = $args['block'];
        $parent = $args['parent'];
        $order  = $args['order'];

        $block[ self::BLOCK_PROPERTIES_INDEX ][ self::ORDER_PROPERTY ] = $order;

        $this->insert_block( $parent, $block, $id );
    }

    /**
     * Insert a block into the template tree.
     */
    private function insert_block( $parent, $block, $id ) {
        if ( $parent === self::ROOT ) {
            $blocks = &$this->template;
        } else {
            $blocks = &$this->cache[ $parent ][ self::BLOCK_CHILDREN_INDEX ];
        }

        $blocks[] = $block;

        if ( $id ) {
            $index = count( $blocks ) - 1;
            $this->cache[ $id ] = &$blocks[ $index ];
        }
    }

    /**
     * Add a group to the template.
     */
    protected function add_group( $args = array() ) {
        $args = wp_parse_args( $args, array( 'order' => 10 ) );

        $group = array(
            'woocommerce/product-tab',
            array(
                'id'    => $args['id'],
                'title' => $args['title'],
                'order' => $args['order'],
            ),
            array(),
        );

        $this->add_field(
            array(
                'id'     => $args['id'],
                'parent' => self::ROOT,
                'block'  => $group,
                'order'  => $args['order'] ?? null,
            )
        );
    }

    /**
     * Add a section to the template.
     */
    protected function add_section( $args = array() ) {
        $section = array(
            'woocommerce/product-section',
            array(
                'id'          => $args['id'],
                'title'       => $args['title'],
                'description' => $args['description'],
            ),
            array(),
        );

        $this->add_field(
            array(
                'id'     => $args['id'],
                'parent' => $args['parent'],
                'block'  => $section,
                'order'  => $args['order'] ?? null,
            )
        );
    }

    /**
     * Sort blocks recursively by the internal block attribute order property.
     */
    private function sort_by_order( $blocks ) {
        usort( $blocks, function( $a, $b ) {
            return $a[ self::BLOCK_PROPERTIES_INDEX ][ self::ORDER_PROPERTY ] > $b[ self::BLOCK_PROPERTIES_INDEX ][ self::ORDER_PROPERTY ] ? 1 : -1;
        } );
        foreach( $blocks as $index => $block ) {
            if ( isset( $block[ self::BLOCK_CHILDREN_INDEX ] ) ) {
                $blocks[ $index ][ self::BLOCK_CHILDREN_INDEX ] = $this->sort_by_order( $block[ self::BLOCK_CHILDREN_INDEX ] );
            }
        }
        return $blocks;
    }

    /**
     * Get the template.
     */
    public function get_template() {
        return $this->sort_by_order( $this->template );
    }

}