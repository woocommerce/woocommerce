<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;

/**
 * Tracks whether code is running to price the cart, as opposed to displaying catalog prices.
 *
 * Cart pricing covers cart totals calculations, mini-cart renders, the cart and checkout pages,
 * and Store API cart and checkout routes, including those loaded through Blocks hydration.
 *
 * @internal
 * @since 11.3.0
 */
class CartPricingContext {

	/**
	 * Template name of the mini-cart.
	 */
	private const MINI_CART_TEMPLATE = 'cart/mini-cart.php';

	/**
	 * Template name of the classic cart cross-sells.
	 */
	private const CROSS_SELLS_TEMPLATE = 'cart/cross-sells.php';

	/**
	 * Stack of Store API and hydrated route dispatches, with whether each is a cart or checkout route and the calculation depth when it started.
	 *
	 * @var array<int, array{request: object, is_cart_or_checkout: bool, calculation_depth: int}>
	 */
	private $dispatch_stack = array();

	/**
	 * Nesting depth of cart totals calculations and mini-cart renders, which price the cart on any page.
	 *
	 * @var int
	 */
	private $calculation_depth = 0;

	/**
	 * Calculation and dispatch depths at which catalog pricing scopes began.
	 *
	 * @var array<int, array{calculation_depth: int, dispatch_depth: int}>
	 */
	private $catalog_pricing_scopes = array();

	/**
	 * Register hooks.
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 */
	final public function init(): void {
		// Register the route context only after a route matches, and clear it right after the callback.
		// Blocks hydration calls route callbacks directly, so it starts and ends its dispatches itself.
		add_filter( 'rest_request_before_callbacks', array( $this, 'handle_rest_request_before_callbacks' ), 10, 3 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'handle_rest_request_after_callbacks' ), 10, 3 );
		// Cart totals are saved in the session and mini-cart line prices are calculated on render,
		// so both can happen outside the cart and checkout pages.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'handle_woocommerce_before_calculate_totals' ), PHP_INT_MIN, 0 );
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'handle_woocommerce_after_calculate_totals' ), PHP_INT_MAX, 0 );
		// The template part hooks fire around the mini-cart and cross-sells even when a theme override drops their own hooks.
		add_action( 'woocommerce_before_template_part', array( $this, 'handle_woocommerce_before_template_part' ), PHP_INT_MIN, 1 );
		add_action( 'woocommerce_after_template_part', array( $this, 'handle_woocommerce_after_template_part' ), PHP_INT_MAX, 1 );
	}

	/**
	 * Determine whether the cart is being priced.
	 *
	 * Inside a Store API dispatch the route decides, unless a cart calculation started within that dispatch.
	 * Outside any dispatch, a running cart calculation or the cart and checkout pages count.
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 *
	 * @return bool True when the cart is being priced.
	 */
	public function is_active(): bool {
		$scope = empty( $this->catalog_pricing_scopes ) ? null : end( $this->catalog_pricing_scopes );
		if ( null !== $scope && count( $this->dispatch_stack ) <= $scope['dispatch_depth'] && $this->calculation_depth <= $scope['calculation_depth'] ) {
			return false;
		}

		$dispatch = empty( $this->dispatch_stack ) ? null : end( $this->dispatch_stack );

		if ( null !== $dispatch ) {
			return $this->calculation_depth > $dispatch['calculation_depth'] || $dispatch['is_cart_or_checkout'];
		}

		return $this->calculation_depth > 0 || is_cart() || is_checkout();
	}

	/**
	 * Price catalog products without applying the billing fallback for a cart without shipping.
	 *
	 * Cart calculations started inside the callback still use the cart pricing context.
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 *
	 * @param callable $callback Renders or prepares catalog products.
	 * @return mixed Callback result.
	 */
	public function with_catalog_pricing( callable $callback ) {
		$this->begin_catalog_pricing();
		try {
			return $callback();
		} finally {
			$this->end_catalog_pricing();
		}
	}

	/**
	 * Start a cart totals calculation.
	 *
	 * @internal
	 */
	public function handle_woocommerce_before_calculate_totals(): void {
		$this->begin_calculation();
	}

	/**
	 * End a cart totals calculation.
	 *
	 * @internal
	 */
	public function handle_woocommerce_after_calculate_totals(): void {
		$this->end_calculation();
	}

	/**
	 * Start pricing the cart for a mini-cart render, or catalog pricing for a cross-sells render.
	 *
	 * @internal
	 *
	 * @param mixed $template_name Template name.
	 */
	public function handle_woocommerce_before_template_part( $template_name ): void {
		if ( self::MINI_CART_TEMPLATE === $template_name ) {
			$this->begin_calculation();
		} elseif ( self::CROSS_SELLS_TEMPLATE === $template_name ) {
			$this->begin_catalog_pricing();
		}
	}

	/**
	 * End a mini-cart or cross-sells render.
	 *
	 * @internal
	 *
	 * @param mixed $template_name Template name.
	 */
	public function handle_woocommerce_after_template_part( $template_name ): void {
		if ( self::MINI_CART_TEMPLATE === $template_name ) {
			$this->end_calculation();
		} elseif ( self::CROSS_SELLS_TEMPLATE === $template_name ) {
			$this->end_catalog_pricing();
		}
	}

	/**
	 * Start the Store API route dispatch after a route matches, before its callback runs.
	 *
	 * @internal
	 *
	 * @param mixed $response Result to send to the client.
	 * @param mixed $handler  Route handler for the request.
	 * @param mixed $request  Request used to generate the response.
	 * @return mixed The response.
	 */
	public function handle_rest_request_before_callbacks( $response, $handler, $request ) {
		if ( $request instanceof \WP_REST_Request ) {
			$this->start_dispatch( $request, $handler );
		}

		return $response;
	}

	/**
	 * End the Store API route dispatch after the route callback runs.
	 *
	 * Paired with rest_request_before_callbacks inside WP_REST_Server::respond_to_request(),
	 * so the stack stays balanced for both external and internal (rest_do_request) dispatches.
	 *
	 * @internal
	 *
	 * @param mixed $response Result to send to the client.
	 * @param mixed $handler  Route handler for the request.
	 * @param mixed $request  Request used to generate the response.
	 * @return mixed The response.
	 */
	public function handle_rest_request_after_callbacks( $response, $handler, $request ) {
		if ( $request instanceof \WP_REST_Request ) {
			$this->end_dispatch( $request );
		}

		return $response;
	}

	/**
	 * Start a dispatch, which prices the cart when its handler is a Store API cart or checkout route.
	 *
	 * Every call must be paired with end_dispatch() for the same request.
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 *
	 * @param \WP_REST_Request $request Request being dispatched.
	 * @param mixed            $handler Route handler for the request.
	 * @phpstan-template T of array
	 * @phpstan-param \WP_REST_Request<T> $request
	 */
	public function start_dispatch( \WP_REST_Request $request, $handler ): void {
		$this->dispatch_stack[] = array(
			'request'             => $request,
			'is_cart_or_checkout' => self::is_cart_route_handler( $handler ),
			'calculation_depth'   => $this->calculation_depth,
		);
	}

	/**
	 * End a dispatch, dropping it and anything left unfinished inside it.
	 *
	 * Inner dispatches, catalog pricing scopes, and the calculation depth go back to their state from when
	 * the dispatch started, so an exception caught inside it cannot change pricing for the rest of the request.
	 * Calling it again for a dispatch that already ended does nothing.
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 *
	 * @param \WP_REST_Request $request Request whose dispatch should end.
	 * @phpstan-template T of array
	 * @phpstan-param \WP_REST_Request<T> $request
	 */
	public function end_dispatch( \WP_REST_Request $request ): void {
		for ( $index = count( $this->dispatch_stack ) - 1; $index >= 0; $index-- ) {
			if ( $request === $this->dispatch_stack[ $index ]['request'] ) {
				$this->calculation_depth      = $this->dispatch_stack[ $index ]['calculation_depth'];
				$this->catalog_pricing_scopes = array_values(
					array_filter(
						$this->catalog_pricing_scopes,
						static fn( array $scope ): bool => $scope['dispatch_depth'] <= $index
					)
				);
				array_splice( $this->dispatch_stack, $index );
				return;
			}
		}
	}

	/**
	 * Start a cart totals calculation or mini-cart render.
	 */
	private function begin_calculation(): void {
		++$this->calculation_depth;
	}

	/**
	 * End a calculation started by begin_calculation().
	 */
	private function end_calculation(): void {
		$this->calculation_depth = max( 0, $this->calculation_depth - 1 );
	}

	/**
	 * Start a catalog pricing scope at the current calculation and dispatch depths.
	 */
	private function begin_catalog_pricing(): void {
		$this->catalog_pricing_scopes[] = array(
			'calculation_depth' => $this->calculation_depth,
			'dispatch_depth'    => count( $this->dispatch_stack ),
		);
	}

	/**
	 * End the innermost catalog pricing scope.
	 */
	private function end_catalog_pricing(): void {
		array_pop( $this->catalog_pricing_scopes );
	}

	/**
	 * Determine whether a route handler belongs to a Store API cart or checkout route.
	 *
	 * @param mixed $handler Route handler, with the route object as the first element of its callback.
	 * @return bool True when the handler is a Store API cart or checkout route.
	 */
	private static function is_cart_route_handler( $handler ): bool {
		$callback = is_array( $handler ) ? ( $handler['callback'] ?? null ) : null;

		return is_array( $callback ) && ( $callback[0] ?? null ) instanceof AbstractCartRoute;
	}
}
