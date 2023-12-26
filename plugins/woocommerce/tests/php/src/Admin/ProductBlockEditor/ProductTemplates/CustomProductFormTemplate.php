<?php

namespace Automattic\WooCommerce\Tests\Admin\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates\AbstractProductFormTemplate;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;

class CustomProductFormTemplate extends AbstractProductFormTemplate implements ProductFormTemplateInterface {
	/**
	 * SimpleProductTemplate constructor.
	 */
	public function __construct() {
		$general_group = $this->add_group(
			[
				'id'         => 'general',
				'order'      => 10,
				'attributes' => [
					'title' => 'General',
				],
			]
		);
		$basic_details = $general_group->add_section(
			[
				'id'         => 'basic-details',
				'attributes' => [
					'title'       => 'Basic details',
					'description' => 'Description',
				],
			]
		);
		$basic_details->add_block(
			[
				'id'         => 'product-name',
				'blockName'  => 'woocommerce/product-name-field',
				'attributes' => [
					'name'      => 'Product name',
					'autoFocus' => true,
				],
			]
		);
		$pricing_group = $this->add_group(
			[
				'id'         => 'pricing',
				'order'      => 20,
				'attributes' => [
					'title' => 'Pricing',
				],
			]
		);
		$product_pricing_section = $pricing_group->add_section(
			[
				'id'         => 'product-pricing-section',
				'attributes' => [
					'title'       => 'Pricing',
					'description' => '',
					'blockGap'    => 'unit-40',
				],
			]
		);
		$product_pricing_section->add_block(
			[
				'id'        => 'product-pricing-group-pricing-columns',
				'blockName' => 'core/columns',
			]
		);
	}
	/**
	 * Get the template ID.
	 */
	public function get_id(): string {
		return 'custom-product';
	}

	/**
	 * Get the template title.
	 */
	public function get_title(): string {
		return 'Custom Product Template';
	}
}
