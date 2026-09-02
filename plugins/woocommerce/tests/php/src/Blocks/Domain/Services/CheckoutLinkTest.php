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
		$legacy_product    = \WC_Helper_Product::create_simple_product();
		$quantity_product  = \WC_Helper_Product::create_simple_product();
		$sku_product       = \WC_Helper_Product::create_simple_product();
		$variable_product  = \WC_Helper_Product::create_variation_product();
		$available_options = $variable_product->get_available_variations();
		$chosen_variation  = array_shift( $available_options );

		$sku_product->set_sku( 'CHECKOUT-LINK-SKU' );
		$sku_product->save();

		$_GET['products'] = implode(
			',',
			[
				(string) $legacy_product->get_id(),
				$quantity_product->get_id() . ':2',
				'CHECKOUT-LINK-SKU',
				$variable_product->get_id() . ':1:' . http_build_query( $chosen_variation['attributes'], '', ';' ) . ':nyp=99',
			]
		);
		$_GET['coupon']   = 'test-coupon';
		CouponHelper::create_coupon( 'test-coupon' );

		$service = new class() extends CheckoutLink {
			/**
			 * Get the checkout link for testing.
			 *
			 * @return string The checkout link.
			 */
			public function get_checkout_link_test() {
				return parent::get_checkout_link();
			}
		};

		$url                  = $service->get_checkout_link_test();
		$cart_by_product      = [];
		$expected_product_ids = [
			$legacy_product->get_id(),
			$quantity_product->get_id(),
			$sku_product->get_id(),
			$variable_product->get_id(),
		];

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$cart_by_product[ $cart_item['product_id'] ] = $cart_item;
		}

		$this->assertSame( $expected_product_ids, array_keys( $cart_by_product ), 'All checkout-link product identifier formats should resolve.' );
		$this->assertSame( 1, $cart_by_product[ $legacy_product->get_id() ]['quantity'], 'A legacy product ID should default to quantity one.' );
		$this->assertSame( 2, $cart_by_product[ $quantity_product->get_id() ]['quantity'], 'A legacy product ID and quantity should remain supported.' );
		$this->assertSame( 1, $cart_by_product[ $sku_product->get_id() ]['quantity'], 'A SKU should resolve to its product.' );
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
		$this->assertArrayNotHasKey( 'nyp', $cart_by_product[ $variable_product->get_id() ]['variation'], 'Additional product data should not be treated as variation data.' );
		$this->assertSame( [ 'test-coupon' ], WC()->cart->get_applied_coupons(), 'The checkout-link coupon should be applied.' );
		$this->assertStringContainsString( 'session=', $url, 'Guest checkout links should include a cart session token.' );
	}

	/**
	 * @testdox Does not treat an unresolved numeric product ID as a numeric SKU.
	 */
	public function test_unresolved_numeric_product_id_does_not_resolve_as_sku(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_sku( '999999' );
		$product->save();

		$_GET['products'] = '999999';

		$service = new class() extends CheckoutLink {
			/**
			 * Get the checkout link for testing.
			 *
			 * @return string The checkout link.
			 */
			public function get_checkout_link_test() {
				return parent::get_checkout_link();
			}
		};

		$service->get_checkout_link_test();

		$this->assertTrue( WC()->cart->is_empty(), 'An unresolved numeric ID must not add a product with the same numeric SKU.' );
	}
}
