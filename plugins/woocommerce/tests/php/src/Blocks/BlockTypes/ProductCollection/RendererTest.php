<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Renderer;
use WC_Unit_Test_Case;
use WP_Hook;

/**
 * Tests for the Product Collection render lifecycle.
 */
class RendererTest extends WC_Unit_Test_Case {

	/**
	 * Hooks registered by the renderer constructor.
	 *
	 * @var string[]
	 */
	private const RENDER_HOOKS = array(
		'render_block_woocommerce/product-collection',
		'render_block_woocommerce/product-template',
		'render_block_woocommerce/product-collection-no-results',
		'render_block_core/query-pagination',
		'render_block_context',
	);

	/**
	 * @testdox Should reset result and No Results state between Product Collection renders.
	 */
	public function test_resets_render_state_between_collections(): void {
		$hook_snapshots = $this->snapshot_render_hooks();
		$global_product = $GLOBALS['product'] ?? null;
		$had_product    = array_key_exists( 'product', $GLOBALS );

		try {
			foreach ( self::RENDER_HOOKS as $hook_name ) {
				remove_all_filters( $hook_name );
			}

			new Renderer();

			$collection_block = array(
				'blockName' => 'woocommerce/product-collection',
				'attrs'     => array(
					'query' => array(
						'isProductCollectionBlock' => false,
					),
				),
			);
			$populated_wrapper = '<div class="wp-block-woocommerce-product-collection">Populated collection</div>';
			$empty_wrapper     = '<div class="wp-block-woocommerce-product-collection">Empty collection wrapper</div>';
			$no_results       = '<p>No results found</p>';

			apply_filters( 'render_block_woocommerce/product-template', '<ul><li>Product</li></ul>' );
			$this->assertSame(
				$populated_wrapper,
				apply_filters( 'render_block_woocommerce/product-collection', $populated_wrapper, $collection_block ),
				'A populated Product Collection should render its wrapper.'
			);

			apply_filters( 'render_block_woocommerce/product-template', '' );
			$this->assertSame(
				'',
				apply_filters( 'render_block_woocommerce/product-collection', $empty_wrapper, $collection_block ),
				'An empty collection must not inherit the previous collection result state.'
			);

			apply_filters( 'render_block_woocommerce/product-template', '' );
			$this->assertSame(
				$no_results,
				apply_filters( 'render_block_woocommerce/product-collection-no-results', $no_results ),
				'The explicit No Results block should pass through unchanged.'
			);
			$this->assertSame(
				$empty_wrapper,
				apply_filters( 'render_block_woocommerce/product-collection', $empty_wrapper, $collection_block ),
				'A collection with an explicit No Results block should render its wrapper.'
			);

			apply_filters( 'render_block_woocommerce/product-template', '' );
			$this->assertSame(
				'',
				apply_filters( 'render_block_woocommerce/product-collection', $empty_wrapper, $collection_block ),
				'An empty collection must not inherit the previous collection No Results state.'
			);

			apply_filters( 'render_block_woocommerce/product-template', '<ul><li>Another product</li></ul>' );
			$this->assertSame(
				$populated_wrapper,
				apply_filters( 'render_block_woocommerce/product-collection', $populated_wrapper, $collection_block ),
				'A later populated collection should render after the empty and No Results cases.'
			);
		} finally {
			$this->restore_render_hooks( $hook_snapshots );
			if ( $had_product ) {
				$GLOBALS['product'] = $global_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact product global captured before rendering.
			} else {
				unset( $GLOBALS['product'] );
			}
		}
	}

	/**
	 * Snapshot the hook stacks touched by the renderer constructor.
	 *
	 * @return array<string, WP_Hook|null>
	 */
	private function snapshot_render_hooks(): array {
		$snapshots = array();
		foreach ( self::RENDER_HOOKS as $hook_name ) {
			$snapshots[ $hook_name ] = isset( $GLOBALS['wp_filter'][ $hook_name ] ) ? clone $GLOBALS['wp_filter'][ $hook_name ] : null;
		}

		return $snapshots;
	}

	/**
	 * Restore the exact hook stacks captured before rendering.
	 *
	 * @param array<string, WP_Hook|null> $snapshots Hook snapshots.
	 */
	private function restore_render_hooks( array $snapshots ): void {
		foreach ( $snapshots as $hook_name => $snapshot ) {
			if ( $snapshot instanceof WP_Hook ) {
				$GLOBALS['wp_filter'][ $hook_name ] = clone $snapshot; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact hook stack captured before rendering.
			} else {
				unset( $GLOBALS['wp_filter'][ $hook_name ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Remove callbacks registered by the test renderer.
			}
		}
	}
}
