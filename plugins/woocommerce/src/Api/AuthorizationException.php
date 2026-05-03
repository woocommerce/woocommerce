<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api;

/**
 * Thrown from an authorize() method to deny access with a custom error message.
 *
 * Uses a fixed UNAUTHORIZED error code and 401 status. The message defaults to
 * a generic denial but can be overridden for more specific feedback.
 */
class AuthorizationException extends ApiException {
	/**
	 * Constructor.
	 *
	 * @param string      $message  The error message.
	 * @param ?\Throwable $previous The previous throwable for chaining.
	 */
	public function __construct(
		string $message = 'You do not have permission to perform this action.',
		?\Throwable $previous = null,
	) {
		parent::__construct( $message, 'UNAUTHORIZED', array(), 401, $previous );
	}
}
