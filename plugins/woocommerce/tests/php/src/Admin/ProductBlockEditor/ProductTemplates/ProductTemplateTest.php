<?php

namespace Automattic\WooCommerce\Tests\Admin\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplate;

use WC_Unit_Test_Case;

/**
 * Tests for the ProductTemplate class.
 */
class ProductTemplateTest extends WC_Unit_Test_Case {
	/**
	 * Test is_product_type_supported() with a mismatched post type.
	 */
	public function test_is_product_type_supported_post_type_mismatch() {
		$template = new ProductTemplate(
			array(
				'post_type' => 'post',
			)
		);

		$this->assertFalse( $template->is_product_type_supported( 'simple', 'product' ) );
	}

	/**
	 * Test is_product_type_supported() with a mismatched product type.
	 */
	public function test_is_product_type_supported_product_type_mismatch() {
		$template = new ProductTemplate(
			array(
				'post_type'    => 'product',
				'product_data' => array(
					'type' => 'variable',
				),
			)
		);

		$this->assertFalse( $template->is_product_type_supported( 'simple', 'product' ) );
	}

	/**
	 * Test is_product_type_supported() with a matching product type.
	 */
	public function test_is_product_type_supported_product_type_match() {
		$template = new ProductTemplate(
			array(
				'post_type'    => 'product',
				'product_data' => array(
					'type' => 'simple',
				),
			)
		);

		$this->assertTrue( $template->is_product_type_supported( 'simple', 'product' ) );
	}

	/**
	 * Test is_product_type_supported() with a null product type and matching post type.
	 */
	public function test_is_product_type_supported_product_type_null_match() {
		$template = new ProductTemplate(
			array(
				'post_type'    => 'product_variation',
				'product_data' => array(
					'type' => null,
				),
			)
		);

		$this->assertTrue( $template->is_product_type_supported( null, 'product_variation' ) );
	}

	/**
	 * Test is_product_type_supported() with a matching alternate product type.
	 */
	public function test_is_product_type_supported_product_type_match_with_alternate_product_data() {
		$template = new ProductTemplate(
			array(
				'post_type'               => 'product',
				'product_data'            => array(
					'type' => 'simple',
				),
				'alternate_product_datas' => array(
					array(
						'type' => 'variable',
					),
				),
			)
		);

		$this->assertTrue( $template->is_product_type_supported( 'variable', 'product' ) );
	}

	/**
	 * Test is_product_type_supported() with a mismatched alternate product type.
	 */
	public function test_is_product_type_supported_product_type_mismatch_with_alternate_product_data() {
		$template = new ProductTemplate(
			array(
				'post_type'               => 'product',
				'product_data'            => array(
					'type' => 'simple',
				),
				'alternate_product_datas' => array(
					array(
						'type' => 'variable',
					),
				),
			)
		);

		$this->assertFalse( $template->is_product_type_supported( 'grouped', 'product' ) );
	}
}
