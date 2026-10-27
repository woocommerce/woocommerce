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
		'render_block_woocommerce/product-filters',
		'render_block_core/query-pagination',
		'render_block',
		'render_block_data',
		'render_block_context',
	);

	/**
	 * @testdox Should isolate duplicate collections without taking a later collection's saved ID.
	 * @dataProvider duplicate_collection_requests
	 *
	 * @param string $request_uri Request URL.
	 * @param string $second_title Expected product in the second collection.
	 * @param bool   $in_product_details Whether Product Details inspects and renders the collections.
	 * @param bool   $synced Whether the collections are stored in a synced pattern.
	 */
	public function test_duplicate_collection_controls( string $request_uri, string $second_title, bool $in_product_details = false, bool $synced = false ): void {
		foreach ( self::RENDER_HOOKS as $hook_name ) {
			remove_all_filters( $hook_name );
		}
		new Renderer();

		$products = array();
		foreach ( array( 'Identity A', 'Identity B', 'Identity C' ) as $name ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_name( $name );
			$product->set_stock_status( 'Identity B' === $name ? \Automattic\WooCommerce\Enums\ProductStockStatus::OUT_OF_STOCK : \Automattic\WooCommerce\Enums\ProductStockStatus::IN_STOCK );
			$product->save();
			$products[] = $product->get_id();
		}

		$markup = '';
		foreach ( array( array( 0, array_slice( $products, 0, 2 ) ), array( 0, array_slice( $products, 1 ) ), array( 1, array( $products[2] ) ) ) as list( $query_id, $product_ids ) ) {
			$attributes            = Utils::get_base_parsed_block()['attrs'];
			$attributes['queryId'] = $query_id;
			$attributes['query']   = array_merge(
				$attributes['query'],
				array(
					'inherit'                       => false,
					'perPage'                       => 1,
					'orderBy'                       => 'title',
					'order'                         => 'asc',
					'woocommerceHandPickedProducts' => $product_ids,
				)
			);
			$markup               .= '<!-- wp:woocommerce/product-collection ' . wp_json_encode( $attributes ) . ' --><div class="wp-block-woocommerce-product-collection">'
				. '<!-- wp:woocommerce/product-filters --><div class="wp-block-woocommerce-product-filters"></div><!-- /wp:woocommerce/product-filters -->'
				. '<!-- wp:woocommerce/product-template --><!-- wp:post-title /--><!-- /wp:woocommerce/product-template -->'
				. '<!-- wp:query-pagination --><!-- wp:query-pagination-next /--><!-- /wp:query-pagination -->'
				. '</div><!-- /wp:woocommerce/product-collection -->';
		}

		if ( $synced ) {
			$pattern_id = self::factory()->post->create(
				array(
					'post_type'    => 'wp_block',
					'post_status'  => 'publish',
					'post_content' => $markup,
				)
			);
			$markup     = '<!-- wp:block {"ref":' . $pattern_id . '} /-->';
		}

		if ( $in_product_details ) {
			add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			$markup = '<!-- wp:woocommerce/product-details --><div class="wp-block-woocommerce-product-details">'
				. '<!-- wp:accordion --><div class="wp-block-accordion">'
				. '<!-- wp:accordion-item {"openByDefault":true} --><div class="wp-block-accordion-item">'
				. '<!-- wp:accordion-panel --><div class="wp-block-accordion-panel">' . $markup
				. '</div><!-- /wp:accordion-panel --></div><!-- /wp:accordion-item -->'
				. '</div><!-- /wp:accordion --></div><!-- /wp:woocommerce/product-details -->';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Restore the original request verbatim after the test.
		$original_uri = $_SERVER['REQUEST_URI'] ?? null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the original request for restoration.
		$original_get = $_GET;
		try {
			$_SERVER['REQUEST_URI'] = $request_uri;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Simulate a public pagination request.
			parse_str( (string) wp_parse_url( $request_uri, PHP_URL_QUERY ), $_GET );
			$html     = do_blocks( $markup );
			$document = new \DOMDocument();
			$document->loadHTML( $html, LIBXML_NOERROR | LIBXML_NOWARNING );
			$xpath = new \DOMXPath( $document );
			foreach ( array(
				'0'        => 'Identity A',
				'0-copy-1' => $second_title,
				'1'        => 'Identity C',
			) as $identity => $title ) {
				$regions = $xpath->query( '//*[@data-wp-router-region="wc-product-collection-' . $identity . '"]' );
				$this->assertCount( 1, $regions, 'Each collection must have one distinct router region.' );
				$titles = $xpath->query( './/h2', $regions->item( 0 ) );
				$this->assertCount( 1, $titles, 'Each collection must retain its own product page.' );
				$this->assertSame( $title, $titles->item( 0 )->textContent, 'Filtering and pagination must affect only the targeted collection.' );
			}
		} finally {
			$_GET = $original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restore the original request after rendering.
			if ( null === $original_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $original_uri;
			}
		}
	}

	/**
	 * Independent controls for copied collections.
	 *
	 * @return array<string, array{0: string, 1: string, 2?: bool, 3?: bool}>
	 */
	public function duplicate_collection_requests(): array {
		return array(
			'initial'                   => array( '/', 'Identity B' ),
			'filter copy'               => array( '/?wcp-0-copy-1-filter_stock_status=instock', 'Identity C' ),
			'paginate copy'             => array( '/?query-0-copy-1-page=2', 'Identity C' ),
			'inspected panels'          => array( '/?wcp-0-copy-1-filter_stock_status=instock', 'Identity C', true ),
			'inspected synced patterns' => array( '/?wcp-0-copy-1-filter_stock_status=instock', 'Identity C', true, true ),
		);
	}

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
