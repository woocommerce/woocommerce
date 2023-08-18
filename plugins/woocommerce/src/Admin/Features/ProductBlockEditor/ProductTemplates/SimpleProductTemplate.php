<?php
/* SimpleProductTemplate */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\AbstractBlockTemplate;

class SimpleProductTemplate extends AbstractBlockTemplate {
	function __construct() {
		$this->add_general_group_blocks();
	}

	/**
	 * Add a custom block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_group( array $block_config ) {
		$block = new Group( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}

	/**
	 * Add a custom block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_section( array $block_config ): Section {
		$block = new Section( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}

	private function add_general_group_blocks() {
		$general_group = $this->add_group(
			[
				'id'         => 'general',
				'order'      => 10,
				'attributes' => array(
					'id'         => 'general',
					'title' => __( 'General', 'woocommerce' ),
				)
			]
		);
		$basic_details = $general_group->add_section( [
			'id'         => 'basic-details',
			'attributes' => array(
				'title'       => __( 'Basic details', 'woocommerce' ),
				'description' => __( 'This info will be displayed on the product page, category pages, social media, and search results.', 'woocommerce' ),
			)
		] );
		$basic_details->add_block( [
			'id'         => 'product-name',
			'blockName'  => 'woocommerce/product-name-field',
			'attributes' => array(
				'name'      => 'Product name',
				'autoFocus' => true,
			)
		] );
		$basic_details->add_block( [
			'id'         => 'product-summary',
			'blockName'  => 'woocommerce/product-summary-field',
		] );
		$basic_details->add_columns( [
			'id'         => 'product-regular-price',
			'blockName'  => 'woocommerce/product-regular-price-field',
			'attributes' => [
				'name'  => 'regular_price',
				'label' => __( 'List price', 'woocommerce' ),
				'help'  => __( 'Manage more settings in <PricingTab>Pricing.</PricingTab>', 'woocommerce' ),
			]
		], [
			'id'         => 'product-sale-price',
			'blockName'  => 'woocommerce/product-sale-price-field',
			'attributes' => [
				'label' => __( 'Sale price', 'woocommerce' ),
			]
		] );
	}
}
