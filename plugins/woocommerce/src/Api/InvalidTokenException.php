<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api;

/**
 * Thrown to signal that authentication credentials were supplied but are
 * invalid, e.g. an unrecognised API token, a malformed Authorization header,
 * or expired credentials.
 *
 * Use this when the caller *did* attempt to authenticate but the credentials
 * themselves were rejected. For "no credentials at all" use
 * {@see UnauthorizedException}.
 *
 * Wire shape: `extensions.code = 'INVALID_TOKEN'`, HTTP status 401.
 */
class InvalidTokenException extends ApiException {
	/**
	 * Constructor.
	 *
	 * @param string      $message    The error message.
	 * @param array       $extensions Additional error metadata to surface in the GraphQL `extensions` object.
	 * @param ?\Throwable $previous   The previous throwable for chaining.
	 */
	public function __construct(
		string $message = 'Invalid credentials.',
		array $extensions = array(),
		?\Throwable $previous = null,
	) {
		parent::__construct( $message, 'INVALID_TOKEN', $extensions, 401, $previous );
	}
}
