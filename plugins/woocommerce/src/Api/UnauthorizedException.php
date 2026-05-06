<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api;

/**
 * Thrown to deny access with a 401 Unauthorized status.
 *
 * Use when authentication is required but missing, or when an `authorize()`
 * method needs to deny access without distinguishing further. For credentials
 * that are present but rejected, prefer {@see InvalidTokenException}; for
 * "authenticated but not allowed", prefer {@see ForbiddenException}.
 *
 * Wire shape: `extensions.code = 'UNAUTHORIZED'`, HTTP status 401.
 */
class UnauthorizedException extends ApiException {
	/**
	 * Constructor.
	 *
	 * @param string      $message    The error message.
	 * @param array       $extensions Additional error metadata to surface in the GraphQL `extensions` object.
	 * @param ?\Throwable $previous   The previous throwable for chaining.
	 */
	public function __construct(
		string $message = 'Authentication required.',
		array $extensions = array(),
		?\Throwable $previous = null,
	) {
		parent::__construct( $message, 'UNAUTHORIZED', $extensions, 401, $previous );
	}
}
