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
	 * Get the slug of the template.
	 *
	 * @return string Template slug
	 */
    public function get_slug() {
        return 'simple';
    }

    /**
	 * Get the title of the template.
	 *
	 * @return string Template title
	 */
    public function get_title() {
        return __( 'Simple product editor template.', 'woocommerce' );
    }

    /**
	 * Get the description for the template.
	 *
	 * @return string Template description
	 */
    public function get_description() {
        return __( 'Product template for editing simple product types.', 'woocommerce' );
    }

}