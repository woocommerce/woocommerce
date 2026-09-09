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
	 * @testdox Uses live block context for review links, ignoring saved ancestry attributes.
	 * @dataProvider review_link_contexts
	 *
	 * @param string $block_name Block to render.
	 * @param string $context_name Product context to simulate.
	 */
	public function test_review_links_use_block_context( string $block_name, string $context_name ): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_review_count( 2 );
		$product->set_average_rating( '4.5' );
		$product->set_reviews_allowed( true );
		$product->save();
		update_option( 'woocommerce_enable_reviews', 'yes' );
		$this->go_to( get_permalink( $product->get_id() ) );

		$context = array( 'postId' => $product->get_id() );
		if ( 'single-product-block' === $context_name ) {
			$context['singleProduct'] = true;
		} elseif ( 'query-loop' === $context_name ) {
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

		$html = $sut->render();
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
	 * @testdox Receives review-link context from the actual Single Product parent block.
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

		$this->assertStringContainsString( 'href="' . esc_url( $product->get_permalink() ) . '#reviews"', $html, 'The parent block should supply the context needed for product review links.' );
	}

	/**
	 * Provides the rating blocks and their product contexts.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function review_link_contexts(): array {
		$cases = array();
		foreach ( array( 'woocommerce/product-rating', 'woocommerce/product-rating-counter' ) as $block_name ) {
			foreach ( array( 'single-product-block', 'single-product-template', 'query-loop' ) as $context_name ) {
				$cases[ $block_name . ' in ' . $context_name ] = array( $block_name, $context_name );
			}
		}
		return $cases;
	}
}
