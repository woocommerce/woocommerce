<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Template block type.
 */
class ProductTemplateTest extends WC_Unit_Test_Case {

	/**
	 * Renders a Product Collection inside a Query Loop post content block.
	 *
	 * @param int $product_id Product ID.
	 * @param int $author_id  Outer post author ID.
	 * @return string Rendered block markup.
	 */
	private function render_product_collection_inside_query_loop( int $product_id, int $author_id ): string {
		$product_collection = $this->get_product_collection_markup( array( $product_id ) );
		$post_id            = self::factory()->post->create(
			array(
				'post_author'  => $author_id,
				'post_title'   => 'Post containing Product Collection',
				'post_content' => $product_collection,
			)
		);

		$query_loop = sprintf(
			'<!-- wp:query {"query":{"perPage":1,"postType":"post","order":"desc","orderBy":"date","author":"%1$d","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-content /--><!-- /wp:post-template --></div>
<!-- /wp:query -->',
			$author_id
		);

		try {
			return do_blocks( $query_loop );
		} finally {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Gets Product Collection block markup for a set of hand-picked products.
	 *
	 * @param int[] $product_ids Product IDs, in the order they should be rendered.
	 * @param int   $query_id    The block's `queryId` attribute (static, unaffected by pagination).
	 * @param int   $offset      The query's `offset` attribute, used to simulate a different page of the same query.
	 * @return string Product Collection block markup.
	 */
	private function get_product_collection_markup( array $product_ids, int $query_id = 0, int $offset = 0 ): string {
		$attributes = array(
			'queryId'    => $query_id,
			'query'      => array(
				'perPage'                       => count( $product_ids ),
				'pages'                         => 1,
				'offset'                        => $offset,
				'postType'                      => 'product',
				'order'                         => 'asc',
				'orderBy'                       => 'post__in',
				'search'                        => '',
				'exclude'                       => array(),
				'inherit'                       => false,
				'taxQuery'                      => array(),
				'isProductCollectionBlock'      => true,
				'featured'                      => false,
				'woocommerceOnSale'             => false,
				'woocommerceStockStatus'        => array( 'instock' ),
				'woocommerceAttributes'         => array(),
				'woocommerceHandPickedProducts' => $product_ids,
				'filterable'                    => false,
			),
			'collection' => 'woocommerce/product-collection/hand-picked',
		);

		return sprintf(
			'<!-- wp:woocommerce/product-collection %1$s -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image /-->
<!-- wp:woocommerce/product-price /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection -->',
			wp_json_encode( $attributes )
		);
	}

	/**
	 * Builds the expected serialized `data-wp-context---draft-key` bag for a given key.
	 *
	 * @param string $draft_key The minted draft key.
	 * @return string Expected attribute markup.
	 */
	private function expected_draft_key_context_directive( string $draft_key ): string {
		return 'data-wp-context---draft-key=\'woocommerce/cart::' . wp_json_encode(
			array( 'draftKey' => $draft_key ),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		) . '\'';
	}

	/**
	 * @testdox Should preserve product context when rendered inside a Query Loop post content block.
	 */
	public function test_preserves_product_context_inside_query_loop_post_content(): void {
		$product   = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => 25,
				'price'         => 25,
			)
		);
		$author_id = self::factory()->user->create();

		try {
			$markup = $this->render_product_collection_inside_query_loop( $product->get_id(), $author_id );

			$this->assertStringContainsString( 'wc-block-components-product-image', $markup, 'Product image should render using product context.' );
			$this->assertStringContainsString( 'wc-block-components-product-price', $markup, 'Product price should render using product context.' );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox A loop item's <li> carries the default woocommerce/products context and a data-wp-context---draft-key bag with the minted collection/<queryId>/<productId> key, no draft-items bag, and no data-wp-init.
	 */
	public function test_loop_item_emits_draft_key_context_and_no_retired_draft_items_bag(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => 25,
				'price'         => 25,
			)
		);

		try {
			$markup = do_blocks( $this->get_product_collection_markup( array( $product->get_id() ), 0 ) );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}

		$expected_products_context = 'data-wp-context=\'woocommerce/products::' . wp_json_encode(
			array(
				'productId'   => $product->get_id(),
				'variationId' => null,
			),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		) . '\'';
		$expected_draft_key_context = $this->expected_draft_key_context_directive( 'collection/0/' . $product->get_id() );

		$this->assertStringContainsString( $expected_products_context, $markup, 'The default woocommerce/products context should still be present, unaffected by the second context bag.' );
		$this->assertStringContainsString( $expected_draft_key_context, $markup, 'The minted collection/<queryId>/<productId> draft key should be present in a data-wp-context---draft-key bag.' );
		$this->assertStringNotContainsString( 'data-wp-context---draft-items', $markup, 'The retired empty draft-items bag should no longer be emitted.' );
		$this->assertStringNotContainsString( 'data-wp-init="woocommerce/cart::actions.registerOrRestoreDraftCollection"', $markup, 'The retired per-card register-or-restore init directive should no longer be emitted.' );
		$this->assertStringContainsString( 'data-wp-key="product-item-' . $product->get_id() . '"', $markup, 'The existing data-wp-key directive should still be present.' );
		$this->assertStringNotContainsString( 'data-wp-context---scope', $markup, 'No scope context bag should be emitted.' );
	}

	/**
	 * @testdox Two cards for different products in the same collection get distinct draft keys.
	 */
	public function test_two_products_in_same_collection_get_distinct_draft_keys(): void {
		$first_product  = WC_Helper_Product::create_simple_product( true );
		$second_product = WC_Helper_Product::create_simple_product( true );

		try {
			$markup = do_blocks( $this->get_product_collection_markup( array( $first_product->get_id(), $second_product->get_id() ), 0 ) );
		} finally {
			WC_Helper_Product::delete_product( $first_product->get_id() );
			WC_Helper_Product::delete_product( $second_product->get_id() );
		}

		$first_expected_key  = $this->expected_draft_key_context_directive( 'collection/0/' . $first_product->get_id() );
		$second_expected_key = $this->expected_draft_key_context_directive( 'collection/0/' . $second_product->get_id() );

		$this->assertStringContainsString( $first_expected_key, $markup, "The first card's draft key should discriminate by product id." );
		$this->assertStringContainsString( $second_expected_key, $markup, "The second card's draft key should discriminate by product id." );
		$this->assertNotSame( $first_expected_key, $second_expected_key, 'The two cards should carry different draft keys.' );
	}

	/**
	 * @testdox The same card renders the same draft key across successive renders, since queryId is a static attribute unchanged by pagination.
	 */
	public function test_draft_key_is_stable_across_successive_renders_with_same_query_id(): void {
		$product = WC_Helper_Product::create_simple_product( true );

		try {
			// Render the same card twice with the same `queryId` (a static parsed
			// block attribute, unaffected by pagination) to simulate a paginate-away-
			// and-back round trip: the card's key must not depend on anything but
			// its stable queryId and product id.
			$first_render  = do_blocks( $this->get_product_collection_markup( array( $product->get_id() ), 3 ) );
			$second_render = do_blocks( $this->get_product_collection_markup( array( $product->get_id() ), 3 ) );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}

		$expected_key = $this->expected_draft_key_context_directive( 'collection/3/' . $product->get_id() );

		$this->assertStringContainsString( $expected_key, $first_render, 'The first render should carry the minted draft key.' );
		$this->assertStringContainsString( $expected_key, $second_render, 'The second render should carry the same minted draft key.' );
	}

	/**
	 * @testdox The minted draft key is present in the inner-block render context, reaching descendant blocks through the priority-1 render_block_context filter.
	 */
	public function test_minted_draft_key_is_available_in_inner_block_render_context(): void {
		$product = WC_Helper_Product::create_simple_product( true );

		$captured_contexts = array();
		$capture_filter    = static function ( $context ) use ( &$captured_contexts ) {
			$captured_contexts[] = $context;
			return $context;
		};

		// Registered at a later priority than ProductTemplate's own priority-1
		// filter, so it observes the context after `draftKey` has been merged in.
		add_filter( 'render_block_context', $capture_filter, 2 );

		try {
			do_blocks( $this->get_product_collection_markup( array( $product->get_id() ), 0 ) );
		} finally {
			remove_filter( 'render_block_context', $capture_filter, 2 );
			WC_Helper_Product::delete_product( $product->get_id() );
		}

		$expected_key = 'collection/0/' . $product->get_id();
		$matches      = array_filter(
			$captured_contexts,
			static function ( $context ) use ( $expected_key ) {
				return isset( $context['draftKey'] ) && $expected_key === $context['draftKey'];
			}
		);

		$this->assertNotEmpty( $matches, 'The minted draft key should be present in the inner-block render context.' );
	}
}
