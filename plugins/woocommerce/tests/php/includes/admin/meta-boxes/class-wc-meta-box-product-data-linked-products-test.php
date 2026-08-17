<?php
declare( strict_types = 1 );

/**
 * Tests for linked products saved through the classic product data meta box.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

/**
 * Class WC_Meta_Box_Product_Data_Linked_Products_Test
 */
class WC_Meta_Box_Product_Data_Linked_Products_Test extends WC_Unit_Test_Case {

	/**
	 * Product IDs created by the current test.
	 *
	 * @var int[]
	 */
	private $product_ids = array();

	/**
	 * Original POST data.
	 *
	 * @var array<string, mixed>
	 */
	private $original_post_data;

	/**
	 * Original WooCommerce cart.
	 *
	 * @var WC_Cart|null
	 */
	private $original_cart;

	/**
	 * Original global values and their presence.
	 *
	 * @var array<string, array{present: bool, value: mixed}>
	 */
	private $original_globals = array();

	/**
	 * Set up isolated request, cart, and template globals.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_post_data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test snapshots request state before exercising the real admin save seam.
		$this->original_cart      = WC()->cart;

		foreach ( array( 'post', 'product', 'woocommerce_loop' ) as $global_name ) {
			$this->original_globals[ $global_name ] = array(
				'present' => array_key_exists( $global_name, $GLOBALS ),
				'value'   => $GLOBALS[ $global_name ] ?? null,
			);
		}

		$_POST     = array();
		WC()->cart = new WC_Cart();
		wc_reset_loop();
	}

	/**
	 * Restore request, cart, and template globals and delete fixtures.
	 */
	public function tearDown(): void {
		try {
			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}

			wp_reset_postdata();
			wc_reset_loop();

			foreach ( array_reverse( $this->product_ids ) as $product_id ) {
				WC_Helper_Product::delete_product( $product_id );
			}
		} finally {
			$_POST     = $this->original_post_data;
			WC()->cart = $this->original_cart;

			foreach ( $this->original_globals as $global_name => $global ) {
				if ( $global['present'] ) {
					$GLOBALS[ $global_name ] = $global['value']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test template global.
				} else {
					unset( $GLOBALS[ $global_name ] );
				}
			}

			parent::tearDown();
		}
	}

	/**
	 * @testdox The classic product save keeps upsells and cross-sells distinct and renders each in its own section.
	 */
	public function test_save_persists_and_renders_distinct_linked_products(): void {
		$products = $this->create_linked_product_fixtures();

		$this->save_product_data(
			$products['main'],
			array(
				'upsell_ids'    => array( $products['upsell_one']->get_id(), $products['upsell_two']->get_id() ),
				'crosssell_ids' => array( $products['cross_sell_one']->get_id(), $products['cross_sell_two']->get_id() ),
			)
		);

		$fresh_main = wc_get_product( $products['main']->get_id() );
		$this->assertInstanceOf( WC_Product::class, $fresh_main, 'The saved main product should reload from the data store.' );
		$this->assertSame(
			array( $products['upsell_one']->get_id(), $products['upsell_two']->get_id() ),
			$fresh_main->get_upsell_ids( 'edit' ),
			'Upsell IDs should retain their exact submitted order.'
		);
		$this->assertSame(
			array( $products['cross_sell_one']->get_id(), $products['cross_sell_two']->get_id() ),
			$fresh_main->get_cross_sell_ids( 'edit' ),
			'Cross-sell IDs should retain their exact submitted order.'
		);
		$this->assertSame(
			array(),
			array_intersect( $fresh_main->get_upsell_ids( 'edit' ), $fresh_main->get_cross_sell_ids( 'edit' ) ),
			'Upsells and cross-sells should not be cross-wired.'
		);

		$upsell_output = $this->render_upsells( $fresh_main );
		$this->assertStringContainsString( 'section class="up-sells upsells products"', $upsell_output, 'The classic upsell section should render.' );
		$this->assertStringContainsString( $products['upsell_one']->get_name(), $upsell_output, 'The first upsell should render in the upsell section.' );
		$this->assertStringContainsString( $products['upsell_two']->get_name(), $upsell_output, 'The second upsell should render in the upsell section.' );
		$this->assertStringNotContainsString( $products['cross_sell_one']->get_name(), $upsell_output, 'Cross-sells must not render in the upsell section.' );
		$this->assertStringNotContainsString( $products['cross_sell_two']->get_name(), $upsell_output, 'Cross-sells must not render in the upsell section.' );
		$this->assertStringNotContainsString( $fresh_main->get_name(), $upsell_output, 'The main product must not render as its own upsell.' );

		$cross_sell_output = $this->render_cross_sells( $fresh_main );
		$this->assertStringContainsString( 'div class="cross-sells"', $cross_sell_output, 'The classic cart cross-sell section should render.' );
		$this->assertStringContainsString( $products['cross_sell_one']->get_name(), $cross_sell_output, 'The first cross-sell should render in the cart section.' );
		$this->assertStringContainsString( $products['cross_sell_two']->get_name(), $cross_sell_output, 'The second cross-sell should render in the cart section.' );
		$this->assertStringNotContainsString( $products['upsell_one']->get_name(), $cross_sell_output, 'Upsells must not render in the cart cross-sell section.' );
		$this->assertStringNotContainsString( $products['upsell_two']->get_name(), $cross_sell_output, 'Upsells must not render in the cart cross-sell section.' );
		$this->assertStringNotContainsString( $fresh_main->get_name(), $cross_sell_output, 'The cart product must not render as its own cross-sell.' );
	}

	/**
	 * @testdox Omitting both linked-product fields clears their IDs and hides both classic sections.
	 */
	public function test_save_clears_and_hides_linked_products(): void {
		$products = $this->create_linked_product_fixtures();
		$main     = $products['main'];

		$main->set_upsell_ids( array( $products['upsell_one']->get_id(), $products['upsell_two']->get_id() ) );
		$main->set_cross_sell_ids( array( $products['cross_sell_one']->get_id(), $products['cross_sell_two']->get_id() ) );
		$main->save();

		$this->save_product_data( $main );

		$fresh_main = wc_get_product( $main->get_id() );
		$this->assertInstanceOf( WC_Product::class, $fresh_main, 'The cleared main product should reload from the data store.' );
		$this->assertSame( array(), $fresh_main->get_upsell_ids( 'edit' ), 'An omitted upsell field should clear every stored upsell ID.' );
		$this->assertSame( array(), $fresh_main->get_cross_sell_ids( 'edit' ), 'An omitted cross-sell field should clear every stored cross-sell ID.' );

		$upsell_output = $this->render_upsells( $fresh_main );
		$this->assertStringNotContainsString( 'section class="up-sells upsells products"', $upsell_output, 'An empty upsell collection should not render its section.' );
		$this->assertLinkedProductNamesAbsent( $products, $upsell_output, 'upsell' );

		$cross_sell_output = $this->render_cross_sells( $fresh_main );
		$this->assertStringNotContainsString( 'div class="cross-sells"', $cross_sell_output, 'An empty cross-sell collection should not render its section.' );
		$this->assertLinkedProductNamesAbsent( $products, $cross_sell_output, 'cross-sell' );
	}

	/**
	 * Create one main product and two distinct products for each linked family.
	 *
	 * @return array{main: WC_Product, upsell_one: WC_Product, upsell_two: WC_Product, cross_sell_one: WC_Product, cross_sell_two: WC_Product}
	 */
	private function create_linked_product_fixtures(): array {
		$names    = array(
			'main'           => 'Slice 070 Main Product',
			'upsell_one'     => 'Slice 070 Upsell Alpha',
			'upsell_two'     => 'Slice 070 Upsell Beta',
			'cross_sell_one' => 'Slice 070 Cross-sell Gamma',
			'cross_sell_two' => 'Slice 070 Cross-sell Delta',
		);
		$products = array();

		foreach ( $names as $key => $name ) {
			$product             = WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'          => $name,
					'regular_price' => '10',
				)
			);
			$this->product_ids[] = $product->get_id();
			$products[ $key ]    = $product;
		}

		return $products;
	}

	/**
	 * Save a product through the real classic meta-box seam.
	 *
	 * @param WC_Product          $product Product to save.
	 * @param array<string,mixed> $linked  Optional linked-product fields.
	 */
	private function save_product_data( WC_Product $product, array $linked = array() ): void {
		$_POST = array_merge( // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The test intentionally supplies the classic admin request.
			array(
				'product-type'      => 'simple',
				'_sku'              => '',
				'_regular_price'    => $product->get_regular_price( 'edit' ),
				'_sale_price'       => '',
				'_visibility'       => 'visible',
				'_tax_status'       => 'taxable',
				'_tax_class'        => '',
				'_stock_status'     => 'instock',
				'_backorders'       => 'no',
				'_download_limit'   => '',
				'_download_expiry'  => '',
				'_low_stock_amount' => '',
				'comment_status'    => 'open',
			),
			$linked
		);

		$post = get_post( $product->get_id() );
		$this->assertInstanceOf( WP_Post::class, $post, 'The product fixture should have a persisted post.' );
		WC_Meta_Box_Product_Data::save( $product->get_id(), $post );
	}

	/**
	 * Render the classic upsell template for a product.
	 *
	 * @param WC_Product $main Main product.
	 * @return string
	 */
	private function render_upsells( WC_Product $main ): string {
		$GLOBALS['post']    = get_post( $main->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Classic template setup requires the product post global.
		$GLOBALS['product'] = $main; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Classic upsell rendering reads the product global.
		wc_reset_loop();

		return $this->capture_output(
			static function (): void {
				woocommerce_upsell_display( -1, 4, 'none', 'asc' );
			}
		);
	}

	/**
	 * Render cart cross-sells derived from a real cart item.
	 *
	 * @param WC_Product $main Main product.
	 * @return string
	 */
	private function render_cross_sells( WC_Product $main ): string {
		WC()->cart->empty_cart();
		$cart_item_key = WC()->cart->add_to_cart( $main->get_id() );
		$this->assertNotFalse( $cart_item_key, 'The main product should enter the real cart before rendering cross-sells.' );
		wc_reset_loop();

		return $this->capture_output(
			static function (): void {
				woocommerce_cross_sell_display( -1, 2, 'none', 'asc' );
			}
		);
	}

	/**
	 * Capture output while restoring the caller's output-buffer depth on failure.
	 *
	 * @param callable(): void $callback Renderer.
	 * @return string
	 */
	private function capture_output( callable $callback ): string {
		$buffer_level = ob_get_level();
		ob_start();

		try {
			$callback();
			return (string) ob_get_clean();
		} finally {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
		}
	}

	/**
	 * Assert that no linked fixture name appears in rendered output.
	 *
	 * @param array<string, WC_Product> $products Product fixtures.
	 * @param string                    $output Rendered output.
	 * @param string                    $family Human-readable family label.
	 */
	private function assertLinkedProductNamesAbsent( array $products, string $output, string $family ): void {
		foreach ( $products as $key => $product ) {
			if ( 'main' === $key ) {
				continue;
			}

			$this->assertStringNotContainsString(
				$product->get_name(),
				$output,
				sprintf( 'Cleared %s output should omit %s.', $family, $product->get_name() )
			);
		}
	}
}
