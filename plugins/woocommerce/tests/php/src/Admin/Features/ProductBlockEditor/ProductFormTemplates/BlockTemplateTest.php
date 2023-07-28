<?php

namespace Automattic\WooCommerce\Tests\Admin\Features\ProductBlockEditor\ProductFormTemplates;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductFormTemplates\BlockTemplate;

use WC_Unit_Test_Case;

class BlockTemplateTest extends WC_Unit_Test_Case {
	public function test_generate_block_id() {
		$block_template = new BlockTemplate();

		$this->assertSame( 'test-block-id-1', $block_template->generate_block_id( 'test-block-id' ) );
	}

	public function test_add_block() {
		$block_template = new BlockTemplate();

		$block = $block_template->add_block( [
			'id'   => 'test-block-id',
			'blockName' => 'test-block-name',
		] );

		$this->assertSame( $block, $block_template->get_block_by_id( 'test-block-id' ) );
	}

	public function test_add_block_throws_exception_if_block_with_same_id_already_exists() {
		$block_template = new BlockTemplate();

		$block_template->add_block( [
			'id'   => 'test-block-id',
			'blockName' => 'test-block-name',
		] );

		$this->expectException( \ValueError::class );

		$block_template->add_block( [
			'id'   => 'test-block-id',
			'blockName' => 'test-block-name',
		] );
	}

	public function test_add_block_generates_id_if_not_provided() {
		$block_template = new BlockTemplate();

		$block = $block_template->add_block( [
			'blockName' => 'test-block-name',
		] );

		$this->assertSame( 'test-block-name-1', $block->get_id() );
	}

	public function test_get_block_by_id_returns_null_if_block_does_not_exist() {
		$block_template = new BlockTemplate();

		$this->assertNull( $block_template->get_block_by_id( 'test-block-id' ) );
	}

	public function test_get_block_by_id_returns_reference() {
		$block_template = new BlockTemplate();

		$block_template->add_block( [
			'id'   => 'test-block-id',
			'blockName' => 'test-block-name',
		] );

		$block = $block_template->get_block_by_id( 'test-block-id' );

		$block->set_order( 23 );

		$this->assertSame( 23, $block_template->get_block_by_id( 'test-block-id' )->get_order() );
	}
}
