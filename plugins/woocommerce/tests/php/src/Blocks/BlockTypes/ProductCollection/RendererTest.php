<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Renderer;
use WC_Helper_Product;
use WC_Unit_Test_Case;

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
	 * @testdox Should extend context only for Product Collection and its descendants, including through Group and Product Template blocks.
	 */
	public function test_scopes_context_to_collection_descendants(): void {
		remove_all_filters( 'render_block_context' );
		new Renderer();
		$product  = WC_Helper_Product::create_simple_product();
		$contexts = array();
		add_filter(
			'render_block_context',
			function ( $context, $parsed_block ) use ( &$contexts ) {
				$anchor = $parsed_block['attrs']['anchor'] ?? '';
				if ( $anchor ) {
					$contexts[ $anchor ] = $context;
				}
				return $context;
			},
			12,
			2
		);

		$markup = '<!-- wp:group {"anchor":"outside-group"} --><div class="wp-block-group">'
			. '<!-- wp:paragraph {"anchor":"outside-child"} --><p>Outside</p><!-- /wp:paragraph -->'
			. '<!-- wp:woocommerce/product-collection {"anchor":"collection","query":{"isProductCollectionBlock":false,"postType":"product","perPage":1,"inherit":false}} -->'
			. '<div class="wp-block-woocommerce-product-collection">'
			. '<!-- wp:group {"anchor":"inside-group"} --><div class="wp-block-group">'
			. '<!-- wp:group {"anchor":"nested-group"} --><div class="wp-block-group">'
			. '<!-- wp:paragraph {"anchor":"inside-child"} --><p>Inside</p><!-- /wp:paragraph -->'
			. '</div><!-- /wp:group --></div><!-- /wp:group -->'
			. '<!-- wp:woocommerce/product-template -->'
			. '<!-- wp:group {"anchor":"inside-template-group"} --><div class="wp-block-group">'
			. '<!-- wp:post-title {"anchor":"inside-product-title"} /-->'
			. '</div><!-- /wp:group -->'
			. '<!-- /wp:woocommerce/product-template -->'
			. '</div><!-- /wp:woocommerce/product-collection -->'
			. '<!-- wp:paragraph {"anchor":"outside-sibling"} --><p>After</p><!-- /wp:paragraph -->'
			. '</div><!-- /wp:group -->';

		render_block( parse_blocks( $markup )[0] );

		foreach ( array( 'collection', 'inside-group', 'nested-group', 'inside-child', 'inside-template-group', 'inside-product-title' ) as $anchor ) {
			$this->assertArrayHasKey( $anchor, $contexts, "The {$anchor} block must render." );
			$this->assertSame( 'woocommerce/product-collection', $contexts[ $anchor ]['iapi/provider'] ?? null, "The {$anchor} block should use Product Collection actions." );
			$this->assertTrue( $contexts[ $anchor ]['isProductCollectionInnerBlock'] ?? false, "The {$anchor} block should be marked as a collection descendant." );
		}
		$this->assertSame( $product->get_id(), $contexts['inside-product-title']['postId'] ?? null, 'The title must render for the product in the Product Template loop.' );

		foreach ( array( 'outside-group', 'outside-child', 'outside-sibling' ) as $anchor ) {
			$this->assertArrayHasKey( $anchor, $contexts, "The {$anchor} block must render." );
			$this->assertArrayNotHasKey( 'iapi/provider', $contexts[ $anchor ], "The {$anchor} block must not receive a Product Collection provider." );
			$this->assertArrayNotHasKey( 'isProductCollectionInnerBlock', $contexts[ $anchor ], "The {$anchor} block must not be marked as a collection descendant." );
			$this->assertArrayNotHasKey( 'productCollectionLocation', $contexts[ $anchor ], "The {$anchor} block must not receive a Product Collection location." );
		}
	}

	/**
	 * @testdox Should reset result and No Results state between Product Collection renders.
	 */
	public function test_resets_render_state_between_collections(): void {
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
			// _restore_hooks() rewinds every RENDER_HOOKS stack on its own, so only
			// process state needs undoing here. The script-module queue is one such
			// piece. The other is the Interactivity store: enhancing the markup runs
			// render_interactivity_notices_region(), which writes
			// wp_interactivity_state( 'woocommerce/store-notices', ... ). That one is
			// left alone deliberately -- it writes the same namespaced values every
			// time and no test reads them -- but it is not reset for us either, so a
			// test that starts asserting on that namespace has to restore it.
			if ( ! $module_was_enqueued ) {
				wp_dequeue_script_module( $script_module_id );
			}
		}
	}
}
