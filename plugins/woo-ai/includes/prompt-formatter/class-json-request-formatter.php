<?php
/**
 * JSON Request Formatter class.
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\PromptFormatter;

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
	 * @param string $data An example JSON response to include in the request.
	 *
	 * @return string
	 */
	public function format( $data ): string {
		return sprintf(
			self::JSON_REQUEST_PROMPT,
			$data
		);
	}
}
