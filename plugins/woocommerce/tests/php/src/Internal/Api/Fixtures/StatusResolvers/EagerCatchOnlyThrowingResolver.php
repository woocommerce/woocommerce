<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\StatusResolvers;

/**
 * Test resolver that throws ONLY when handed the synthetic errors-shape
 * built by GraphQLController::handle_request()'s eager-catch block.
 * Exercises the no-loop guarantee: when the throw originates from inside
 * that catch, the framework must not re-invoke the resolver while building
 * the failure response.
 */
class EagerCatchOnlyThrowingResolver {
	/**
	 * Number of calls that returned successfully (i.e. did not match the
	 * eager-catch heuristic).
	 *
	 * @var int
	 */
	public int $calls_succeeded = 0;

	/**
	 * Number of calls that matched the eager-catch heuristic and threw.
	 *
	 * @var int
	 */
	public int $calls_thrown = 0;

	/**
	 * Throw only on the synthetic eager-catch shape; pass through otherwise.
	 *
	 * Heuristic: a single error with no `data` key is exactly what
	 * handle_request()'s catch block builds. Other decision points either
	 * include `data` (decision #4) or are not currently exercised by this
	 * fixture.
	 *
	 * @param int              $default_status The framework-computed status.
	 * @param array            $output         The response body about to be sent.
	 * @param \WP_REST_Request $request        The originating request.
	 *
	 * @throws \RuntimeException When the input matches the eager-catch shape.
	 */
	public function resolve_status( int $default_status, array $output, \WP_REST_Request $request ): int {
		unset( $request );
		$has_one_error = isset( $output['errors'] ) && 1 === count( $output['errors'] );
		$has_no_data   = ! array_key_exists( 'data', $output );
		if ( $has_one_error && $has_no_data ) {
			++$this->calls_thrown;
			throw new \RuntimeException( 'eager-catch-only' );
		}
		++$this->calls_succeeded;
		return $default_status;
	}
}
