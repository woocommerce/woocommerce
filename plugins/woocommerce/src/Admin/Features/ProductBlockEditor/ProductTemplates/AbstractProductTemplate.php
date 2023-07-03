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
    const BLOCK_PROPERTIES_INDEX = 'attrs';

    /**
     * Index for the child blocks.
     */
    const INNER_BLOCKS_INDEX = 'innerBlocks';

    /**
     * Index for the child blocks.
     */
    const INNER_CONTENT_INDEX = 'innerContent';

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
     * Add a field to the existing template.
     */
    public function add_field( $field ) {
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
        $block = wp_parse_args(
            $block,
            array(
                'innerBlocks'  => array(),
                'innerContent' => array(),
            )
        );

        if ( $parent === self::ROOT ) {
            $inner_blocks = &$this->template;
        } else {
            $inner_blocks = &$this->cache[ $parent ][ self::INNER_BLOCKS_INDEX ];
            $inner_content = &$this->cache[ $parent ][ self::INNER_CONTENT_INDEX ];
        }

        $inner_blocks[] = $block;
        $inner_content  = array_map( 'serialize_block', $inner_blocks );

        if ( $id ) {
            $index = count( $inner_blocks ) - 1;
            $this->cache[ $id ] = &$inner_blocks[ $index ];
        }
    }

    /**
     * Add a group to the template.
     */
    public function add_group( $args = array() ) {
        $args = wp_parse_args( $args, array( 'order' => 10 ) );

        $group = array(
            'blockName'  => 'woocommerce/product-tab',
            'attrs'      => array(
                'id'    => $args['id'],
                'title' => $args['title'],
                'order' => $args['order'],
            ),
            'innerBlocks' => array(),
            'innerContent' => array(),
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
    public function add_section( $args = array() ) {
        $section = array(
            'blockName'   => 'woocommerce/product-section',
            'attrs'       => array(
                'id'          => $args['id'],
                'title'       => $args['title'],
                'description' => $args['description'],
            ),
            'innerBlocks' => array(),
            'innerContent' => array(),
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
            return isset( $a[ self::BLOCK_PROPERTIES_INDEX ][ self::ORDER_PROPERTY ] ) && isset( $b[ self::BLOCK_PROPERTIES_INDEX ][ self::ORDER_PROPERTY ] ) &&
                $a[ self::BLOCK_PROPERTIES_INDEX ][ self::ORDER_PROPERTY ] > $b[ self::BLOCK_PROPERTIES_INDEX ][ self::ORDER_PROPERTY ] ? 1 : -1;
        } );
        foreach( $blocks as $index => $block ) {
            if ( isset( $block[ self::INNER_BLOCKS_INDEX ] ) ) {
                $blocks[ $index ][ self::INNER_BLOCKS_INDEX ] = $this->sort_by_order( $block[ self::INNER_BLOCKS_INDEX ] );
            }
        }
        return $blocks;
    }

    /**
     * Get the template.
     */
    public function get_template() {
        $template = $this->template;
        $template = $this->sort_by_order( $this->template );
        $serialized = serialize_blocks( $template );
        return $serialized;
    }

}