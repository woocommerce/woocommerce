<?php
/**
 * SimpleProductTemplate
 */

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;
use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates\DownloadableProductTrait;

/**
 * Simple Product Template.
 */
class SimpleProductTemplate extends AbstractProductFormTemplate implements ProductFormTemplateInterface {

	/**
	 * Get the template slug.
	 */
	public function get_slug(): string {
		return 'simple-form';
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return __( 'Simple Product Form Template', 'woocommerce' );
	}

	/**
	 * Get the template description.
	 */
	public function get_description(): string {
		return __( 'Template for the simple product form', 'woocommerce' );
	}

	public function get_post_types() {

	}

	public function get_product_data() {

	}

}
