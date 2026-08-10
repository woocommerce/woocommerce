<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api;

/**
 * Thrown to signal that the caller is authenticated but lacks permission to
 * perform the requested operation, e.g. the right user but the wrong role,
 * scope, or capability.
 *
 * Use this for "I know who you are, but you can't do this." For "you need to
 * authenticate first," prefer {@see UnauthorizedException}.
 *
 * Wire shape: `extensions.code = 'FORBIDDEN'`, HTTP status 403.
 */
class ForbiddenException extends ApiException {
	/**
	 * Constructor.
	 *
	 * @param string      $message    The error message.
	 * @param array       $extensions Additional error metadata to surface in the GraphQL `extensions` object.
	 * @param ?\Throwable $previous   The previous throwable for chaining.
	 */
	public function __construct(
		string $message = 'Forbidden.',
		array $extensions = array(),
		?\Throwable $previous = null,
	) {
		parent::__construct( $message, 'FORBIDDEN', $extensions, 403, $previous );
	}
}
