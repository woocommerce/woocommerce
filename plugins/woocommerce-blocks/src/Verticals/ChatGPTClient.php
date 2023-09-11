<?php

namespace Automattic\WooCommerce\Blocks\Verticals;

use WP_Error;

interface ChatGPTClient {
	/**
	 * Returns a text completion from the GPT API.
	 *
	 * @param string $prompt The prompt to send to the GPT API.
	 *
	 * @return string|WP_Error The text completion, or WP_Error if the request failed.
	 */
	public function text_completion( string $prompt );
}
