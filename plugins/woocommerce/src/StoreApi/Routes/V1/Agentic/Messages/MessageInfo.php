<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Messages;

use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\MessageType;

/**
 * MessageInfo class.
 *
 * Represents an info message object as defined in the Agentic Commerce Protocol.
 */
class MessageInfo extends Message {
	/**
	 * The error type (always 'error' for message errors).
	 *
	 * @var string
	 */
	private $type = MessageType::INFO;

	/**
	 * Constructor.
	 *
	 * @param string      $content Error content/message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 */
	public function __construct( $content, $param = null ) {
		$this->content = $content;
		$this->param   = $param;
	}

	/**
	 * Check if the message is an error.
	 *
	 * @return bool True if the message is an error, false otherwise.
	 */
	public function is_error(): bool {
		return false;
	}

	/**
	 * Convert the error to an array.
	 *
	 * @return array A message for the `messages` array of the response.
	 */
	public function to_array(): array {
		$data = array(
			'type'         => $this->type,
			'content_type' => $this->content_type,
			'content'      => $this->content,
		);

		if ( null !== $this->param ) {
			$data['param'] = $this->param;
		}

		return $data;
	}
}
