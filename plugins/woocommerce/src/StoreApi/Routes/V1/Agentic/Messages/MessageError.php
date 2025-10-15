<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Messages;

use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\ErrorCode;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\MessageType;

/**
 * MessageError class.
 *
 * Represents a message error object as defined in the Agentic Commerce Protocol.
 * This class handles message-level errors with type, code, content_type, content, and optional param.
 */
class MessageError extends Message {
	/**
	 * The error type (always 'error' for message errors).
	 *
	 * @var string
	 */
	private $type = MessageType::ERROR;

	/**
	 * Error code from ErrorCode enum.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Constructor.
	 *
	 * @param string      $code    Error code from ErrorCode enum.
	 * @param string      $content Error content/message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 */
	public function __construct( string $code, string $content, ?string $param = null ) {
		$this->code    = $code;
		$this->content = $content;
		$this->param   = $param;
	}

	/**
	 * Create a missing field error.
	 *
	 * @param string      $content Error content/message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return MessageError
	 */
	public static function missing( $content, $param = null ) {
		return new self( ErrorCode::MISSING, $content, $param );
	}

	/**
	 * Create an invalid field error.
	 *
	 * @param string      $content Error content/message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return MessageError
	 */
	public static function invalid( $content, $param = null ) {
		return new self( ErrorCode::INVALID, $content, $param );
	}

	/**
	 * Create an out of stock error.
	 *
	 * @param string      $content Error content/message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return MessageError
	 */
	public static function out_of_stock( $content, $param = null ) {
		return new self( ErrorCode::OUT_OF_STOCK, $content, $param );
	}

	/**
	 * Create a payment declined error.
	 *
	 * @param string      $content Error content/message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return MessageError
	 */
	public static function payment_declined( $content, $param = null ) {
		return new self( ErrorCode::PAYMENT_DECLINED, $content, $param );
	}

	/**
	 * Create a requires sign in error.
	 *
	 * @param string      $content Error content/message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return MessageError
	 */
	public static function requires_sign_in( $content, $param = null ) {
		return new self( ErrorCode::REQUIRES_SIGN_IN, $content, $param );
	}

	/**
	 * Create a requires 3DS error.
	 *
	 * @param string      $content Error content/message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return MessageError
	 */
	public static function requires_3ds( $content, $param = null ) {
		return new self( ErrorCode::REQUIRES_3DS, $content, $param );
	}

	/**
	 * Check if the message is an error.
	 *
	 * @return bool True if the message is an error, false otherwise.
	 */
	public function is_error(): bool {
		return true;
	}

	/**
	 * Convert the error to an array.
	 *
	 * @return array A message for the `messages` array of the response.
	 */
	public function to_array(): array {
		$data = array(
			'type'         => $this->type,
			'code'         => $this->code,
			'content_type' => $this->content_type,
			'content'      => $this->content,
		);

		if ( null !== $this->param ) {
			$data['param'] = $this->param;
		}

		return $data;
	}
}
