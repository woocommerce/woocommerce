<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Message types as defined in the Agentic Commerce Protocol.
 */
class MessageType {
	/**
	 * Informational message.
	 */
	const INFO = 'info';

	/**
	 * Warning message (deprecated in favor of info).
	 */
	const WARNING = 'warning';

	/**
	 * Error message.
	 */
	const ERROR = 'error';
}
