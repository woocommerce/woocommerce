<?php

namespace Automattic\WooCommerce\Tests\Admin\ProductBlockEditor\ProductTemplates;

use WC_Unit_Test_Case;

/**
 * Tests for the CustomBlockTemplate class.
 */
class CustomProductFormTemplateTest extends WC_Unit_Test_Case {
	/**
	 * Test getting the template ID.
	 */
	public function test_get_id() {
		$template = new CustomProductFormTemplate();
		$this->assertEquals( $template->get_id(), 'custom-product' );
	}

	/**
	 * Test get group block by id.
	 */
	public function test_get_group_by_id() {
		$template = new CustomProductFormTemplate();
		$block = $template->get_group_by_id( 'general' );
		$this->assertEquals( $block->get_attributes()['title'], 'General' );
	}

	/**
	 * Test throw error get group block with section id.
	 */
	public function test_get_group_by_id_with_section_id() {
		$template = new CustomProductFormTemplate();

		$this->expectException( \UnexpectedValueException::class );
		$template->get_group_by_id('basic-details' );
	}

	/**
	 * Test get section block by id.
	 */
	public function test_get_section_by_id() {
		$template = new CustomProductFormTemplate();
		$block = $template->get_section_by_id( 'product-pricing-section' );
		$this->assertEquals( $block->get_attributes()['title'], 'Pricing' );
	}

	/**
	 * Test throw error get section block with section id.
	 */
	public function test_get_section_by_id_with_block_id() {
		$template = new CustomProductFormTemplate();
		$this->expectException( \UnexpectedValueException::class );
		$template->get_section_by_id( 'product-pricing-group-pricing-columns' );
	}

	/**
	 * Test get block by id.
	 */
	public function test_get_block_by_id() {
		$template = new CustomProductFormTemplate();
		$block = $template->get_block_by_id( 'product-name' );
		$this->assertEquals( $block->get_attributes()['name'], 'Product name' );
	}

	/**
	 * Test add custom block to section.
	 */
	public function test_add_custom_block_to_section() {
		$template = new CustomProductFormTemplate();
		$block = $template->get_section_by_id( 'product-pricing-section' );
		$new_block = $block->add_block( [
			'id'        => 'test-block-id',
			'blockName' => 'test-block-name',
			'attributes' => [
				'name' => 'A name'
			]
		] );

		$this->assertEquals( $new_block->get_parent(), $block );
	}

	/**
	 * Test add custom group.
	 */
	public function test_add_custom_group() {
		$template = new CustomProductFormTemplate();
		$new_group = $template->add_group( [
			'id'        => 'new-group',
			'order'     => 0,
			'attributes' => [
				'title' => 'Group title'
			]
		] );

		$this->assertEquals( $new_group->get_parent(), $template );
	}

	/**
	 * Test throw error when passing blockName to add_group.
	 */
	public function test_passing_blockname_to_add_group() {
		$template = new CustomProductFormTemplate();
		$this->expectException( \InvalidArgumentException::class );
		$template->add_group( [
			'id'        => 'new-group',
			'blockName' => 'block-name',
			'order'     => 0,
			'attributes' => [
				'title' => 'Group title'
			]
		] );
	}

	/**
	 * Test throw error when passing blockName to add_section.
	 */
	public function test_passing_blockname_to_add_section() {
		$template = new CustomProductFormTemplate();
		$group = $template->get_group_by_id( 'general' );
		$this->expectException( \InvalidArgumentException::class );
		$group->add_section( [
			'id'        => 'new-section',
			'blockName' => 'block-name',
			'attributes' => [
				'title' => 'Section title'
			]
		] );
	}
}
