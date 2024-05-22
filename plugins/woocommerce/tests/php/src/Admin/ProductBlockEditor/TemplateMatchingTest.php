<?php

namespace Automattic\WooCommerce\Tests\Admin\ProductBlockEditor;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplate;
use WC_Unit_Test_Case;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\TemplateMatching;
use WC_Helper_Product;
/**
 * Tests for the BlockRegistry class.
 */
class TemplateMatchingTest extends WC_Unit_Test_Case {
	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		$this->product_templates = array(
			new ProductTemplate(
				array(
					'id'                 => 'standard-product-template',
					'title'              => '',
					'description'        => '',
					'icon'               => '',
					'layout_template_id' => 'simple-product',
					'product_data'       => array(
						'type' => 'simple',
					),
				)
			),
			new ProductTemplate(
				array(
					'id'                 => 'variable-product-template',
					'title'              => '',
					'description'        => '',
					'icon'               => '',
					'layout_template_id' => 'variable-product',
					'product_data'       => array(
						'type' => 'variable',
					),
				)
			),
			new ProductTemplate(
				array(
					'id'                 => 'meta-product-template',
					'title'              => '',
					'description'        => '',
					'icon'               => '',
					'layout_template_id' => 'meta-product',
					'product_data'       => array(
						'type'      => 'simple',
						'meta_data' => array(
							array(
								'key'   => 'key',
								'value' => 'value',
							),
						),
					),
				)
			),
			new ProductTemplate(
				array(
					'id'                 => 'match-product-template',
					'title'              => '',
					'description'        => '',
					'icon'               => '',
					'layout_template_id' => 'match-product',
					'product_data'       => array(
						'type'      => 'simple',
						'meta_data' => array(
							array(
								'key'   => 'key',
								'value' => 'value',
							),
						),
					),
					'match_fn'           => function( $product ) {
						return $product->get_regular_price() === '15';
					},
				)
			),
		);
	}
	/**
	 * Product templates
	 * @var array
	 */
	private $product_templates = array();

	/**
	 * Test that matches standard prod template.
	 */
	public function test_matches_standard_prod_template() {
		$product             = WC_Helper_Product::create_simple_product( false, array( 'type' => 'simple' ) );
		$product_template_id = TemplateMatching::determine_product_template(
			$product,
			$this->product_templates
		);
		$this->assertEquals( 'standard-product-template', $product_template_id );

	}

	/**
	 * Test that matches meta prod template.
	 */
	public function test_matches_meta_prod_template() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			array(
				'type' => 'simple',
			)
		);
		$product->set_meta_data(
			array(
				array(
					'id'    => 123,
					'key'   => 'key',
					'value' => 'value',
				),
			),
		);
		$product_template_id = TemplateMatching::determine_product_template(
			$product,
			$this->product_templates
		);
		$this->assertEquals( 'meta-product-template', $product_template_id );

	}

	/**
	 * Test that matches 'match-product-template'.
	 */
	public function test_match_fn_bypasses_rest() {
		$product             = WC_Helper_Product::create_simple_product(
			false,
			array(
				'type'          => 'simple',
				'regular_price' => '15',
			)
		);
		$product_template_id = TemplateMatching::determine_product_template(
			$product,
			$this->product_templates
		);
		$this->assertEquals( 'match-product-template', $product_template_id );
	}
}
