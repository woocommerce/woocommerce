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

			$collection_block  = array(
				'blockName' => 'woocommerce/product-collection',
				'attrs'     => array(
					'query' => array(
						'isProductCollectionBlock' => false,
					),
				),
			);
			$populated_wrapper = '<div class="wp-block-woocommerce-product-collection">Populated collection</div>';
			$empty_wrapper     = '<div class="wp-block-woocommerce-product-collection">Empty collection wrapper</div>';
			$no_results        = '<p>No results found</p>';

			apply_filters( 'render_block_woocommerce/product-template', '<ul><li>Product</li></ul>' );
			$this->assertSame(
				$populated_wrapper,
				apply_filters( 'render_block_woocommerce/product-collection', $populated_wrapper, $collection_block ),
				'A populated Product Collection should render its wrapper.'
			);

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
	 * @testdox Should add one render event initializer to each Product Collection.
	 */
	public function test_adds_one_render_event_init_per_collection(): void {
		$hook_snapshots      = $this->snapshot_render_hooks();
		$script_module_id    = 'woocommerce/product-collection';
		$module_was_enqueued = in_array( $script_module_id, wp_script_modules()->get_queue(), true );

		try {
			$renderer = new Renderer();

			foreach ( array( 'featured', 'on-sale', 'best-sellers' ) as $collection ) {
				$block_content = '<div class="wp-block-woocommerce-product-collection">Products</div>';
				$block         = array(
					'blockName' => 'woocommerce/product-collection',
					'attrs'     => array(
						'collection'      => $collection,
						'forcePageReload' => true,
						'query'           => array(
							'isProductCollectionBlock' => true,
						),
					),
				);

				$rendered  = $renderer->enhance_product_collection_with_interactivity( $block_content, $block );
				$processor = new \WP_HTML_Tag_Processor( $rendered );

				$this->assertTrue(
					$processor->next_tag( array( 'class_name' => 'wp-block-woocommerce-product-collection' ) ),
					"The {$collection} Product Collection root should remain present."
				);
				$this->assertSame(
					'woocommerce/product-collection',
					$processor->get_attribute( 'data-wp-interactive' ),
					"The {$collection} Product Collection should use the real interactive namespace."
				);
				$this->assertSame(
					1,
					substr_count( $rendered, 'data-wp-interactive="woocommerce/product-collection"' ),
					"The {$collection} Product Collection should declare its interactive namespace exactly once."
				);
				$this->assertSame(
					'callbacks.onRender',
					$processor->get_attribute( 'data-wp-init' ),
					"The {$collection} Product Collection should initialize its render event callback."
				);
				$this->assertSame(
					1,
					substr_count( $rendered, 'data-wp-init="callbacks.onRender"' ),
					"The {$collection} Product Collection should initialize its render event exactly once."
				);

				$context = json_decode( (string) $processor->get_attribute( 'data-wp-context' ), true );
				$this->assertIsArray( $context, "The {$collection} Product Collection context should be valid JSON." );
				$this->assertSame(
					$collection,
					$context['collection'] ?? null,
					"The {$collection} Product Collection should retain its collection context."
				);
			}

			$non_collection_content = '<div class="wp-block-query">Posts</div>';
			$this->assertSame(
				$non_collection_content,
				$renderer->enhance_product_collection_with_interactivity(
					$non_collection_content,
					array(
						'attrs' => array(
							'query' => array(
								'isProductCollectionBlock' => false,
							),
						),
					)
				),
				'Non-Product Collection markup should remain byte-identical.'
			);
		} finally {
			$this->restore_render_hooks( $hook_snapshots );
			if ( ! $module_was_enqueued ) {
				wp_dequeue_script_module( $script_module_id );
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
