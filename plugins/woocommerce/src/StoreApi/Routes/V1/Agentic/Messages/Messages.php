<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Messages;

/**
 * Class Messages
 *
 * Manages error & info messages for the agentic checkout process.
 */
class Messages {
	/**
	 * Array of messages.
	 *
	 * @var Message[]
	 */
	private $messages = array();

	/**
	 * Add a message.
	 *
	 * @param Message $message The message to add.
	 * @return void
	 */
	public function add( Message $message ): void {
		$this->messages[] = $message;
	}

	/**
	 * Check if there are any error messages.
	 *
	 * @return bool True if there are error messages, false otherwise.
	 */
	public function has_errors(): bool {
		foreach ( $this->messages as $message ) {
			if ( $message->is_error() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get all error messages, formatted as per the ACP spec.
	 *
	 * @return array that is ready for the response.
	 */
	public function get_formatted_messages(): array {
		return array_map(
			function ( Message $message ) {
				return $message->to_array();
			},
			$this->messages
		);
	}
}
