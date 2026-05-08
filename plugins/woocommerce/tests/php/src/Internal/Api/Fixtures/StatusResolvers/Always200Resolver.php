<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\StatusResolvers;

/**
 * Test resolver that always returns 200 (Shopify-style override).
 */
class Always200Resolver {
	/**
	 * Always return 200 regardless of input.
	 *
	 * @param int              $default_status The framework-computed status.
	 * @param array            $output         The response body about to be sent.
	 * @param \WP_REST_Request $request        The originating request.
	 */
	public function resolve_status( int $default_status, array $output, \WP_REST_Request $request ): int {
		unset( $default_status, $output, $request );
		return 200;
	}
}
