<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Errors;

/**
 * Class ErrorMessages
 *
 * Manages error messages for the agentic checkout process.
 */
class ErrorMessages {
	/**
	 * Array of error messages.
	 *
	 * @var MessageError[]
	 */
	private $error_messages = array();

	/**
	 * Add an error message.
	 *
	 * @param MessageError $message The error message to add.
	 * @return void
	 */
	public function add( $message ) {
		if ( ! empty( $message ) && $message instanceof MessageError ) {
			$this->error_messages[] = $message;
		}
	}

	/**
	 * Check if there are any error messages.
	 *
	 * @return bool True if there are error messages, false otherwise.
	 */
	public function has_errors() {
		return ! empty( $this->error_messages );
	}

	/**
	 * Get all error messages, formatted as per the ACP spec.
	 *
	 * @return array that is ready for the response.
	 */
	public function get_formatted_messages() {
		return array_map(
			function ( $message_error ) {
				return $message_error->to_array();
			},
			$this->error_messages
		);
	}
}
