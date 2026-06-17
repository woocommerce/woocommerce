<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api;

/**
 * Thrown to signal that input is well-formed but failed business-rule
 * validation, e.g. a required field was empty, two fields contradict each
 * other, a value violates a domain constraint.
 *
 * For purely structural input errors (wrong type, malformed shape) prefer
 * letting the framework's `\InvalidArgumentException` handling do the work:
 * `Utils::translate_exceptions()` already maps it to `INVALID_ARGUMENT` (400).
 *
 * Wire shape: `extensions.code = 'VALIDATION_ERROR'`, HTTP status 422.
 */
class ValidationException extends ApiException {
	/**
	 * Constructor.
	 *
	 * @param string      $message    The error message.
	 * @param array       $extensions Additional error metadata to surface in the GraphQL `extensions` object.
	 * @param ?\Throwable $previous   The previous throwable for chaining.
	 */
	public function __construct(
		string $message = 'Validation failed.',
		array $extensions = array(),
		?\Throwable $previous = null,
	) {
		parent::__construct( $message, 'VALIDATION_ERROR', $extensions, 422, $previous );
	}
}
