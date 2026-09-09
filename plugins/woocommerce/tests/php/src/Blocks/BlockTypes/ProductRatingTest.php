<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;
use WP_Block;

/**
 * Tests the product rating blocks' review links in different contexts.
 */
class ProductRatingTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Uses the global product and query context for review links, ignoring saved ancestry attributes.
	 * @dataProvider review_link_contexts
	 *
	 * @param string $block_name Block to render.
	 * @param string $context_name Product context to simulate.
	 * @param string $global_product_state Global product to simulate.
	 */
	public function test_review_links_use_global_product_and_query_context( string $block_name, string $context_name, string $global_product_state ): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_review_count( 2 );
		$product->set_average_rating( '4.5' );
		$product->set_reviews_allowed( true );
		$product->save();
		update_option( 'woocommerce_enable_reviews', 'yes' );
		$this->go_to( get_permalink( $product->get_id() ) );

		$context = array( 'postId' => $product->get_id() );
		if ( 'query-loop' === $context_name ) {
			$context['queryId'] = 0;
		}

		$sut = new WP_Block(
			array(
				'blockName'    => $block_name,
				'attrs'        => array(
					'isDescendentOfSingleProductBlock'    => 'query-loop' === $context_name,
					'isDescendentOfSingleProductTemplate' => 'query-loop' === $context_name,
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			),
			$context
		);

		$previous_product = $GLOBALS['product'] ?? null;
		try {
			if ( 'missing' === $global_product_state ) {
				unset( $GLOBALS['product'] );
			} elseif ( 'invalid' === $global_product_state ) {
				$GLOBALS['product'] = false;
			} elseif ( 'different' === $global_product_state ) {
				$GLOBALS['product'] = WC_Helper_Product::create_simple_product();
			} else {
				$GLOBALS['product'] = $product;
			}

			$expected_global_product = $GLOBALS['product'] ?? null;
			$html                    = $sut->render();
			$this->assertSame( $expected_global_product, $GLOBALS['product'] ?? null, 'Rendering a rating must not replace the global product.' );
		} finally {
			if ( null === $previous_product ) {
				unset( $GLOBALS['product'] );
			} else {
				$GLOBALS['product'] = $previous_product;
			}
		}
		$this->assertStringContainsString( 'wc-block-components-product-rating', $html, 'The rating block should render.' );

		if ( 'single-product-block' === $context_name ) {
			$this->assertStringContainsString( 'href="' . esc_url( $product->get_permalink() ) . '#reviews"', $html, 'Embedded products should link to their product page.' );
			$this->assertStringNotContainsString( 'woocommerce-review-link', $html, 'Embedded products should not use the current page review tab.' );
		} elseif ( 'single-product-template' === $context_name ) {
			$this->assertStringContainsString( 'class="woocommerce-review-link" rel="nofollow" href="#reviews"', $html, 'The product template should link to its review tab.' );
		} else {
			$this->assertStringNotContainsString( 'href=', $html, 'Query loop ratings should not inherit review links from the containing template.' );
			if ( 'woocommerce/product-rating' === $block_name ) {
				$this->assertStringNotContainsString( 'customer reviews', $html, 'The combined rating should omit the review count in query loops.' );
			} else {
				$this->assertStringContainsString( '2 customer reviews', $html, 'The separate counter should retain the review count in query loops.' );
			}
		}
	}

	/**
	 * @testdox Links to the product page when rendered inside a Single Product block on a regular page.
	 * @testWith ["woocommerce/product-rating"]
	 *           ["woocommerce/product-rating-counter"]
	 *
	 * @param string $block_name Block to render.
	 */
	public function test_review_links_inside_single_product_block( string $block_name ): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_review_count( 2 );
		$product->set_average_rating( '4.5' );
		$product->set_reviews_allowed( true );
		$product->save();
		update_option( 'woocommerce_enable_reviews', 'yes' );
		$this->go_to( home_url( '/' ) );

		$html = do_blocks(
			sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%1$d} --><div><!-- wp:%2$s /--></div><!-- /wp:woocommerce/single-product -->',
				$product->get_id(),
				$block_name
			)
		);

		$this->assertStringContainsString( 'href="' . esc_url( $product->get_permalink() ) . '#reviews"', $html, 'Ratings embedded on a regular page should link to the product reviews.' );
	}

	/**
	 * Provides the rating blocks, product contexts, and global product states.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public function review_link_contexts(): array {
		$contexts = array(
			array( 'single-product-template', 'matching' ),
			array( 'single-product-block', 'missing' ),
			array( 'single-product-block', 'invalid' ),
			array( 'single-product-block', 'different' ),
			array( 'query-loop', 'matching' ),
			array( 'query-loop', 'different' ),
		);
		$cases    = array();
		foreach ( array( 'woocommerce/product-rating', 'woocommerce/product-rating-counter' ) as $block_name ) {
			foreach ( $contexts as list( $context_name, $global_product_state ) ) {
				$cases[ $block_name . ' in ' . $context_name . ' with ' . $global_product_state . ' global product' ] = array( $block_name, $context_name, $global_product_state );
			}
		}
		return $cases;
	}
}
