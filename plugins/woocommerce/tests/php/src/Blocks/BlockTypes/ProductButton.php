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
	 * Previous WooCommerce options to restore after each test.
	 *
	 * @var array<string, mixed>
	 */
	private $previous_options = array();

	/**
	 * Set up test options.
	 */
	protected function setUp(): void {
		parent::setUp();

		foreach ( array( 'woocommerce_cart_redirect_after_add', 'woocommerce_enable_ajax_add_to_cart' ) as $option_name ) {
			$this->previous_options[ $option_name ] = get_option( $option_name, null );
			update_option( $option_name, 'no' );
		}
	}

	/**
	 * Restore test options.
	 */
	protected function tearDown(): void {
		foreach ( $this->previous_options as $option_name => $value ) {
			if ( null === $value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $value );
			}
		}

		parent::tearDown();
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
	 * @testdox Filtered add-to-cart text renders one visible Product Button for the expected product.
	 */
	public function test_filtered_add_to_cart_text_renders_visible_button(): void {
		$had_product      = array_key_exists( 'product', $GLOBALS );
		$previous_product = $GLOBALS['product'] ?? null;
		$product          = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Slice 071 Filtered Product Button',
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
			remove_filter( 'woocommerce_product_add_to_cart_text', $filter );
			WC_Helper_Product::delete_product( $product->get_id() );

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

		$original_theme        = get_stylesheet();
		$had_product           = array_key_exists( 'product', $GLOBALS );
		$previous_product      = $GLOBALS['product'] ?? null;
		$previous_cart         = WC()->cart;
		$had_enqueue_hook      = isset( $wp_filter['wp_enqueue_scripts'] );
		$previous_enqueue_hook = $had_enqueue_hook ? clone $wp_filter['wp_enqueue_scripts'] : null;
		$scripts               = wp_scripts();
		$previous_queue        = $scripts->queue;
		$previous_registered   = $scripts->registered;
		$product               = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Slice 071 Script Product Button',
				'regular_price' => '10',
			)
		);

		try {
			switch_theme( 'twentytwentyfour' );
			$this->assertTrue( wp_is_block_theme(), 'The script policy should run under a real block theme.' );

			wp_register_script(
				'wc-add-to-cart',
				WC()->plugin_url() . '/assets/js/frontend/add-to-cart.min.js',
				array( 'jquery', 'wc-jquery-blockui' ),
				WC_VERSION,
				true
			);
			$this->assertTrue( wp_script_is( 'wc-add-to-cart', 'registered' ), 'WooCommerce should register the real legacy handle.' );
			wp_enqueue_script( 'wc-add-to-cart' );
			$this->assertTrue( wp_script_is( 'wc-add-to-cart', 'enqueued' ), 'The legacy handle should start enqueued.' );

			$markup   = $this->render_product_button( $product );
			$callback = $this->get_dequeue_callback( $wp_filter['wp_enqueue_scripts'] ?? null );
			$this->assertNotSame( '', $markup, 'The registered Product Button should render before its frontend action runs.' );
			$this->assertNotNull( $callback, 'Rendering should queue the Product Button legacy-script callback.' );
			if ( null === $callback ) {
				return;
			}

			$callback[0]->dequeue_add_to_cart_scripts();
			$this->assertFalse( wp_script_is( 'wc-add-to-cart', 'enqueued' ), 'The registered block-theme callback should dequeue the legacy handle.' );
		} finally {
			if ( $had_enqueue_hook ) {
				$wp_filter['wp_enqueue_scripts'] = $previous_enqueue_hook;
			} else {
				unset( $wp_filter['wp_enqueue_scripts'] );
			}

			$scripts->queue      = $previous_queue;
			$scripts->registered = $previous_registered;
			switch_theme( $original_theme );
			WC()->cart = $previous_cart;
			WC_Helper_Product::delete_product( $product->get_id() );

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
		try {
			$this->render_product_button( $product );
		} finally {
			remove_filter( 'woocommerce_loop_add_to_cart_args', $filter );
		}

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
