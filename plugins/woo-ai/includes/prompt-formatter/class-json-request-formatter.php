<?php
/**
 * JSON Request Formatter class.
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\PromptFormatter;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * JSON Request Formatter class.
 */
class Json_Request_Formatter implements Prompt_Formatter_Interface {
	const JSON_REQUEST_PROMPT = <<<JSON_REQUEST_PROMPT
Structure your response as JSON (RFC 8259), similar to this example:
%s

You speak only JSON (RFC 8259). Output only the JSON response. Do not say anything else or use normal text.
JSON_REQUEST_PROMPT;


	/**
	 * Generates a prompt so that we can get a JSON response from the completion API.
	 *
	 * @param array $data An example array of data to include in the request as a possible JSON response.
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException If the input data is not valid.
	 */
	public function format( $data ): string {
		if ( ! $this->validate_data( $data ) ) {
			throw new InvalidArgumentException( 'Invalid input data. Provide an array.' );
		}

		return sprintf(
			self::JSON_REQUEST_PROMPT,
			wp_json_encode( $data )
		);
	}

	/**
	 * Validates the data to make sure it can be formatted.
	 *
	 * @param mixed $data The data to format.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	public function validate_data( $data ): bool {
		return ! empty( $data ) && is_array( $data );
	}

}
