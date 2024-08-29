<?php

namespace Automattic\WooCommerce\Tests\Admin\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Internal\Features\ProductBlockEditor\ProductTemplates\AbstractProductFormTemplate;
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
		$inventory_group                 = $this->add_group(
			array(
				'id'         => 'inventory',
				'order'      => 20,
				'attributes' => array(
					'title' => 'Pricing',
				),
			)
		);
		$product_inventory_section       = $inventory_group->add_section(
			array(
				'id'         => 'product-inventory-section',
				'attributes' => array(
					'title'       => 'Inventory',
					'description' => '',
					'blockGap'    => 'unit-40',
				),
			)
		);
		$product_inventory_inner_section = $product_inventory_section->add_subsection(
			array(
				'id'         => 'product-stock-subsection',
				'attributes' => array(
					'title'       => 'Stock',
					'description' => '',
					'blockGap'    => 'unit-40',
				),
			)
		);
		$product_inventory_inner_section->add_block(
			array(
				'id'        => 'product-inventory-sku',
				'blockName' => 'woocommerce/product-sku-field',
			)
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
