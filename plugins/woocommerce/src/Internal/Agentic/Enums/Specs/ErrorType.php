<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Error types as defined in the Agentic Commerce Protocol.
 */
class ErrorType {
	/**
	 * Invalid request.
	 */
	const INVALID_REQUEST = 'invalid_request';

	/**
	 * Request not idempotent.
	 */
	const REQUEST_NOT_IDEMPOTENT = 'request_not_idempotent';

	/**
	 * Processing error.
	 */
	const PROCESSING_ERROR = 'processing_error';

	/**
	 * Service unavailable.
	 */
	const SERVICE_UNAVAILABLE = 'service_unavailable';
}
