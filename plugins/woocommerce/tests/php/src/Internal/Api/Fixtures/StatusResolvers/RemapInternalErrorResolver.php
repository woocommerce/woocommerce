<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\StatusResolvers;

/**
 * Test resolver that remaps INTERNAL_ERROR responses to HTTP 503 and leaves
 * everything else on the framework default. Exercises partial-override
 * semantics.
 */
class RemapInternalErrorResolver {
	/**
	 * Remap INTERNAL_ERROR responses to 503; pass through everything else.
	 *
	 * @param int              $default_status The framework-computed status.
	 * @param array            $output         The response body about to be sent.
	 * @param \WP_REST_Request $request        The originating request.
	 */
	public function resolve_status( int $default_status, array $output, \WP_REST_Request $request ): int {
		unset( $request );
		foreach ( $output['errors'] ?? array() as $error ) {
			if ( 'INTERNAL_ERROR' === ( $error['extensions']['code'] ?? null ) ) {
				return 503;
			}
		}
		return $default_status;
	}
}
