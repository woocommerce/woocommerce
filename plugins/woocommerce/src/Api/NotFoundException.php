<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api;

/**
 * Thrown to signal that the requested resource doesn't exist.
 *
 * Note: when the existence of a resource is itself sensitive (e.g. an order
 * the caller has no business knowing about), prefer {@see UnauthorizedException}
 * instead: leaking a 404 vs 401 distinction lets callers probe for resource
 * existence.
 *
 * Wire shape: `extensions.code = 'NOT_FOUND'`, HTTP status 404.
 */
class NotFoundException extends ApiException {
	/**
	 * Constructor.
	 *
	 * @param string      $message    The error message.
	 * @param array       $extensions Additional error metadata to surface in the GraphQL `extensions` object.
	 * @param ?\Throwable $previous   The previous throwable for chaining.
	 */
	public function __construct(
		string $message = 'Resource not found.',
		array $extensions = array(),
		?\Throwable $previous = null,
	) {
		parent::__construct( $message, 'NOT_FOUND', $extensions, 404, $previous );
	}
}
