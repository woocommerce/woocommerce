<?php
/**
 * Product Block Editor abstract product template.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

class WooCommerceBlockTemplate {

    /**
     * Index for the block properties.
     */
    const ATTRS_KEY = 'attrs';

    /**
     * Index for the child blocks.
     */
    const INNER_BLOCKS_KEY = 'innerBlocks';

    /**
     * Index for the child blocks.
     */
    const INNER_CONTENT_KEY = 'innerContent';

    /**
     * Property for the internal ID.
     */
    const ID_KEY = 'id';

    /**
     * Property for the internal order.
     */
    const ORDER_KEY = 'order';

    /**
     * Property for the parent key.
     */
    const PARENT_KEY = 'parent';

    /**
     * The block template.
     *
     * @var array
     */
    protected $template = array();

    /**
     * Cache of reference to inserted blocks location in tree.
     *
     * @var array
     */
    protected $cache = array();

    /**
     * Add a field to the existing template.
     *
     * @param array $block Block to add.
     */
    public function add_block( $block ) {
        $args   = wp_parse_args(
            $block,
            array(
                'order' => 10,
                'parent' => null,
            )
        );
        // @todo Create ID if one is not passed.
        $id     = $args[ self::ID_KEY ] ?? null;
        $parent = $args[ self::PARENT_KEY ];
        $order  = $args[ self::ORDER_KEY ];

        $block[ self::ATTRS_KEY ][ self::ORDER_KEY ] = $order;

        $this->insert_block( $parent, $args, $id );
    }

    /**
     * Insert a block into the template tree.
     *
     * @param integer|null $parent_id Parent ID.
     * @param array        $block Block.
     * @param integer      $id Block ID.
     */
    private function insert_block( $parent_id, $block, $id ) {
        $block = wp_parse_args(
            $block,
            array(
                'attrs'        => array(),
                'innerBlocks'  => array(),
                'innerContent' => array(),
            )
        );

        if ( ! $parent_id ) {
            $inner_blocks = &$this->template;
        } else {
            $inner_blocks = &$this->cache[ $parent_id ][ self::INNER_BLOCKS_KEY ];
            $inner_content = &$this->cache[ $parent_id ][ self::INNER_CONTENT_KEY ];
        }

        $inner_blocks[] = $block;
        $inner_content  = array_map( 'serialize_block', $inner_blocks );

        if ( $id ) {
            $index = count( $inner_blocks ) - 1;
            $this->cache[ $id ] = &$inner_blocks[ $index ];
        }
    }

    /**
     * Remove a block by ID.
     *
     * @param integer    $id ID of block to remove.
     * @param array|null $blocks_haystack Haystack in which to look for the block.
     * @return boolean   Returns true if removed, false if the block was not found.
     */
    public function remove_block( $id, &$blocks_haystack = null ) {
        if ( $blocks_haystack === null ) {
            $blocks_haystack = &$this->template;
        }

        foreach( $blocks_haystack as $key => &$block ) {
            if ( $block[ self::ID_KEY ] === $id ) {
                unset( $blocks_haystack[ $key ] );
                return true;
            }
            $result = $this->remove_block( $id, $block[ self::INNER_BLOCKS_KEY ] );
            if ( $result ) {
                unset( $this->cache[ $id ] );
                return $result;
            }
        }

        return false;
    }

    /**
     * Handling sorting and cleaning block properties recursively.
     *
     * @param array $blocks Blocks to sort recursively.
     * @return array Sorted blocks.
     */
    private function parse_blocks( $blocks ) {
        usort( $blocks, function( $a, $b ) {
            return isset( $a[ self::ATTRS_KEY ][ self::ORDER_KEY ] ) && isset( $b[ self::ATTRS_KEY ][ self::ORDER_KEY ] ) &&
                $a[ self::ATTRS_KEY ][ self::ORDER_KEY ] > $b[ self::ATTRS_KEY ][ self::ORDER_KEY ] ? 1 : -1;
        } );

        foreach( $blocks as $index => &$block ) {
            $block = $this->clean_block_properties( $block );
            if ( isset( $block[ self::INNER_BLOCKS_KEY ] ) ) {
                $blocks[ $index ][ self::INNER_BLOCKS_KEY ] = $this->parse_blocks( $block[ self::INNER_BLOCKS_KEY ] );
            }
        }
        return $blocks;
    }

    /**
     * Remove block attributes that are unnecessary in the parsed block.
     *
     * @param array $block Block.
     * @return array
     */
    private function clean_block_properties( $block ) {
        $properties_to_remove = array(
            self::ID_KEY,
            self::ORDER_KEY,
            self::PARENT_KEY,
        );
        foreach ( $properties_to_remove as $property ) {
            unset( $block[ $property ] );
        }
        return $block;
    }

    /**
     * Get the parsed template.
     *
     * @return array Parsed template.
     */
    public function get_parsed_template() {
        return $this->parse_blocks( $this->template );
    }

}