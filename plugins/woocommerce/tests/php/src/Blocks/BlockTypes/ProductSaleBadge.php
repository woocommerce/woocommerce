<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

/**
 * Tests for the ProductSaleBadge block type
 */
class ProductSaleBadge extends \WP_UnitTestCase {

	/**
	 * @testdox Product Sale Badge does not render for a regular-price product.
	 */
	public function test_product_sale_badge_does_not_render_for_regular_price(): void {
		global $product;

		$had_product      = array_key_exists( 'product', $GLOBALS );
		$original_product = $had_product ? $product : null;
		$product          = new \WC_Product_Simple();

		try {
			$product->set_name( 'Regular Product' );
			$product->set_regular_price( '10' );
			$product_id = $product->save();

			$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge /--></div><!-- /wp:woocommerce/single-product -->' );

			$this->assertStringNotContainsString( 'wp-block-woocommerce-product-sale-badge', $markup, 'The outer Sale Badge block should be omitted.' );
			$this->assertStringNotContainsString( 'wc-block-components-product-sale-badge', $markup, 'The Sale Badge component should be omitted.' );
			$this->assertStringNotContainsString( 'Sale', $markup, 'Sale text should be omitted.' );
		} finally {
			if ( $product->get_id() ) {
				$product->delete( true );
			}

			if ( $had_product ) {
				$product = $original_product;
			} else {
				unset( $GLOBALS['product'] );
			}
		}
	}

	/**
	 * @testdox Product Sale Badge renders the $align alignment class.
	 *
	 * @dataProvider provider_product_sale_badge_alignment
	 * @param string $align Alignment value.
	 */
	public function test_product_sale_badge_renders_alignment_class( string $align ): void {
		global $product;

		$had_product      = array_key_exists( 'product', $GLOBALS );
		$original_product = $had_product ? $product : null;
		$product          = new \WC_Product_Simple();

		try {
			$product->set_name( 'Sale Product' );
			$product->set_regular_price( '10' );
			$product->set_sale_price( '5' );
			$product_id = $product->save();

			$markup         = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge {"align":"' . $align . '"} /--></div><!-- /wp:woocommerce/single-product -->' );
			$expected_class = 'wc-block-components-product-sale-badge--align-' . $align;

			$this->assertStringContainsString( $expected_class, $markup );
			foreach ( array_diff( array( 'left', 'center', 'right' ), array( $align ) ) as $other_align ) {
				$this->assertStringNotContainsString(
					'wc-block-components-product-sale-badge--align-' . $other_align,
					$markup,
					'The Sale Badge should contain only its requested alignment class.'
				);
			}
		} finally {
			if ( $product->get_id() ) {
				$product->delete( true );
			}

			if ( $had_product ) {
				$product = $original_product;
			} else {
				unset( $GLOBALS['product'] );
			}
		}
	}

	/**
	 * @testdox Product Sale Badge displays the configured content for a simple sale product.
	 * @dataProvider provider_product_sale_badge_content
	 * @param array  $attributes Badge attributes.
	 * @param string $expected Expected badge text.
	 */
	public function test_product_sale_badge_content( array $attributes, string $expected ): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '20' );
		$product->set_sale_price( '15' );
		$product->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge ' . wp_json_encode( (object) $attributes ) . ' /--></div><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wc-block-components-product-sale-badge__text" aria-hidden="true">' . $expected . '</span>', $markup );
		$this->assertStringContainsString( 'screen-reader-text">Product on sale: ' . $expected . '</span>', $markup );
	}

	/**
	 * Sale badge content options.
	 *
	 * @return array<string, array{array, string}>
	 */
	public function provider_product_sale_badge_content(): array {
		return array(
			'empty'      => array( array( 'saleText' => '' ), 'Sale' ),
			'zero text'  => array( array( 'saleText' => '0' ), '0' ),
			'custom'     => array(
				array( 'saleText' => 'Special offer' ),
				'Special offer',
			),
			'percentage' => array(
				array(
					'badgeContent' => 'percentage',
					'prefix'       => 'Save ',
					'suffix'       => ' off',
				),
				'Save 25% off',
			),
			'amount'     => array(
				array(
					'badgeContent' => 'amount',
					'prefix'       => 'Save ',
				),
				'Save $5.00',
			),
		);
	}

	/**
	 * @testdox Percentage badges omit zero-rounded discounts while preserving the rounding boundary.
	 * @dataProvider provider_percentage_badge_rounding
	 * @param string $type Product type.
	 * @param string $sale Sale price.
	 * @param string $mode Badge content mode.
	 * @param string $expected Expected badge text, or empty for an omitted badge.
	 */
	public function test_percentage_badge_rounding( string $type, string $sale, string $mode, string $expected ): void {
		$product = 'variable' === $type ? \WC_Helper_Product::create_variation_product() : \WC_Helper_Product::create_simple_product();
		$priced  = 'variable' === $type ? wc_get_product( $product->get_children()[0] ) : $product;
		$priced->set_regular_price( '1000' );
		$priced->set_sale_price( $sale );
		$priced->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge {"badgeContent":"' . $mode . '"} /--></div><!-- /wp:woocommerce/single-product -->' );

		if ( '' === $expected ) {
			$this->assertStringNotContainsString( 'wc-block-components-product-sale-badge', $markup, 'Zero-rounded percentage badges should be omitted.' );
		} else {
			$this->assertStringContainsString( 'aria-hidden="true">' . $expected . '</span>', $markup );
		}
	}

	/**
	 * Percentage rounding and unchanged amount-mode cases.
	 *
	 * @return array
	 */
	public function provider_percentage_badge_rounding(): array {
		return array(
			'simple zero'     => array( 'simple', '996', 'percentage', '' ),
			'simple half'     => array( 'simple', '995', 'percentage', '1%' ),
			'variable zero'   => array( 'variable', '996', 'percentage', '' ),
			'variable half'   => array( 'variable', '995', 'percentage', 'Up to 1%' ),
			'simple amount'   => array( 'simple', '996', 'amount', '$4.00' ),
			'variable amount' => array( 'variable', '996', 'amount', 'Up to $4.00' ),
		);
	}

	/**
	 * @testdox Variable product badge uses each variation's own regular price for the largest discount.
	 */
	public function test_variable_product_sale_badge_displays_maximum_discount(): void {
		$product    = \WC_Helper_Product::create_variation_product();
		$variations = $product->get_children();

		$first = wc_get_product( $variations[0] );
		$first->set_sale_price( '5' );
		$first->save();

		$second = wc_get_product( $variations[1] );
		$second->set_sale_price( '9' );
		$second->save();

		foreach (
			array(
				'percentage' => 'Up to 50%',
				'amount'     => 'Up to $6.00',
			) as $mode => $expected
		) {
			$attributes = array(
				'badgeContent' => $mode,
				'prefix'       => 'Save ',
				'suffix'       => ' off',
			);
			$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge ' . wp_json_encode( $attributes ) . ' /--></div><!-- /wp:woocommerce/single-product -->' );
			$this->assertStringContainsString( 'wc-block-components-product-sale-badge__text" aria-hidden="true">' . $expected . '</span>', $markup );
			$this->assertStringContainsString( 'screen-reader-text">Product on sale: ' . $expected . '</span>', $markup );
		}
	}

	/**
	 * @testdox Uniform variation discounts retain affixes only in the matching content mode.
	 * @dataProvider provider_uniform_variation_discounts
	 * @param string $sale Second variation sale price.
	 * @param string $mode Badge content mode.
	 * @param string $expected Expected badge text.
	 */
	public function test_uniform_variation_discounts( string $sale, string $mode, string $expected ): void {
		$product = \WC_Helper_Product::create_variation_product();
		foreach ( $product->get_children() as $index => $id ) {
			$variation = wc_get_product( $id );
			$variation->set_regular_price( 0 === $index ? '10' : '20' );
			$variation->set_sale_price( 0 === $index ? '9' : $sale );
			$variation->save();
		}
		$attributes = array(
			'badgeContent' => $mode,
			'prefix'       => 'Save ',
			'suffix'       => ' off',
		);
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge ' . wp_json_encode( $attributes ) . ' /--></div><!-- /wp:woocommerce/single-product -->' );
		$this->assertStringContainsString( 'aria-hidden="true">' . $expected . '</span>', $markup );
		$this->assertStringContainsString( 'screen-reader-text">Product on sale: ' . $expected . '</span>', $markup );
	}

	/**
	 * Uniform percentages, uniform amounts, and undiscounted variations.
	 *
	 * @return array
	 */
	public function provider_uniform_variation_discounts(): array {
		return array(
			'equal percentage'       => array( '18', 'percentage', 'Save 10% off' ),
			'different amount'       => array( '18', 'amount', 'Up to $2.00' ),
			'equal amount'           => array( '19', 'amount', 'Save $1.00 off' ),
			'different percentage'   => array( '19', 'percentage', 'Up to 10%' ),
			'undiscounted variation' => array( '20', 'percentage', 'Up to 10%' ),
		);
	}

	/**
	 * @testdox Discount amount reflects prices shown with tax on the storefront.
	 */
	public function test_discount_amount_includes_displayed_tax(): void {
		add_filter( 'wc_tax_enabled', '__return_true' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );
		$tax_id = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => '',
			)
		);

		try {
			$simple = \WC_Helper_Product::create_simple_product();
			$simple->set_regular_price( '100' );
			$simple->set_sale_price( '80' );
			$simple->save();

			$variable  = \WC_Helper_Product::create_variation_product();
			$variation = wc_get_product( $variable->get_children()[0] );
			$variation->set_regular_price( '100' );
			$variation->set_sale_price( '80' );
			$variation->save();

			foreach (
				array(
					$simple->get_id()   => '$24.00',
					$variable->get_id() => 'Up to $24.00',
				) as $product_id => $expected
			) {
				$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge {"badgeContent":"amount"} /--></div><!-- /wp:woocommerce/single-product -->' );
				$this->assertStringContainsString( 'wc-block-components-product-sale-badge__text" aria-hidden="true">' . $expected . '</span>', $markup );
			}
		} finally {
			\WC_Tax::_delete_tax_rate( $tax_id );
			remove_filter( 'wc_tax_enabled', '__return_true' );
		}
	}

	/**
	 * @testdox Variable badges bind their labels and visibility to Interactivity API state.
	 */
	public function test_variable_badge_interactivity(): void {
		$product   = \WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );
		$variation->set_sale_price( '5' );
		$variation->save();
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge {"badgeContent":"percentage","prefix":"Save ","suffix":" off"} /--></div><!-- /wp:woocommerce/single-product -->' );
		$html   = new \WP_HTML_Tag_Processor( $markup );
		$html->next_tag( array( 'class_name' => 'wp-block-woocommerce-product-sale-badge' ) );
		$context = json_decode( $html->get_attribute( 'data-wp-context' ), true );
		$this->assertSame( 'state.isSaleBadgeHidden', $html->get_attribute( 'data-wp-bind--hidden' ) );
		$this->assertSame( 'Up to 50%', $context['saleBadgeText'] );
		$this->assertSame( 'percentage', $context['badgeContent'] );
		$this->assertSame( 'Save ', $context['prefix'] );
		$this->assertStringContainsString( 'data-wp-text="state.saleBadgeText"', $markup );
		$this->assertStringContainsString( 'data-wp-text="state.saleBadgeScreenReaderText"', $markup );
		$this->assertStringContainsString( '>Up to 50%</span>', $markup );
	}

	/**
	 * Alignment values for the Sale Badge.
	 *
	 * @return array<string, array{string}>
	 */
	public function provider_product_sale_badge_alignment(): array {
		return array(
			'left'   => array( 'left' ),
			'center' => array( 'center' ),
			'right'  => array( 'right' ),
		);
	}

	/**
	 * Tests that the Product Sale Badge block is rendered correctly on the Single Product Block
	 */
	public function test_product_sale_badge_render_single_product_block() {
		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge /--></div><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wp-block-woocommerce-product-sale-badge', $markup, 'The Single Product Block contains the Product Sale Badge block.' );
		$this->assertStringContainsString( 'Sale', $markup, 'The Product Sale Badge block contains the sale text.' );

		$product->delete();
	}

	/**
	 * Tests that the woocommerce_sale_badge_text filter works correctly in Single Product block.
	 */
	public function test_product_sale_badge_render_single_product_block_with_custom_text() {
		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );
		$product_id = $product->save();

		$default_sale_text = null;
		/** @var \WC_Product|null */
		$received_product = null;

		add_filter(
			'woocommerce_sale_badge_text',
			function ( $sale_text, $product_obj ) use ( &$default_sale_text, &$received_product ) {
				$default_sale_text = $sale_text;
				$received_product  = $product_obj;
				return 'Special Offer!';
			},
			10,
			2
		);

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/product-sale-badge /--></div><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wp-block-woocommerce-product-sale-badge', $markup, 'The Single Product Block contains the Product Sale Badge block.' );
		$this->assertStringContainsString( 'Special Offer!', $markup, 'The Product Sale Badge block contains the custom sale text.' );
		$this->assertStringNotContainsString( 'Sale', $markup, 'The Product Sale Badge block does not contain the default sale text.' );

		$this->assertInstanceOf( \WC_Product::class, $received_product, 'The filter received a WC_Product object.' );
		/**
		 * Check that the filter received the correct parameters.
		 */
		$this->assertEquals( $product_id, $received_product->get_id(), 'The filter received the correct product object.' );
		$this->assertEquals( 'Sale', $default_sale_text, 'The default sale text is not modified.' );

		remove_all_filters( 'woocommerce_sale_badge_text' );
		$product->delete();
	}

	/**
	 * Tests that the woocommerce_sale_badge_text filter works correctly in Product Collection block.
	 */
	public function test_product_sale_badge_render_product_collection_block_with_custom_text() {
		$product1 = \WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 20 );
		$product1->set_sale_price( 15 );
		$product1->save();

		$product2 = \WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 30 );
		$product2->set_sale_price( 25 );
		$product2->save();

		$product_ids = array( $product1->get_id(), $product2->get_id() );

		$default_sale_text = null;
		/** @var \WC_Product|null */
		$received_product = null;

		add_filter(
			'woocommerce_sale_badge_text',
			function ( $sale_text, $product_obj ) use ( &$default_sale_text, &$received_product ) {
				$default_sale_text = $sale_text;
				$received_product  = $product_obj;
				$sale_price        = (float) $product_obj->get_sale_price();
				if ( $sale_price < 20 ) {
					return 'Special Deal!';
				}
				return 'Limited Time!';
			},
			10,
			2
		);

		$collection_block  = '<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"isProductCollectionBlock":true,"woocommerceHandPickedProducts":[' . implode( ',', $product_ids ) . ']}} -->';
		$collection_block .= '<!-- wp:woocommerce/product-template -->';
		$collection_block .= '<!-- wp:woocommerce/product-sale-badge /-->';
		$collection_block .= '<!-- /wp:woocommerce/product-template -->';
		$collection_block .= '<!-- /wp:woocommerce/product-collection -->';

		$markup = do_blocks( $collection_block );

		$this->assertStringContainsString( 'Special Deal!', $markup, 'Product with sale price < 20 should show "Special Deal!"' );
		$this->assertStringContainsString( 'Limited Time!', $markup, 'Product with sale price >= 20 should show "Limited Time!"' );
		$this->assertStringNotContainsString( 'Sale', $markup, 'Default "Sale" text should not appear when filter is applied' );

		$this->assertInstanceOf( \WC_Product::class, $received_product, 'The filter received a WC_Product object.' );
		$this->assertEquals( 'Sale', $default_sale_text, 'The default sale text is not modified.' );

		remove_all_filters( 'woocommerce_sale_badge_text' );

		$product1->delete();
		$product2->delete();
	}
}
