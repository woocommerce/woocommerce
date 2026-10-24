<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Internal\Tax\CartPricingContext;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Tests for the CartPricingContext class.
 *
 * These tests cover which code counts as cart pricing and how nested or failed work is unwound. The tax results of
 * cart totals, mini-cart renders, hydrated cart requests and cross-sells are tested end to end in CartWithoutShippingTest.
 *
 * Assertions that the context is inactive run inside a non-cart dispatch: outside any dispatch,
 * is_checkout() is unreliable in CI because legacy tests define the WOOCOMMERCE_CHECKOUT constant
 * for the rest of the process.
 */
class CartPricingContextTest extends \WC_Unit_Test_Case {

	use StoreApiRouteTestTrait;

	/**
	 * Store API cart route that the nesting tests dispatch from inside other work.
	 */
	private const CART_INNER_ROUTE = '/wc/store/v1/cart/test-inner';

	/**
	 * Non-cart REST route that the nesting tests dispatch from inside other work.
	 */
	private const OTHER_INNER_ROUTE = '/wc/v3/test-inner';

	/**
	 * Store API cart route that runs the outer work of the nesting tests.
	 */
	private const CART_OUTER_ROUTE = '/wc/store/v1/cart/test-outer';

	/**
	 * Non-cart REST route that runs the outer work of the nesting tests.
	 */
	private const OTHER_OUTER_ROUTE = '/wc/v3/test-outer';

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

		// A fresh instance, so state left by earlier tests cannot leak in; Blocks hydration resolves it from the container.
		$this->sut = new CartPricingContext();
		$this->sut->init();
		wc_get_container()->replace( CartPricingContext::class, $this->sut );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			$this->reset_container_replacements();
			$this->clear_rest_server();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Is active for Store API cart and checkout routes.
	 */
	public function test_is_active_for_store_api_cart_and_checkout_routes(): void {
		$is_active = null;

		$this->dispatch_test_route(
			'/wc/store/v1/cart',
			$this->as_cart_route(
				function ( $request ) use ( &$is_active ) {
					unset( $request ); // Avoid parameter not used PHPCS errors.
					$is_active = $this->sut->is_active();
					return rest_ensure_response( array( 'ok' => true ) );
				}
			)
		);

		$this->assertTrue( $is_active, 'A Store API cart route should price the cart.' );
	}

	/**
	 * @testdox Every Store API cart and checkout route extends AbstractCartRoute, so the context recognizes it.
	 */
	public function test_every_store_api_cart_and_checkout_route_extends_abstract_cart_route(): void {
		$routes = StoreApi::container()->get( RoutesController::class )->get_all_routes( 'v1', true );

		foreach ( $routes as $path => $route_class ) {
			$is_cart_path = 1 === preg_match( '#^/wc/store/v1/(?:cart|checkout)(?:/|$)#', $path );
			$this->assertSame( $is_cart_path, is_subclass_of( $route_class, AbstractCartRoute::class ), "The {$path} route should extend AbstractCartRoute only when it is a cart or checkout route." );
		}
	}

	/**
	 * @testdox Is not active for other REST routes.
	 *
	 * @testWith ["/wc/store/v1/products"]
	 *           ["/wc/store/v1/cartography"]
	 *           ["/wc/v3/cart"]
	 *           ["/wc/store/v1/cart/not-a-cart-route"]
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
				$this->sut->handle_woocommerce_before_template_part( 'cart/cart.php' );
				$is_active = $this->sut->is_active();
				$this->sut->handle_woocommerce_after_template_part( 'cart/cart.php' );
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertFalse( $is_active, 'Other template parts should not price the cart.' );
	}


	/**
	 * @testdox Nested $inner inside $outer: prices the cart only for cart work, then the outer context resumes.
	 *
	 * Covers nested Store API requests in both directions, catalog pricing scopes (cross-sells) inside a cart route,
	 * and REST requests dispatched while the mini-cart renders.
	 *
	 * @dataProvider provider_nested_work
	 *
	 * @param string $outer          Work that runs the nested work: 'cart route', 'non-cart route', 'catalog pricing' inside a cart route, or 'mini-cart'.
	 * @param string $inner          Nested work: 'cart route', 'non-cart route', or 'cart totals'.
	 * @param bool   $expected_inner Whether the nested work prices the cart.
	 * @param bool   $expected_outer Whether the outer work prices the cart after the nested work returns.
	 */
	public function test_nested_work_uses_own_context_and_restores_outer_context( string $outer, string $inner, bool $expected_inner, bool $expected_outer ): void {
		$this->add_product_to_cart();
		$inner_active = null;
		$outer_active = null;
		$record_inner = function () use ( &$inner_active ): void {
			$inner_active = $this->sut->is_active();
		};
		$run_inner    = function () use ( $inner, $record_inner, &$outer_active ): void {
			if ( 'cart totals' === $inner ) {
				add_action( 'woocommerce_before_calculate_totals', $record_inner );
				WC()->cart->calculate_totals();
			} else {
				rest_do_request( new \WP_REST_Request( 'GET', 'cart route' === $inner ? self::CART_INNER_ROUTE : self::OTHER_INNER_ROUTE ) );
			}
			$outer_active = $this->sut->is_active();
		};
		$this->register_nesting_routes(
			function () use ( $outer, $run_inner ): void {
				if ( 'catalog pricing' === $outer ) {
					$this->sut->with_catalog_pricing( $run_inner );
				} else {
					$run_inner();
				}
			},
			$record_inner
		);

		if ( 'mini-cart' === $outer ) {
			add_action( 'woocommerce_before_mini_cart_contents', $run_inner );
			ob_start();
			woocommerce_mini_cart();
			ob_end_clean();
		} else {
			rest_do_request( new \WP_REST_Request( 'GET', 'non-cart route' === $outer ? self::OTHER_OUTER_ROUTE : self::CART_OUTER_ROUTE ) );
		}

		$this->assertSame( $expected_inner, $inner_active, 'The nested work should price the cart only when it is cart work.' );
		$this->assertSame( $expected_outer, $outer_active, 'The outer context should resume after the nested work.' );
	}

	/**
	 * Provides nested work and whether it, and the work around it, price the cart.
	 *
	 * @return array<string, array{string, string, bool, bool}>
	 */
	public function provider_nested_work(): array {
		return array(
			'non-cart route inside cart route'      => array( 'cart route', 'non-cart route', false, true ),
			'cart route inside non-cart route'      => array( 'non-cart route', 'cart route', true, false ),
			'cart route inside catalog pricing'     => array( 'catalog pricing', 'cart route', true, false ),
			'non-cart route inside catalog pricing' => array( 'catalog pricing', 'non-cart route', false, false ),
			'cart totals inside catalog pricing'    => array( 'catalog pricing', 'cart totals', true, false ),
			'non-cart route inside mini-cart'       => array( 'mini-cart', 'non-cart route', false, true ),
		);
	}

	/**
	 * @testdox Failed or unfinished work inside a request does not change pricing after it.
	 *
	 * Each case runs inside an outer route and checks is_active() right after the work, then checks that the
	 * dispatch stack is empty once the outer route ends. Covers exceptions in cart calculations, hydrated cart
	 * controllers and catalog pricing callbacks, unfinished mini-cart renders, unbalanced calculation hooks,
	 * and requests short-circuited by rest_pre_dispatch.
	 *
	 * @dataProvider provider_failed_or_unfinished_work
	 *
	 * @param bool     $in_cart_route  Whether the outer route is a Store API cart route.
	 * @param callable $work           Receives the test case and a function that runs a callback inside a nested non-cart request.
	 * @param bool     $expected_after Whether the cart is priced right after the work.
	 */
	public function test_failed_or_unfinished_work_does_not_change_pricing_after_it( bool $in_cart_route, callable $work, bool $expected_after ): void {
		$this->add_product_to_cart();
		$after       = null;
		$nested_work = null;
		$run_nested  = function ( callable $callback ) use ( &$nested_work ): void {
			$nested_work = $callback;
			rest_do_request( new \WP_REST_Request( 'GET', self::OTHER_INNER_ROUTE ) );
		};
		$this->register_nesting_routes(
			function () use ( $work, $run_nested, &$after ): void {
				$work( $this, $run_nested );
				$after = $this->sut->is_active();
			},
			function () use ( &$nested_work ): void {
				if ( null !== $nested_work ) {
					$nested_work();
				}
			}
		);

		rest_do_request( new \WP_REST_Request( 'GET', $in_cart_route ? self::CART_OUTER_ROUTE : self::OTHER_OUTER_ROUTE ) );

		$this->assertSame( $expected_after, $after, 'Pricing right after the work should match the outer route.' );
		$this->assert_dispatch_stack_empty( 'No dispatch should be left on the stack once the outer route ends.' );
	}

	/**
	 * Provides failed or unfinished work, and whether the cart is priced right after it.
	 *
	 * @return array<string, array{bool, callable, bool}>
	 */
	public function provider_failed_or_unfinished_work(): array {
		return array(
			'cart calculation that throws in a nested request' => array(
				false,
				static function ( self $test, callable $run_nested ): void {
					unset( $test ); // Avoid parameter not used PHPCS errors.
					$run_nested(
						static function (): void {
							add_action(
								'woocommerce_before_calculate_totals',
								static function ( $cart ): void {
									if ( $cart instanceof \WC_Cart ) {
										throw new \RuntimeException( 'Calculation failure' );
									}
								}
							);
							try {
								WC()->cart->calculate_totals();
							} catch ( \RuntimeException $e ) {
								unset( $e ); // The failure is the point of the test.
							}
						}
					);
				},
				false,
			),
			'mini-cart render left unfinished in a nested request' => array(
				false,
				static function ( self $test, callable $run_nested ): void {
					$run_nested(
						static function () use ( $test ): void {
							$test->sut->handle_woocommerce_before_template_part( 'cart/mini-cart.php' );
						}
					);
				},
				false,
			),
			'hydrated cart controller that throws' => array(
				false,
				static function ( self $test ): void {
					$threw = false;
					// The cart controller creates a nonce after its response catch blocks.
					add_filter(
						'nonce_life',
						static function ( $nonce_life ) use ( $test, &$threw ) {
							if ( $test->sut->is_active() ) {
								$threw = true;
								throw new \Exception( 'Cart response failure' );
							}
							return $nonce_life;
						}
					);
					StoreApi::container();
					$hydration = new Hydration( $test->createMock( AssetDataRegistry::class ) );
					$hydration->get_rest_api_response_data( '/wc/store/v1/cart' );
					$test->assertTrue( $threw, 'The hydrated cart controller should run the failing code.' );
				},
				false,
			),
			'cart route short-circuited by rest_pre_dispatch' => array(
				false,
				static function ( self $test ): void {
					// dispatch() returns before respond_to_request(), so neither rest_request_*_callbacks filter fires.
					add_filter(
						'rest_pre_dispatch',
						static function ( $result, $server, $request ) {
							unset( $server ); // Avoid parameter not used PHPCS errors.
							return self::CART_INNER_ROUTE === $request->get_route() ? rest_ensure_response( array( 'hijacked' => true ) ) : $result;
						},
						10,
						3
					);
					$response = rest_do_request( new \WP_REST_Request( 'GET', self::CART_INNER_ROUTE ) );
					$test->assertSame( array( 'hijacked' => true ), $response->get_data(), 'The filter should short-circuit the cart route.' );
				},
				false,
			),
			'catalog pricing callback that throws in a cart route' => array(
				true,
				static function ( self $test ): void {
					try {
						$test->sut->with_catalog_pricing(
							static function (): void {
								throw new \RuntimeException( 'Catalog rendering failed' );
							}
						);
					} catch ( \RuntimeException $e ) {
						unset( $e ); // The failure exercises the scope's finally block.
					}
				},
				true,
			),
			'calculation started after a stray calculation end' => array(
				false,
				static function ( self $test ): void {
					$test->sut->handle_woocommerce_after_calculate_totals();
					$test->sut->handle_woocommerce_before_calculate_totals();
				},
				true,
			),
		);
	}

	/**
	 * Register cart and non-cart routes that run the outer work, and cart and non-cart routes that run the inner work.
	 *
	 * @param callable $outer_work Work for CART_OUTER_ROUTE and OTHER_OUTER_ROUTE.
	 * @param callable $inner_work Work for CART_INNER_ROUTE and OTHER_INNER_ROUTE.
	 */
	private function register_nesting_routes( callable $outer_work, callable $inner_work ): void {
		$as_route_callback = static function ( callable $work ): callable {
			return static function ( $request ) use ( $work ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				$work();
				return rest_ensure_response( array( 'ok' => true ) );
			};
		};

		$this->create_rest_server_with_routes(
			array(
				function () use ( $as_route_callback, $outer_work, $inner_work ): void {
					$this->register_test_route( self::CART_OUTER_ROUTE, $this->as_cart_route( $as_route_callback( $outer_work ) ) );
					$this->register_test_route( self::OTHER_OUTER_ROUTE, $as_route_callback( $outer_work ) );
					$this->register_test_route( self::CART_INNER_ROUTE, $this->as_cart_route( $as_route_callback( $inner_work ) ) );
					$this->register_test_route( self::OTHER_INNER_ROUTE, $as_route_callback( $inner_work ) );
				},
			)
		);
	}

	/**
	 * Add a simple product to the cart, so cart totals and mini-cart hooks fire.
	 */
	private function add_product_to_cart(): void {
		WC()->cart->add_to_cart( \WC_Helper_Product::create_simple_product()->get_id() );
	}

	/**
	 * Assert the dispatch stack is empty.
	 *
	 * @param string $message Assertion failure message.
	 */
	private function assert_dispatch_stack_empty( string $message ): void {
		$stack_property = ( new \ReflectionClass( $this->sut ) )->getProperty( 'dispatch_stack' );
		$stack_property->setAccessible( true );

		$this->assertSame( array(), $stack_property->getValue( $this->sut ), $message );
	}
}
