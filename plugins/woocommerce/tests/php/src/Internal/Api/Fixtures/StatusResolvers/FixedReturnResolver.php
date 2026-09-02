<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\StatusResolvers;

/**
 * Test resolver that returns a fixed integer regardless of inputs. Used to
 * exercise pick_status()' range guard with values outside the 100..599 HTTP
 * range.
 */
class FixedReturnResolver {
	/**
	 * The integer this resolver always returns.
	 *
	 * @var int
	 */
	private int $value;

	/**
	 * Constructor.
	 *
	 * @param int $value The integer to return from every resolve_status() call.
	 */
	public function __construct( int $value ) {
		$this->value = $value;
	}

	/**
	 * Always return the configured value.
	 *
	 * @param int              $default_status The framework-computed status.
	 * @param array            $output         The response body about to be sent.
	 * @param \WP_REST_Request $request        The originating request.
	 */
	public function resolve_status( int $default_status, array $output, \WP_REST_Request $request ): int {
		unset( $default_status, $output, $request );
		return $this->value;
	}
}
