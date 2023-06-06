<?php
/**
 * Product Block Editor abstract base product template.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

abstract class BaseProductTemplate {
    use CoreProductTemplate;

    /**
     * The block template.
     */
    protected $template = array();

    /**
     * Set up the template.
     */
    public function __construct() {
        $this->add_field( 'root', $this->get_general_block() );
        $test_block = array(
            'my-test-block'
        );
        $this->add_field( 'root', $test_block );
    }

    /**
     * Add a field to the existing template.
     */
    public function add_field( $parent, $block ) {
        $this->template[] = $block;
    }

    /**
     * Get the template.
     */
    public function get_template() {
        return $this->template;
    }
}