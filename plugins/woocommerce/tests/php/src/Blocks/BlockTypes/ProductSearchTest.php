<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductSearch;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Search block type.
 */
class ProductSearchTest extends WC_Unit_Test_Case {

	/**
	 * The live-results script handle.
	 */
	private const LIVE_RESULTS_SCRIPT_HANDLE = 'wc-product-search-block-frontend';

	/**
	 * Create a Product Search instance without registering the deprecated block.
	 *
	 * @return ProductSearch
	 */
	private function create_block(): ProductSearch {
		return new class(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		) extends ProductSearch {
			/**
			 * Registration is already handled by WooCommerce during test bootstrap.
			 */
			protected function initialize() {
			}
		};
	}

	/**
	 * Reset global script state between tests.
	 */
	protected function tearDown(): void {
		wp_dequeue_script( self::LIVE_RESULTS_SCRIPT_HANDLE );
		parent::tearDown();
	}

	/**
	 * @testdox Adds live results only to the opted-in Product Search variation.
	 */
	public function test_add_live_results_for_opted_in_product_search(): void {
		$block   = $this->create_block();
		$content = '<form class="wp-block-search"><input type="search" /></form>';

		$result = $block->add_live_results(
			$content,
			array(
				'attrs' => array(
					'namespace'   => 'woocommerce/product-search',
					'liveResults' => true,
				),
			)
		);

		$this->assertStringContainsString( 'wc-block-product-search--live', $result );
		$this->assertTrue( wp_script_is( self::LIVE_RESULTS_SCRIPT_HANDLE, 'enqueued' ) );
	}

	/**
	 * @testdox Leaves core Search blocks and opt-out Product Search blocks unchanged.
	 */
	public function test_add_live_results_skips_blocks_without_the_opt_in(): void {
		$block   = $this->create_block();
		$content = '<form class="wp-block-search"><input type="search" /></form>';

		$result = $block->add_live_results(
			$content,
			array(
				'attrs' => array(
					'namespace' => 'woocommerce/product-search',
				),
			)
		);

		$this->assertSame( $content, $result );
		$this->assertFalse( wp_script_is( self::LIVE_RESULTS_SCRIPT_HANDLE, 'enqueued' ) );
	}
}
