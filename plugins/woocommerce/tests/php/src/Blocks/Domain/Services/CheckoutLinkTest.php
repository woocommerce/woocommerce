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
	 * Test that products and coupon are added and token in url.
	 */
	public function test_products_and_coupon_are_added_and_token_in_url() {
		$test_products = [
			\WC_Helper_Product::create_simple_product(),
			\WC_Helper_Product::create_simple_product(),
			\WC_Helper_Product::create_simple_product(),
			\WC_Helper_Product::create_variation_product(),
		];

		$product_ids = [];
		$products    = [];

		foreach ( $test_products as $product ) {
			$product_ids[] = $product->get_id();
			if ( $product->is_type( 'variable' ) ) {
				$variations = $product->get_available_variations();
				$variation  = array_shift( $variations );

				$products[] = $product->get_id() . ':1:' . http_build_query( $variation['attributes'], '', ';' );
			} else {
				$products[] = $product->get_id();
			}
		}

		$coupon = CouponHelper::create_coupon( 'test-coupon' );

		$_GET['products'] = implode( ',', $products );
		$_GET['coupon']   = 'test-coupon';

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

		$url = $service->get_checkout_link_test();

		$cart_contents    = WC()->cart->get_cart();
		$cart_product_ids = array_map(
			function ( $item ) {
				return $item['product_id'];
			},
			$cart_contents
		);

		$applied_coupons      = WC()->cart->get_coupons();
		$applied_coupon_codes = array_map(
			function ( $coupon ) {
				return $coupon->get_code();
			},
			$applied_coupons
		);

		$this->assertEquals( array_values( $product_ids ), array_values( $cart_product_ids ) );

		// Check that the variable product in cart has the expected variations.
		foreach ( $cart_contents as $cart_item ) {
			if ( isset( $cart_item['variation'] ) && ! empty( $cart_item['variation'] ) ) {
				// The first variation should have pa_size=small, pa_colour and pa_number should be empty.
				$expected_variation = [
					'attribute_pa_size'   => 'small',
					'attribute_pa_colour' => '',
					'attribute_pa_number' => '',
				];
				$this->assertEquals( $expected_variation, $cart_item['variation'] );
			}
		}
		
		$this->assertEquals( array_values( [ 'test-coupon' ] ), array_values( $applied_coupon_codes ) );
		$this->assertStringContainsString( 'session=', $url );

		// Clean up.
		foreach ( $test_products as $product ) {
			wp_delete_post( $product->get_id(), true );
		}
	}
}
