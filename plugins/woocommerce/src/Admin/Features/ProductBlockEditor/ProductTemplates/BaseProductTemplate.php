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
     * Cache of reference to inserted blocks location in tree.
     */
    protected $cache = array();

    /**
     * Set up the template.
     */
    public function __construct() {
        $this->add_tab( $this->get_general_tab_args() );
        $this->add_section( $this->get_basic_section_args() );
        $this->add_field( 'basic-details', $this->get_name_field_args() );
    }

    /**
     * Add a field to the existing template.
     */
    protected function add_field( $parent, $block, $id = null ) {
        $this->insert_block( $parent, $block, $id );
    }

    /**
     * Insert a block into the template tree.
     */
    private function insert_block( $parent, $block, $id ) {
        if ( $parent === self::ROOT ) {
            $blocks = &$this->template;
        } else {
            $blocks = &$this->cache[ $parent ][ self::CHILD_BLOCKS_INDEX ];
        }

        $index = $id ? $id : count( $blocks );
        $blocks[ $index ] = $block;

        if ( $id ) {
            $this->cache[ $id ] = &$blocks[ $index ];
        }
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

        $this->add_field( self::ROOT, $tab, $args['id'] );
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

        $this->add_field( $args['parent'], $tab, $args['id'] );
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