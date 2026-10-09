<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutLink;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;

/**
 * Unit tests for CheckoutLink.
 */
class CheckoutLinkTest extends \WC_Unit_Test_Case {
	/**
	 * @testdox Installing-mode requests queue the endpoint rewrite without replacing persisted rules.
	 */
	public function test_endpoint_rewrite_is_deferred_during_installing_mode(): void {
		global $wp_rewrite;

		$original_installing     = wp_installing();
		$original_rules          = get_option( 'rewrite_rules', null );
		$original_queue          = get_option( 'woocommerce_queue_flush_rewrite_rules', null );
		$original_top_rules      = $wp_rewrite->extra_rules_top;
		$persisted_rewrite_rules = array( '^third-party/?$' => 'index.php?third-party=1' );

		update_option( 'rewrite_rules', $persisted_rewrite_rules );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		wp_installing( true );

		try {
			( new CheckoutLink() )->add_checkout_link_endpoint();
			$this->assertSame( $persisted_rewrite_rules, get_option( 'rewrite_rules' ), 'Installing mode must preserve the complete rules from the prior normal request.' );

			wp_installing( false );

			$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ), 'Installing mode should queue the missing checkout-link rule.' );
			$this->assertArrayHasKey( '^checkout-link$', $wp_rewrite->extra_rules_top, 'The endpoint should still register its rule for the current request.' );
		} finally {
			wp_installing( false );
			delete_option( 'rewrite_rules' );
			delete_option( 'woocommerce_queue_flush_rewrite_rules' );
			if ( null !== $original_rules ) {
				add_option( 'rewrite_rules', $original_rules );
			}
			if ( null !== $original_queue ) {
				add_option( 'woocommerce_queue_flush_rewrite_rules', $original_queue );
			}
			$wp_rewrite->extra_rules_top = $original_top_rules;
			wp_installing( $original_installing );
		}
	}

	/**
	 * @testdox Adds legacy products, quantities, SKUs, variations, additional data, and a coupon from a checkout link.
	 */
	public function test_products_and_coupon_are_added_and_token_in_url(): void {
		$legacy_product      = \WC_Helper_Product::create_simple_product();
		$quantity_product    = \WC_Helper_Product::create_simple_product();
		$sku_product         = \WC_Helper_Product::create_simple_product();
		$numeric_sku_product = \WC_Helper_Product::create_simple_product();
		$variable_product    = \WC_Helper_Product::create_variation_product();
		$available_options   = $variable_product->get_available_variations();
		$chosen_variation    = array_shift( $available_options );

		$sku_product->set_sku( 'CHECKOUT,LINK:SKU' );
		$sku_product->save();
		$numeric_sku_product->set_sku( '999999' );
		$numeric_sku_product->save();

		$_GET['products'] = implode(
			',',
			[
				(string) $legacy_product->get_id(),
				$quantity_product->get_id() . ':2',
				'CHECKOUT~,LINK~:SKU',
				'sku=999999',
				$variable_product->get_id() . ':1:' . http_build_query( $chosen_variation['attributes'], '', ';' ) . ':nyp=99;note=alpha~,beta~;gamma:delta',
			]
		);
		$_GET['coupon']   = 'test-coupon';
		CouponHelper::create_coupon( 'test-coupon' );

		$sut = $this->get_checkout_link_service();

		$url                  = $sut->get_checkout_link_test();
		$cart_by_product      = [];
		$expected_product_ids = [
			$legacy_product->get_id(),
			$quantity_product->get_id(),
			$sku_product->get_id(),
			$numeric_sku_product->get_id(),
			$variable_product->get_id(),
		];

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$cart_by_product[ $cart_item['product_id'] ] = $cart_item;
		}

		$this->assertSame( $expected_product_ids, array_keys( $cart_by_product ), 'All checkout-link product identifier formats should resolve.' );
		$this->assertSame( 1, $cart_by_product[ $legacy_product->get_id() ]['quantity'], 'A legacy product ID should default to quantity one.' );
		$this->assertSame( 2, $cart_by_product[ $quantity_product->get_id() ]['quantity'], 'A legacy product ID and quantity should remain supported.' );
		$this->assertSame( 1, $cart_by_product[ $sku_product->get_id() ]['quantity'], 'An escaped SKU should resolve to its product.' );
		$this->assertSame( 1, $cart_by_product[ $numeric_sku_product->get_id() ]['quantity'], 'An explicitly prefixed numeric SKU should resolve to its product.' );
		$this->assertSame(
			[
				'attribute_pa_colour' => '',
				'attribute_pa_number' => '',
				'attribute_pa_size'   => 'small',
			],
			$cart_by_product[ $variable_product->get_id() ]['variation'],
			'Variation data should select the requested variation.'
		);
		$this->assertSame( '99', $cart_by_product[ $variable_product->get_id() ]['nyp'], 'Additional product data should be retained on the cart item.' );
		$this->assertSame( 'alpha,beta;gamma:delta', $cart_by_product[ $variable_product->get_id() ]['note'], 'Escaped delimiters should be retained in additional product data.' );
		$this->assertArrayNotHasKey( 'nyp', $cart_by_product[ $variable_product->get_id() ]['variation'], 'Additional product data should not be treated as variation data.' );
		$this->assertSame( [ 'test-coupon' ], WC()->cart->get_applied_coupons(), 'The checkout-link coupon should be applied.' );
		$this->assertStringContainsString( 'session=', $url, 'Guest checkout links should include a cart session token.' );
	}

	/**
	 * @testdox Preserves escaped delimiters in variation and cart item data.
	 */
	public function test_escaped_product_data_delimiters_are_preserved(): void {
		$_GET['products'] = '123:1:ratio=10~:20~,wide~;special:note=a~,b~;c:d';

		$sut = new class() extends CheckoutLink {
			/**
			 * Get parsed checkout-link products for testing.
			 *
			 * @return array[] Parsed product data.
			 */
			public function get_products_test() {
				return parent::get_products_from_checkout_link();
			}
		};

		$this->assertSame(
			[
				[
					'id'             => 123,
					'quantity'       => 1,
					'variation'      => [
						[
							'attribute' => 'ratio',
							'value'     => '10:20,wide;special',
						],
					],
					'cart_item_data' => [ 'note' => 'a,b;c:d' ],
				],
			],
			$sut->get_products_test(),
			'Escaped delimiters should be treated as data rather than checkout-link separators.'
		);
	}

	/**
	 * @testdox Does not treat an unresolved numeric product ID as a numeric SKU.
	 */
	public function test_unresolved_numeric_product_id_does_not_resolve_as_sku(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_sku( '999999' );
		$product->save();

		$_GET['products'] = '999999';

		$sut = $this->get_checkout_link_service();

		$sut->get_checkout_link_test();

		$this->assertTrue( WC()->cart->is_empty(), 'An unresolved numeric ID must not add a product with the same numeric SKU.' );
	}

	/**
	 * @testdox Redirects to the product page with known attributes preselected when a variation has an "Any" attribute.
	 */
	public function test_variation_with_any_attribute_redirects_to_product_page(): void {
		$variable_product = \WC_Helper_Product::create_variation_product();
		$any_variation    = wc_get_product( wc_get_product_id_by_sku( 'DUMMY SKU VARIABLE SMALL' ) );

		$_GET['products'] = $any_variation->get_id() . ':2:pa_colour=red';

		$url = $this->get_checkout_link_service()->get_checkout_link_test();

		$this->assertTrue( WC()->cart->is_empty(), 'A variation with a missing "Any" attribute should not be added to the cart.' );
		$this->assertStringStartsWith( $variable_product->get_permalink(), $url, 'The shopper should be sent to the product page.' );

		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		$this->assertSame( 'small', $query['attribute_pa_size'] ?? null, 'Attributes set by the variation should be preselected.' );
		$this->assertSame( 'red', $query['attribute_pa_colour'] ?? null, 'Attributes passed in the link should be preselected.' );
		$this->assertArrayNotHasKey( 'attribute_pa_number', $query, 'The missing attribute should be left for the shopper to choose.' );

		$notices = wc_get_notices( 'error' );
		$notice  = $query['wc_error'] ?? ( $notices[0]['notice'] ?? '' );

		$this->assertStringContainsString( 'Choose options for', $notice, 'The shopper should be told to choose the missing options.' );
	}

	/**
	 * @testdox Adds resolvable products and redirects to the first product that needs options.
	 */
	public function test_resolvable_products_are_added_when_another_needs_options(): void {
		$simple_product   = \WC_Helper_Product::create_simple_product();
		$variable_product = \WC_Helper_Product::create_variation_product();
		$any_variation    = wc_get_product( wc_get_product_id_by_sku( 'DUMMY SKU VARIABLE HUGE BLUE ANY NUMBER' ) );
		$second_variation = wc_get_product( wc_get_product_id_by_sku( 'DUMMY SKU VARIABLE LARGE' ) );

		$_GET['products'] = implode( ',', [ (string) $simple_product->get_id(), (string) $any_variation->get_id(), (string) $second_variation->get_id() ] );

		$url = $this->get_checkout_link_service()->get_checkout_link_test();

		$this->assertSame( [ $simple_product->get_id() ], array_values( wp_list_pluck( WC()->cart->get_cart(), 'product_id' ) ), 'Only the resolvable product should be added to the cart.' );
		$this->assertStringStartsWith( $variable_product->get_permalink(), $url, 'The shopper should be sent to the first product that needs options.' );
		$this->assertStringContainsString( 'attribute_pa_colour=blue', $url, 'Attributes of the first product that needs options should be preselected.' );
		$this->assertStringContainsString( 'session=', $url, 'Guests should keep the cart through the session token.' );

		$notices = wc_get_notices( 'error' );

		$this->assertCount( 1, $notices, 'One notice should list every product that needs options.' );
		$this->assertStringContainsString( 'to add them to your cart', $notices[0]['notice'], 'The notice should cover both products.' );
	}

	/**
	 * @testdox Keeps the cart page error when the $unpublished product that needs options is not published.
	 *
	 * @testWith ["parent", "draft"]
	 *           ["variation", "private"]
	 *
	 * @param string $unpublished Which product to unpublish: the parent or the variation.
	 * @param string $status      The unpublished status to set.
	 */
	public function test_unpublished_product_that_needs_options_redirects_to_cart( string $unpublished, string $status ): void {
		$variable_product = \WC_Helper_Product::create_variation_product();
		$any_variation    = wc_get_product( wc_get_product_id_by_sku( 'DUMMY SKU VARIABLE SMALL' ) );
		$product          = 'parent' === $unpublished ? $variable_product : $any_variation;

		$product->set_status( $status );
		$product->save();

		$_GET['products'] = (string) $any_variation->get_id();

		$url = $this->get_checkout_link_service()->get_checkout_link_test();

		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		$this->assertStringNotContainsString( 'attribute_pa_size', $url, 'An unpublished product page should not be used as the redirect.' );
		$this->assertStringContainsString( 'Missing variation data', $query['wc_error'] ?? '', 'The add to cart error should still be shown.' );
	}

	/**
	 * Get a checkout link service that exposes the checkout link for testing.
	 *
	 * @return CheckoutLink
	 */
	private function get_checkout_link_service() {
		return new class() extends CheckoutLink {
			/**
			 * Get the checkout link for testing.
			 *
			 * @return string The checkout link.
			 */
			public function get_checkout_link_test() {
				return parent::get_checkout_link();
			}
		};
	}
}
