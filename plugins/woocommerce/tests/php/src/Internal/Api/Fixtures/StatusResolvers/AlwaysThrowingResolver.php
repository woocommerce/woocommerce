<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\StatusResolvers;

/**
 * Test resolver that throws on every call. Exercises the throw-safety path:
 * the framework must produce a clean 500 INTERNAL_ERROR without leaking the
 * exception message and without re-invoking the resolver.
 */
class AlwaysThrowingResolver {
	/**
	 * The exception message used by every throw — kept distinctive so tests
	 * can assert it does NOT appear on the wire.
	 */
	public const THROW_MESSAGE = 'resolver-implementation-detail';

	/**
	 * Always throw a RuntimeException.
	 *
	 * @param int              $default_status The framework-computed status.
	 * @param array            $output         The response body about to be sent.
	 * @param \WP_REST_Request $request        The originating request.
	 *
	 * @throws \RuntimeException Always.
	 */
	public function resolve_status( int $default_status, array $output, \WP_REST_Request $request ): int {
		unset( $default_status, $output, $request );
		throw new \RuntimeException( self::THROW_MESSAGE );
	}
}
