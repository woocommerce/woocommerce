<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductButton as ProductButtonBlock;
use WC_Helper_Product;

/**
 * Tests for the Product Button block type.
 */
class ProductButton extends \WP_UnitTestCase {

	/**
	 * Set up test options.
	 *
	 * Renders run with AJAX add to cart off unless a test turns it on.
	 * Both writes land inside the per-test transaction, which tear_down() rolls back.
	 */
	protected function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_cart_redirect_after_add', 'no' );
		update_option( 'woocommerce_enable_ajax_add_to_cart', 'no' );
	}

	/**
	 * Render the Product Button block for a product.
	 *
	 * @param \WC_Product $product Product to render.
	 * @return string Rendered block markup.
	 */
	private function render_product_button( \WC_Product $product ): string {
		return do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><!-- wp:woocommerce/product-button /--><!-- /wp:woocommerce/single-product -->'
		);
	}

	/**
	 * Render the Product Button block on its own, from the global product.
	 *
	 * A Single Product block rewrites data-wp-interactive and data-wp-context on the first
	 * div of its content, which is the Product Button wrapper when the button is its only
	 * inner block. Rendering the button alone keeps the button's own Interactivity context.
	 *
	 * @param \WC_Product $product Product to render. Left in the product global for the caller to restore.
	 * @return string Rendered block markup.
	 */
	private function render_product_button_from_global_product( \WC_Product $product ): string {
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The block falls back to the product global when it has no post context.

		return do_blocks( '<!-- wp:woocommerce/product-button /-->' );
	}

	/**
	 * Render the Product Button block from a post context, the way a product template supplies it.
	 *
	 * @param \WC_Product $product Product the post context points at.
	 * @return string Rendered block markup.
	 */
	private function render_product_button_from_post_context( \WC_Product $product ): string {
		$parsed_blocks = parse_blocks( '<!-- wp:woocommerce/product-button /-->' );
		$block         = new \WP_Block( $parsed_blocks[0], array( 'postId' => $product->get_id() ) );

		return $block->render();
	}

	/**
	 * @testdox Filtered add-to-cart text renders one non-AJAX Product Button link for the expected product.
	 */
	public function test_filtered_add_to_cart_text_renders_in_the_non_ajax_link(): void {
		$had_product      = array_key_exists( 'product', $GLOBALS );
		$previous_product = $GLOBALS['product'] ?? null;
		$product          = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Product Button add-to-cart text fixture',
				'regular_price' => '10',
			)
		);
		$filter           = static function (): string {
			return 'Buy Now';
		};

		add_filter( 'woocommerce_product_add_to_cart_text', $filter );

		try {
			$markup    = $this->render_product_button( $product );
			$processor = new \WP_HTML_Tag_Processor( $markup );
			$wrappers  = 0;

			while (
				$processor->next_tag(
					array(
						'tag_name'   => 'div',
						'class_name' => 'wc-block-components-product-button',
					)
				)
			) {
				++$wrappers;
			}

			$this->assertSame( 1, $wrappers, 'The registered composition should render exactly one Product Button wrapper.' );

			$processor = new \WP_HTML_Tag_Processor( $markup );
			$this->assertTrue(
				$processor->next_tag(
					array(
						'tag_name'   => 'a',
						'class_name' => 'wc-block-components-product-button__button',
					)
				),
				'The non-AJAX registered Product Button should render a link.'
			);
			$this->assertSame( (string) $product->get_id(), $processor->get_attribute( 'data-product_id' ), 'The rendered button should identify the fixture product.' );
			$this->assertStringContainsString( '>Buy Now</span>', $markup, 'The real product text filter should reach escaped Product Button markup.' );
		} finally {
			// _restore_hooks() drops the filter and the rollback drops the product;
			// the product global is this test's own to put back.
			if ( $had_product ) {
				$GLOBALS['product'] = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test product global.
			} else {
				unset( $GLOBALS['product'] );
			}
		}
	}

	/**
	 * @testdox Filtered add-to-cart text labels the AJAX Product Button rendered from the product global through its Interactivity context.
	 */
	public function test_filtered_add_to_cart_text_reaches_the_ajax_button_context(): void {
		$had_product      = array_key_exists( 'product', $GLOBALS );
		$previous_product = $GLOBALS['product'] ?? null;
		$product          = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Product Button AJAX add-to-cart text fixture',
				'regular_price' => '10',
			)
		);
		$filter           = static function (): string {
			return 'Buy Now';
		};

		update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
		add_filter( 'woocommerce_product_add_to_cart_text', $filter );

		try {
			$markup    = $this->render_product_button_from_global_product( $product );
			$processor = new \WP_HTML_Tag_Processor( $markup );

			$this->assertTrue(
				$processor->next_tag(
					array(
						'tag_name'   => 'button',
						'class_name' => 'wc-block-components-product-button__button',
					)
				),
				'A purchasable simple product should render the Product Button as a button when AJAX add to cart is on.'
			);
			$this->assertTrue( $processor->has_class( 'ajax_add_to_cart' ), 'The AJAX Product Button should carry the ajax_add_to_cart class.' );

			$processor = new \WP_HTML_Tag_Processor( $markup );
			$this->assertTrue(
				$processor->next_tag(
					array(
						'tag_name'   => 'div',
						'class_name' => 'wc-block-components-product-button',
					)
				),
				'The AJAX Product Button should render its wrapper.'
			);

			$context = json_decode( (string) $processor->get_attribute( 'data-wp-context' ), true );
			$this->assertIsArray( $context, 'The wrapper should carry an Interactivity context the client can decode.' );
			$this->assertArrayHasKey( 'addToCartText', $context, 'The Interactivity context should carry the button label.' );
			$this->assertSame( 'Buy Now', $context['addToCartText'], 'The real product text filter should reach the Interactivity context.' );

			$processor      = new \WP_HTML_Tag_Processor( $markup );
			$span_directive = null;

			while ( $processor->next_tag( array( 'tag_name' => 'span' ) ) ) {
				$directive = $processor->get_attribute( 'data-wp-text' );

				if ( null !== $directive ) {
					$span_directive = $directive;
					break;
				}
			}

			$this->assertSame( 'state.addToCartText', $span_directive, 'The AJAX Product Button label span should bind to the Interactivity state.' );
			$this->assertStringContainsString( '>Buy Now</span>', $markup, 'Server-side directive processing should label the AJAX button span from the Interactivity state.' );
		} finally {
			// _restore_hooks() drops the filter and the rollback drops both the product
			// and the option write; the product global is this test's own to put back.
			if ( $had_product ) {
				$GLOBALS['product'] = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test product global.
			} else {
				unset( $GLOBALS['product'] );
			}
		}
	}

	/**
	 * @testdox Filtered add-to-cart text labels an AJAX Product Button that takes its product from the post context.
	 */
	public function test_filtered_add_to_cart_text_reaches_the_ajax_button_resolved_from_post_context(): void {
		$had_product      = array_key_exists( 'product', $GLOBALS );
		$previous_product = $GLOBALS['product'] ?? null;
		$product          = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Product Button post-context add-to-cart text fixture',
				'regular_price' => '10',
			)
		);
		$filter           = static function (): string {
			return 'Buy Now';
		};

		unset( $GLOBALS['product'] );
		update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
		add_filter( 'woocommerce_product_add_to_cart_text', $filter );

		try {
			$markup    = $this->render_product_button_from_post_context( $product );
			$processor = new \WP_HTML_Tag_Processor( $markup );

			$this->assertTrue(
				$processor->next_tag(
					array(
						'tag_name'   => 'button',
						'class_name' => 'ajax_add_to_cart',
					)
				),
				'A product supplied through the post context should render the AJAX Product Button.'
			);
			$this->assertSame( (string) $product->get_id(), $processor->get_attribute( 'data-product_id' ), 'The button should render for the product the post context names.' );

			$processor = new \WP_HTML_Tag_Processor( $markup );
			$this->assertTrue(
				$processor->next_tag(
					array(
						'tag_name'   => 'div',
						'class_name' => 'wc-block-components-product-button',
					)
				),
				'The Product Button should render its wrapper.'
			);

			$context = json_decode( (string) $processor->get_attribute( 'data-wp-context' ), true );
			$this->assertIsArray( $context, 'The wrapper should carry an Interactivity context the client can decode.' );
			$this->assertArrayHasKey( 'addToCartText', $context, 'The Interactivity context should carry the button label.' );
			$this->assertSame( 'Buy Now', $context['addToCartText'], 'The real product text filter should reach the Interactivity context.' );
			$this->assertStringContainsString( '>Buy Now</span>', $markup, 'Server-side directive processing should label the button span from the Interactivity state.' );
		} finally {
			// _restore_hooks() drops the filter and the rollback drops both the product
			// and the option write; the product global is this test's own to put back.
			if ( $had_product ) {
				$GLOBALS['product'] = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test product global.
			} else {
				unset( $GLOBALS['product'] );
			}
		}
	}

	/**
	 * @testdox A block-theme Product Button dequeues the legacy add-to-cart script through its registered frontend action.
	 */
	public function test_block_theme_dequeues_legacy_add_to_cart_script(): void {
		global $wp_filter;

		$original_theme      = get_stylesheet();
		$had_product         = array_key_exists( 'product', $GLOBALS );
		$previous_product    = $GLOBALS['product'] ?? null;
		$previous_cart       = WC()->cart;
		$scripts             = wp_scripts();
		$previous_queue      = $scripts->queue;
		$previous_registered = $scripts->registered;
		$styles              = wp_styles();
		$previous_styles     = $styles->queue;
		$previous_style_reg  = $styles->registered;
		$product             = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Product Button script policy fixture',
				'regular_price' => '10',
			)
		);

		try {
			switch_theme( 'twentytwentyfour' );
			$this->assertTrue( wp_is_block_theme(), 'The script policy should run under a real block theme.' );

			// Let WooCommerce enqueue the handle rather than standing in for it. A test
			// that registers 'wc-add-to-cart' itself pins the same literal on both
			// sides, so core folding the legacy script into another handle would leave
			// dequeue_add_to_cart_scripts() a silent no-op with this test still green --
			// which is the regression the deleted E2E title used to catch.
			update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
			\WC_Frontend_Scripts::load_scripts();
			$this->assertTrue( wp_script_is( 'wc-add-to-cart', 'enqueued' ), 'WooCommerce should enqueue the legacy handle when AJAX add to cart is on.' );

			$markup   = $this->render_product_button( $product );
			$callback = $this->get_dequeue_callback( $wp_filter['wp_enqueue_scripts'] ?? null );
			$this->assertNotSame( '', $markup, 'The registered Product Button should render before its frontend action runs.' );
			$this->assertNotNull( $callback, 'Rendering should queue the Product Button legacy-script callback.' );

			// Fire the hook rather than calling the callback, so the assertion covers the
			// whole stack: a later callback re-enqueueing the handle would fail here and
			// would not if the method were invoked on its own. `_restore_hooks()` rewinds
			// the `$wp_actions` count and `$wp_current_filter` that firing it leaves.
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing core's own frontend action, not declaring one.
			do_action( 'wp_enqueue_scripts' );
			$this->assertFalse( wp_script_is( 'wc-add-to-cart', 'enqueued' ), 'The registered block-theme callback should dequeue the legacy handle.' );
		} finally {
			// _restore_hooks() rewinds wp_enqueue_scripts and the rollback takes the
			// product back. WP_Scripts, WC()->cart and the product global survive both:
			// this class extends WP_UnitTestCase, so there is no WooCommerce teardown.
			$scripts->queue      = $previous_queue;
			$scripts->registered = $previous_registered;
			// Firing wp_enqueue_scripts writes to WP_Styles too -- WooCommerce's own
			// frontend styles, plus core attaching the theme's global stylesheet to the
			// shared 'global-styles' handle, which outlives the switch_theme() below.
			$styles->queue      = $previous_styles;
			$styles->registered = $previous_style_reg;
			switch_theme( $original_theme );
			WC()->cart = $previous_cart;

			if ( $had_product ) {
				$GLOBALS['product'] = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test product global.
			} else {
				unset( $GLOBALS['product'] );
			}
		}
	}

	/**
	 * Find the Product Button's registered dequeue callback.
	 *
	 * @param \WP_Hook|null $hook Enqueue hook after rendering.
	 * @return array{ProductButtonBlock, string}|null
	 */
	private function get_dequeue_callback( ?\WP_Hook $hook ): ?array {
		if ( ! $hook ) {
			return null;
		}

		foreach ( $hook->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];
				if (
					is_array( $function ) &&
					$function[0] instanceof ProductButtonBlock &&
					'dequeue_add_to_cart_scripts' === $function[1]
				) {
					return $function;
				}
			}
		}

		return null;
	}

	/**
	 * @testdox Product permalink links do not include nofollow.
	 */
	public function test_product_permalink_does_not_include_nofollow(): void {
		$product = WC_Helper_Product::create_variation_product();
		$markup  = $this->render_product_button( $product );

		$this->assertStringContainsString( 'href="' . esc_url( $product->get_permalink() ) . '"', $markup );
		$this->assertStringContainsString( 'rel=""', $markup );
	}

	/**
	 * @testdox Product Button filter arguments retain an empty rel attribute for product permalink links.
	 */
	public function test_product_permalink_filter_args_include_empty_rel(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$filtered_args = null;
		$filter        = static function ( array $args ) use ( &$filtered_args ): array {
			$filtered_args = $args;
			return $args;
		};

		add_filter( 'woocommerce_loop_add_to_cart_args', $filter );
		$this->render_product_button( $product );

		$this->assertIsArray( $filtered_args );
		$this->assertArrayHasKey( 'rel', $filtered_args['attributes'] );
		$this->assertSame( '', $filtered_args['attributes']['rel'] );
	}

	/**
	 * @testdox Direct add-to-cart links retain nofollow.
	 */
	public function test_direct_add_to_cart_link_retains_nofollow(): void {
		$product = WC_Helper_Product::create_simple_product();
		$markup  = $this->render_product_button( $product );

		$this->assertStringContainsString( 'rel="nofollow"', $markup );
	}

	/**
	 * @testdox External product links retain nofollow and security attributes.
	 */
	public function test_external_product_link_retains_nofollow_and_security_attributes(): void {
		$product = WC_Helper_Product::create_external_product();
		$markup  = $this->render_product_button( $product );

		$this->assertStringContainsString( 'rel="nofollow noopener noreferrer"', $markup );
	}
}
