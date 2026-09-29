<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Internal\Tax\CartPricingContext;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Tests for the CartPricingContext class.
 *
 * Assertions that the context is inactive run inside a non-cart dispatch: outside any dispatch,
 * is_checkout() is unreliable in CI because legacy tests define the WOOCOMMERCE_CHECKOUT constant
 * for the rest of the process.
 */
class CartPricingContextTest extends \WC_Unit_Test_Case {

	use StoreApiRouteTestTrait;

	/**
	 * The System Under Test.
	 *
	 * @var CartPricingContext
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( CartPricingContext::class );
		$this->sut->init();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			$this->clear_rest_server();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Is active for Store API cart and checkout routes.
	 *
	 * @dataProvider provider_store_api_cart_and_checkout_routes
	 *
	 * @param string $route Store API route.
	 */
	public function test_is_active_for_store_api_cart_and_checkout_routes( string $route ): void {
		$is_active = null;

		$this->dispatch_test_route(
			$route,
			function ( $request ) use ( &$is_active ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				$is_active = $this->sut->is_active();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertTrue( $is_active, "The {$route} route should price the cart." );
	}

	/**
	 * Provides Store API cart and checkout routes that can calculate cart totals.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_store_api_cart_and_checkout_routes(): array {
		return array(
			'cart namespace'       => array( '/wc/store/cart' ),
			'versioned cart'       => array( '/wc/store/v1/cart' ),
			'add cart item'        => array( '/wc/store/v1/cart/add-item' ),
			'cart items'           => array( '/wc/store/v1/cart/items' ),
			'cart item by key'     => array( '/wc/store/v1/cart/items/cart-item-key' ),
			'apply coupon'         => array( '/wc/store/v1/cart/apply-coupon' ),
			'cart coupons'         => array( '/wc/store/v1/cart/coupons' ),
			'remove coupon'        => array( '/wc/store/v1/cart/coupons/coupon-code' ),
			'remove cart coupon'   => array( '/wc/store/v1/cart/remove-coupon' ),
			'remove cart item'     => array( '/wc/store/v1/cart/remove-item' ),
			'select shipping rate' => array( '/wc/store/v1/cart/select-shipping-rate' ),
			'update cart item'     => array( '/wc/store/v1/cart/update-item' ),
			'update customer'      => array( '/wc/store/v1/cart/update-customer' ),
			'cart extension'       => array( '/wc/store/v1/cart/extensions' ),
			'checkout namespace'   => array( '/wc/store/checkout' ),
			'versioned checkout'   => array( '/wc/store/v1/checkout' ),
			'checkout draft order' => array( '/wc/store/v1/checkout/123' ),
		);
	}

	/**
	 * @testdox Is not active for other REST routes.
	 *
	 * @testWith ["/wc/store/v1/products"]
	 *           ["/wc/store/v1/cartography"]
	 *           ["/wc/v3/cart"]
	 *
	 * @param string $route REST route.
	 */
	public function test_is_not_active_for_other_routes( string $route ): void {
		$is_active = null;

		$this->dispatch_test_route(
			$route,
			function ( $request ) use ( &$is_active ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				$is_active = $this->sut->is_active();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertFalse( $is_active, "The {$route} route should not price the cart." );
	}

	/**
	 * @testdox Is active only while cart totals are calculated inside a non-cart route.
	 */
	public function test_is_active_during_cart_totals_inside_non_cart_route(): void {
		$this->add_product_to_cart();
		$during = null;
		$after  = null;
		$probe  = function () use ( &$during ): void {
			$during = $this->sut->is_active();
		};
		add_action( 'woocommerce_before_calculate_totals', $probe );

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$after ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				WC()->cart->calculate_totals();
				$after = $this->sut->is_active();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertTrue( $during, 'Cart totals should price the cart.' );
		$this->assertFalse( $after, 'The route should decide again once totals are calculated.' );
	}

	/**
	 * @testdox Is active only while the mini-cart renders inside a non-cart route.
	 */
	public function test_is_active_during_mini_cart_inside_non_cart_route(): void {
		$this->add_product_to_cart();
		$during = null;
		$after  = null;
		$probe  = function () use ( &$during ): void {
			$during = $this->sut->is_active();
		};
		add_action( 'woocommerce_before_mini_cart_contents', $probe );

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$after ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				ob_start();
				woocommerce_mini_cart();
				ob_end_clean();
				$after = $this->sut->is_active();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertTrue( $during, 'A mini-cart render should price the cart.' );
		$this->assertFalse( $after, 'The route should decide again once the mini-cart is rendered.' );
	}

	/**
	 * @testdox Is active only while a theme mini-cart override without the mini-cart hooks renders.
	 */
	public function test_is_active_only_during_mini_cart_override_without_mini_cart_hooks(): void {
		$this->add_product_to_cart();
		$during        = null;
		$after         = null;
		$mini_cart     = '';
		$override_file = wp_tempnam( 'mini-cart-without-hooks.php' );
		file_put_contents( $override_file, '<p>Theme mini-cart</p>' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture in a temp file.
		$override = function ( $template, $template_name ) use ( $override_file ) {
			return 'cart/mini-cart.php' === $template_name ? $override_file : $template;
		};
		$probe    = function ( $template_name ) use ( &$during ): void {
			if ( 'cart/mini-cart.php' === $template_name ) {
				$during = $this->sut->is_active();
			}
		};
		add_filter( 'wc_get_template', $override, 10, 2 );
		add_action( 'woocommerce_before_template_part', $probe );

		try {
			$this->dispatch_test_route(
				'/wc/store/v1/products',
				function ( $request ) use ( &$after, &$mini_cart ) {
					unset( $request ); // Avoid parameter not used PHPCS errors.
					ob_start();
					woocommerce_mini_cart();
					$mini_cart = (string) ob_get_clean();
					$after     = $this->sut->is_active();
					return rest_ensure_response( array( 'ok' => true ) );
				}
			);
		} finally {
			wp_delete_file( $override_file );
		}

		$this->assertStringContainsString( 'Theme mini-cart', $mini_cart, 'The theme override should be rendered.' );
		$this->assertTrue( $during, 'A mini-cart override render should price the cart.' );
		$this->assertFalse( $after, 'The route should decide again once the mini-cart override is rendered.' );
	}

	/**
	 * @testdox Ignores template parts other than the mini-cart.
	 */
	public function test_ignores_other_template_parts(): void {
		$is_active = null;

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$is_active ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				$this->sut->handle_template_part_start( 'cart/cart.php' );
				$is_active = $this->sut->is_active();
				$this->sut->handle_template_part_end( 'cart/cart.php' );
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertFalse( $is_active, 'Other template parts should not price the cart.' );
	}

	/**
	 * @testdox Recovers from an unbalanced calculation end.
	 */
	public function test_recovers_from_unbalanced_calculation_end(): void {
		$is_active = null;

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$is_active ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				$this->sut->handle_calculation_end();
				$this->sut->handle_calculation_start();
				$is_active = $this->sut->is_active();
				$this->sut->handle_calculation_end();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertTrue( $is_active, 'A stray calculation end should not cancel the next calculation start.' );
	}

	/**
	 * @testdox Is active for a cart route loaded through Blocks hydration, and only while it runs.
	 *
	 * Hydration calls the cart route callback directly, so the context is registered from the
	 * hydration filters instead of rest_request_before_callbacks.
	 */
	public function test_is_active_for_cart_route_loaded_through_hydration(): void {
		$during = null;
		$after  = null;
		$probe  = function ( $response ) use ( &$during ) {
			$during = $this->sut->is_active();
			return $response;
		};
		// Runs before the context is cleared at the default priority.
		add_filter( 'woocommerce_hydration_request_after_callbacks', $probe, 9 );

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$after ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				StoreApi::container();
				$hydration = new Hydration( $this->createMock( AssetDataRegistry::class ) );
				$hydration->get_rest_api_response_data( '/wc/store/v1/cart' );
				$after = $this->sut->is_active();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertTrue( $during, 'A hydrated cart route should price the cart.' );
		$this->assertFalse( $after, 'The outer route should decide again once hydration is done.' );
		$this->assert_dispatch_stack_empty( 'A completed hydration should not leave a context on the stack.' );
	}

	/**
	 * @testdox Restores the cart route context after a nested non-cart request.
	 */
	public function test_restores_cart_route_context_after_nested_non_cart_request(): void {
		$inner = null;
		$outer = null;

		$this->create_rest_server_with_routes(
			array(
				function () use ( &$inner, &$outer ): void {
					$this->register_test_route(
						'/wc/v3/test-inner',
						function ( $request ) use ( &$inner ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							$inner = $this->sut->is_active();
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
					$this->register_test_route(
						'/wc/store/v1/cart/test-outer',
						function ( $request ) use ( &$outer ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							rest_do_request( new \WP_REST_Request( 'GET', '/wc/v3/test-inner' ) );
							$outer = $this->sut->is_active();
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
				},
			)
		);

		rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/test-outer' ) );

		$this->assertFalse( $inner, 'A nested non-cart request should not price the cart.' );
		$this->assertTrue( $outer, 'The cart route context should be restored after a nested request.' );
	}

	/**
	 * @testdox Restores the non-cart route context after a nested cart request.
	 */
	public function test_restores_non_cart_route_context_after_nested_cart_request(): void {
		$inner = null;
		$outer = null;

		$this->create_rest_server_with_routes(
			array(
				function () use ( &$inner, &$outer ): void {
					$this->register_test_route(
						'/wc/store/v1/cart/test-inner',
						function ( $request ) use ( &$inner ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							$inner = $this->sut->is_active();
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
					$this->register_test_route(
						'/wc/v3/test-outer',
						function ( $request ) use ( &$outer ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/test-inner' ) );
							$outer = $this->sut->is_active();
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
				},
			)
		);

		rest_do_request( new \WP_REST_Request( 'GET', '/wc/v3/test-outer' ) );

		$this->assertTrue( $inner, 'A nested cart request should price the cart.' );
		$this->assertFalse( $outer, 'The outer non-cart context should be restored after a nested cart request.' );
	}

	/**
	 * @testdox Is not active for a products request dispatched while the mini-cart renders.
	 */
	public function test_is_not_active_for_products_request_dispatched_from_mini_cart(): void {
		$this->add_product_to_cart();
		$inner = null;
		$outer = null;

		$this->create_rest_server_with_routes(
			array(
				function () use ( &$inner ): void {
					$this->register_test_route(
						'/wc/store/v1/products',
						function ( $request ) use ( &$inner ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							$inner = $this->sut->is_active();
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
				},
			)
		);
		add_action(
			'woocommerce_before_mini_cart_contents',
			function () use ( &$outer ): void {
				rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );
				$outer = $this->sut->is_active();
			}
		);

		ob_start();
		woocommerce_mini_cart();
		ob_end_clean();

		$this->assertFalse( $inner, 'A products request inside the mini-cart should not price the cart.' );
		$this->assertTrue( $outer, 'The mini-cart should still price the cart after the nested request.' );
	}

	/**
	 * @testdox Does not leave a context on the stack when rest_pre_dispatch short-circuits a cart route.
	 *
	 * A rest_pre_dispatch filter can hijack a request, so dispatch() returns before
	 * respond_to_request() and neither rest_request_before_callbacks nor
	 * rest_request_after_callbacks fires.
	 */
	public function test_does_not_register_context_when_pre_dispatch_short_circuits_cart_route(): void {
		$this->create_rest_server_with_routes(
			array(
				function (): void {
					$this->register_test_route(
						'/wc/store/v1/cart/test-hijack',
						function ( $request ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
				},
			)
		);
		add_filter(
			'rest_pre_dispatch',
			function ( $result, $server, $request ) {
				unset( $server ); // Avoid parameter not used PHPCS errors.
				if ( '/wc/store/v1/cart/test-hijack' === $request->get_route() ) {
					return rest_ensure_response( array( 'hijacked' => true ) );
				}
				return $result;
			},
			10,
			3
		);

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/test-hijack' ) );

		$this->assertSame( array( 'hijacked' => true ), $response->get_data(), 'The hijack filter should short-circuit the cart route.' );
		$this->assert_dispatch_stack_empty( 'A short-circuited cart request should not leave a context on the stack.' );
	}

	/**
	 * @testdox Does not leave a context on the stack for an unmatched Store API cart route.
	 */
	public function test_does_not_register_context_for_unmatched_store_api_cart_route(): void {
		$this->create_rest_server_with_routes( array() );

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/this-route-does-not-exist' ) );

		$this->assertSame( 404, $response->get_status(), 'An unmatched Store API cart route should return 404.' );
		$this->assert_dispatch_stack_empty( 'An unmatched cart request should not leave a context on the stack.' );
	}

	/**
	 * Add a simple product to the cart, so cart totals and mini-cart hooks fire.
	 */
	private function add_product_to_cart(): void {
		WC()->cart->add_to_cart( \WC_Helper_Product::create_simple_product()->get_id() );
	}

	/**
	 * Assert the dispatch stack and context map are both empty.
	 *
	 * @param string $message Assertion failure message.
	 */
	private function assert_dispatch_stack_empty( string $message ): void {
		$reflection     = new \ReflectionClass( $this->sut );
		$stack_property = $reflection->getProperty( 'dispatch_stack' );
		$stack_property->setAccessible( true );
		$contexts_property = $reflection->getProperty( 'dispatch_contexts' );
		$contexts_property->setAccessible( true );

		$this->assertSame( array(), $stack_property->getValue( $this->sut ), "Dispatch stack should be empty. {$message}" );
		$this->assertSame( array(), $contexts_property->getValue( $this->sut ), "Dispatch contexts should be empty. {$message}" );
	}
}
