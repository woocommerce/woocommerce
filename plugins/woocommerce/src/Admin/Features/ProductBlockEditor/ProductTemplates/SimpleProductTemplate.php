<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

/**
 * Simple product template.
 */
class SimpleProductTemplate extends AbstractProductTemplate implements ProductTemplateInterface {

    use BaseProductTemplate;

    /**
     * Set up the template.
     */
    public function __construct() {
        $this->add_base_template();
    }

	/**
	 * Get the name of the template.
	 *
	 * @return string Template name
	 */
    public function get_name() {
        return 'simple';
    }

}