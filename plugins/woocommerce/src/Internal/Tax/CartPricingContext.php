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
 * @since 11.3.0
 */
class CartPricingContext {

	/**
	 * Template name of the mini-cart.
	 */
	private const MINI_CART_TEMPLATE = 'cart/mini-cart.php';

	/**
	 * Stack of dispatch IDs for Store API requests and hydrated routes.
	 *
	 * @var int[]
	 */
	private $dispatch_stack = array();

	/**
	 * Map of dispatch IDs to whether the route is a cart or checkout route, and the calculation and mini-cart depths when the dispatch started.
	 *
	 * @var array<int, array{is_cart_or_checkout: bool, calculation_depth: int, mini_cart_count: int}>
	 */
	private $dispatch_contexts = array();

	/**
	 * Nesting depth of cart totals calculations and mini-cart renders, which price the cart on any page.
	 *
	 * @var int
	 */
	private $calculation_depth = 0;

	/**
	 * Calculation depths saved when mini-cart renders started, restored when each render ends.
	 *
	 * @var int[]
	 */
	private $mini_cart_depths = array();

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
	 * @internal
	 */
	final public function init(): void {
		$this->dispatch_stack         = array();
		$this->dispatch_contexts      = array();
		$this->calculation_depth      = 0;
		$this->mini_cart_depths       = array();
		$this->catalog_pricing_scopes = array();
		// Register the route context only after a route matches, and clear it right after the callback.
		add_filter( 'rest_request_before_callbacks', array( $this, 'handle_rest_request_before_callbacks' ), 10, 3 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'handle_rest_request_after_callbacks' ), 10, 3 );
		// Blocks hydration calls cart and checkout route callbacks directly, without the REST dispatch
		// lifecycle, so the context is registered from its own before/after filters instead.
		add_filter( 'woocommerce_hydration_dispatch_request', array( $this, 'handle_hydration_dispatch_request' ), 10, 4 );
		add_filter( 'woocommerce_hydration_request_after_callbacks', array( $this, 'handle_hydration_request_after_callbacks' ), 10, 3 );
		// Cart totals are saved in the session and mini-cart line prices are calculated on render,
		// so both can happen outside the cart and checkout pages.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'handle_calculation_start' ), PHP_INT_MIN, 0 );
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'handle_calculation_end' ), PHP_INT_MAX, 0 );
		// The template part hooks fire around the mini-cart even when a theme override drops the mini-cart hooks.
		add_action( 'woocommerce_before_template_part', array( $this, 'handle_template_part_start' ), PHP_INT_MIN, 1 );
		add_action( 'woocommerce_after_template_part', array( $this, 'handle_template_part_end' ), PHP_INT_MAX, 1 );
	}

	/**
	 * Determine whether the cart is being priced.
	 *
	 * Inside a Store API dispatch the route decides, unless a cart calculation started within that dispatch.
	 * Outside any dispatch, a running cart calculation or the cart and checkout pages count.
	 *
	 * @since 11.3.0
	 *
	 * @return bool True when the cart is being priced.
	 */
	public function is_active(): bool {
		$scope = empty( $this->catalog_pricing_scopes ) ? null : end( $this->catalog_pricing_scopes );
		if ( null !== $scope && count( $this->dispatch_stack ) <= $scope['dispatch_depth'] && $this->calculation_depth <= $scope['calculation_depth'] ) {
			return false;
		}

		$dispatch_context = empty( $this->dispatch_stack ) ? null : ( $this->dispatch_contexts[ end( $this->dispatch_stack ) ] ?? null );

		if ( null !== $dispatch_context ) {
			return $this->calculation_depth > $dispatch_context['calculation_depth'] || $dispatch_context['is_cart_or_checkout'];
		}

		return $this->calculation_depth > 0 || is_cart() || is_checkout();
	}

	/**
	 * Price catalog products without applying the billing fallback for a cart without shipping.
	 *
	 * Cart calculations started inside the callback still use the cart pricing context.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param callable $callback Renders or prepares catalog products.
	 * @return mixed Callback result.
	 */
	public function with_catalog_pricing( callable $callback ) {
		$this->catalog_pricing_scopes[] = array(
			'calculation_depth' => $this->calculation_depth,
			'dispatch_depth'    => count( $this->dispatch_stack ),
		);
		try {
			return $callback();
		} finally {
			array_pop( $this->catalog_pricing_scopes );
		}
	}

	/**
	 * Start a cart totals calculation.
	 *
	 * @since 11.3.0
	 * @internal
	 */
	public function handle_calculation_start(): void {
		++$this->calculation_depth;
	}

	/**
	 * End a calculation started by handle_calculation_start().
	 *
	 * @since 11.3.0
	 * @internal
	 */
	public function handle_calculation_end(): void {
		$this->calculation_depth = max( 0, $this->calculation_depth - 1 );
	}

	/**
	 * Start a mini-cart render.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param mixed $template_name Template name.
	 */
	public function handle_template_part_start( $template_name ): void {
		if ( self::MINI_CART_TEMPLATE !== $template_name ) {
			return;
		}

		$this->mini_cart_depths[] = $this->calculation_depth;
		++$this->calculation_depth;
	}

	/**
	 * End a mini-cart render, restoring the calculation depth from before it started.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param mixed $template_name Template name.
	 */
	public function handle_template_part_end( $template_name ): void {
		if ( self::MINI_CART_TEMPLATE !== $template_name || empty( $this->mini_cart_depths ) ) {
			return;
		}

		$this->calculation_depth = (int) array_pop( $this->mini_cart_depths );
	}

	/**
	 * Register the Store API route context after a route matches, before its callback runs.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param mixed            $response Result to send to the client.
	 * @param mixed            $handler  Route handler for the request.
	 * @param \WP_REST_Request $request  Request used to generate the response.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return mixed The response.
	 */
	public function handle_rest_request_before_callbacks( $response, $handler, \WP_REST_Request $request ) {
		$this->register_dispatch_context( $request, self::is_cart_route_handler( $handler ) );

		return $response;
	}

	/**
	 * Clear the Store API route context after the route callback runs.
	 *
	 * Paired with rest_request_before_callbacks inside WP_REST_Server::respond_to_request(),
	 * so the stack stays balanced for both external and internal (rest_do_request) dispatches.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param mixed            $response Result to send to the client.
	 * @param mixed            $handler  Route handler for the request.
	 * @param \WP_REST_Request $request  Request used to generate the response.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return mixed The response.
	 */
	public function handle_rest_request_after_callbacks( $response, $handler, \WP_REST_Request $request ) {
		$this->end_dispatch( $request );

		return $response;
	}

	/**
	 * Register the route context for a route hydrated by Blocks hydration.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param mixed $hydration_result Result of the hydration. If not null, it is used as the response.
	 * @param mixed $request          Request used to generate the response.
	 * @param mixed $path             Request path matched for the request.
	 * @param mixed $handler          Route handler used for the request.
	 * @return mixed The hydration result.
	 */
	public function handle_hydration_dispatch_request( $hydration_result, $request, $path, $handler = null ) {
		if ( ! $request instanceof \WP_REST_Request ) {
			return $hydration_result;
		}

		$this->register_dispatch_context( $request, self::is_cart_route_handler( $handler ) );

		return $hydration_result;
	}

	/**
	 * Clear the hydration route context after the hydrated route callback runs.
	 *
	 * Paired with the woocommerce_hydration_dispatch_request filter inside Hydration::get_response_from_controller().
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param mixed $response Result to send to the client.
	 * @param mixed $handler  Route handler used for the request.
	 * @param mixed $request  Request used to generate the response.
	 * @return mixed The response.
	 */
	public function handle_hydration_request_after_callbacks( $response, $handler, $request ) {
		if ( $request instanceof \WP_REST_Request ) {
			$this->end_dispatch( $request );
		}

		return $response;
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

	/**
	 * Push a dispatch context on the stack.
	 *
	 * @param \WP_REST_Request $request             Request used to generate the response.
	 * @param bool             $is_cart_or_checkout Whether the request is handled by a Store API cart or checkout route.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	private function register_dispatch_context( \WP_REST_Request $request, bool $is_cart_or_checkout ): void {
		$dispatch_id = spl_object_id( $request );

		$this->dispatch_contexts[ $dispatch_id ] = array(
			'is_cart_or_checkout' => $is_cart_or_checkout,
			'calculation_depth'   => $this->calculation_depth,
			'mini_cart_count'     => count( $this->mini_cart_depths ),
		);
		$this->dispatch_stack[]                  = $dispatch_id;
	}

	/**
	 * End a dispatch, dropping its context and any inner contexts left on the stack above it.
	 *
	 * Calculation and mini-cart depths go back to their values from when the dispatch started, so an
	 * exception caught inside the dispatch cannot leave the cart priced for the rest of the request.
	 * Calling it again for a dispatch that already ended does nothing.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param \WP_REST_Request $request Request whose context should be cleared.
	 * @phpstan-template T of array
	 * @phpstan-param \WP_REST_Request<T> $request
	 */
	public function end_dispatch( \WP_REST_Request $request ): void {
		$dispatch_id = spl_object_id( $request );
		// Search from the top, in case a leaked context reused the same object ID.
		$stack_key = array_search( $dispatch_id, array_reverse( $this->dispatch_stack, true ), true );
		$context   = $this->dispatch_contexts[ $dispatch_id ] ?? null;

		if ( false === $stack_key || null === $context ) {
			return;
		}

		foreach ( array_splice( $this->dispatch_stack, (int) $stack_key ) as $ended_dispatch_id ) {
			unset( $this->dispatch_contexts[ $ended_dispatch_id ] );
		}

		$this->calculation_depth = $context['calculation_depth'];
		$this->mini_cart_depths  = array_slice( $this->mini_cart_depths, 0, $context['mini_cart_count'] );
	}
}
