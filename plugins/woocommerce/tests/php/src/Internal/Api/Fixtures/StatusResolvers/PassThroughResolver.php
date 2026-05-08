<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\StatusResolvers;

/**
 * Test resolver that returns the framework default verbatim, recording each
 * call for later inspection.
 */
class PassThroughResolver {
	/**
	 * One entry per resolve_status() invocation, keyed `default` (the
	 * framework-computed status passed in) and `codes` (the list of GraphQL
	 * error codes seen in the output).
	 *
	 * @var array<int, array{default: int, codes: array<int, ?string>}>
	 */
	public array $calls = array();

	/**
	 * Pass through the framework default unchanged.
	 *
	 * @param int              $default_status The framework-computed status.
	 * @param array            $output         The response body about to be sent.
	 * @param \WP_REST_Request $request        The originating request.
	 */
	public function resolve_status( int $default_status, array $output, \WP_REST_Request $request ): int {
		unset( $request );
		$this->calls[] = array(
			'default' => $default_status,
			'codes'   => array_map(
				static fn ( $err ) => $err['extensions']['code'] ?? null,
				$output['errors'] ?? array()
			),
		);
		return $default_status;
	}
}
