<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi;

/**
 * Tracks the Store API request currently being responded to.
 *
 * Routes establish the context with run_with_request() while they build a response, and consumers such as
 * ExtendSchema read it with get_current_request(). Requests are kept on a stack, so a Store API request
 * dispatched while another is being responded to (for example a batch sub-request or rest_do_request())
 * doesn't leave the wrong request behind, and the context is always cleaned up, even when the response throws.
 *
 * @internal This class is not part of the public API and may change at any time.
 */
final class StoreApiRequestContext {
	/**
	 * Requests being responded to, innermost last.
	 *
	 * @var array<\WP_REST_Request<array<string, mixed>>>
	 */
	private array $requests = array();

	/**
	 * Runs a callback with the given request as the current request, then restores the previous one.
	 *
	 * @template T
	 *
	 * @param \WP_REST_Request $request  The request being responded to.
	 * @param callable(): T    $callback Callback that builds the response.
	 * @return T The callback's return value.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public function run_with_request( \WP_REST_Request $request, callable $callback ) {
		$this->requests[] = $request;

		try {
			return $callback();
		} finally {
			array_pop( $this->requests );
		}
	}

	/**
	 * Gets the Store API request currently being responded to.
	 *
	 * @return \WP_REST_Request<array<string, mixed>>|null The current request, or null outside a Store API request.
	 */
	public function get_current_request(): ?\WP_REST_Request {
		$request = end( $this->requests );

		return false === $request ? null : $request;
	}
}
