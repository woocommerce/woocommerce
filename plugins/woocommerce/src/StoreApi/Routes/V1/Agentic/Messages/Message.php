<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Messages;

use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\MessageContentType;

/**
 * Base class for error and info messages.
 */
abstract class Message {
	/**
	 * Content type for the error message.
	 *
	 * Defaults to plain, but could also be markdown.
	 *
	 * @var string
	 */
	protected $content_type = MessageContentType::PLAIN;

	/**
	 * Error content/message.
	 *
	 * @var string
	 */
	protected $content;

	/**
	 * RFC 9535 JSONPath to the problematic parameter (optional).
	 *
	 * @var string|null
	 */
	protected $param;

	/**
	 * Check if the message is an error.
	 *
	 * @return bool True if the message is an error, false otherwise.
	 */
	abstract public function is_error(): bool;

	/**
	 * Convert the message to an array.
	 *
	 * @return array A message for the `messages` array of the response.
	 */
	abstract public function to_array(): array;

	/**
	 * Use markdown content type for the content of the error.
	 */
	public function use_markdown() {
		$this->content_type = MessageContentType::MARKDOWN;
	}
}
