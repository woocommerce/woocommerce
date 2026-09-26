<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * Tests for the Product Results Count block type.
 */
class ProductResultsCountTest extends WP_UnitTestCase {

	/**
	 * Whether the WooCommerce loop existed before the test.
	 *
	 * @var bool
	 */
	private bool $had_woocommerce_loop;

	/**
	 * WooCommerce loop value before the test.
	 *
	 * @var mixed
	 */
	private $original_woocommerce_loop;

	/**
	 * Set up the paginated product loop.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->had_woocommerce_loop      = array_key_exists( 'woocommerce_loop', $GLOBALS );
		$this->original_woocommerce_loop = $this->had_woocommerce_loop ? $GLOBALS['woocommerce_loop'] : null;

		wc_setup_loop(
			array(
				'is_paginated' => true,
				'total'        => 12,
				'per_page'     => 4,
				'current_page' => 2,
			)
		);
	}

	/**
	 * Restore the WooCommerce loop.
	 */
	public function tearDown(): void {
		wc_reset_loop();

		if ( $this->had_woocommerce_loop ) {
			$GLOBALS['woocommerce_loop'] = $this->original_woocommerce_loop;
		} else {
			unset( $GLOBALS['woocommerce_loop'] );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Renders exact paginated output and query-specific Interactivity attributes.
	 */
	public function test_renders_paginated_result_count_with_query_context(): void {
		$markup = do_blocks(
			'<!-- wp:query {"queryId":17} --><div class="wp-block-query"><!-- wp:woocommerce/product-results-count /--></div><!-- /wp:query -->'
		);
		$p      = new WP_HTML_Tag_Processor( $markup );

		$this->assertTrue( $p->next_tag( array( 'class_name' => 'wc-block-product-results-count' ) ), 'The rendered result count wrapper should exist.' );
		$this->assertSame( 'woocommerce/product-results-count', $p->get_attribute( 'data-wp-interactive' ), 'The wrapper should declare the Product Results Count Interactivity store.' );
		$this->assertSame( 'wc-product-results-count-17', $p->get_attribute( 'data-wp-router-region' ), 'The router region should include the inherited query ID.' );
		$class_tokens = preg_split( '/\s+/', trim( (string) $p->get_attribute( 'class' ) ) );
		$class_tokens = is_array( $class_tokens ) ? array_values( array_filter( $class_tokens ) ) : array();
		$this->assertContains( 'woocommerce', $class_tokens, 'The wrapper should include the WooCommerce class.' );
		$this->assertContains( 'wp-block-woocommerce-product-results-count', $class_tokens, 'The wrapper should include the WordPress block class.' );

		$text = html_entity_decode( wp_strip_all_tags( $markup ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );
		$this->assertIsString( $text );
		$this->assertStringContainsString( 'Showing 5–8 of 12 results', $text, 'The block should expose the exact second-page result range.' );
	}

	/**
	 * @testdox Uses the default router region when no query context is present.
	 */
	public function test_uses_default_router_region_without_query_context(): void {
		$markup = do_blocks( '<!-- wp:woocommerce/product-results-count /-->' );
		$p      = new WP_HTML_Tag_Processor( $markup );

		$this->assertTrue( $p->next_tag( array( 'class_name' => 'wc-block-product-results-count' ) ), 'The standalone result count wrapper should exist.' );
		$this->assertSame( 'wc-product-results-count-0', $p->get_attribute( 'data-wp-router-region' ), 'The standalone router region should use the default query ID.' );
	}
}
