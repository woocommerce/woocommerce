<?php
/**
 * Completion Service Interface
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\Completion;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Completion Service Interface.
 */
interface Completion_Service_Interface {
	/**
	 * Gets the completion from the OpenAI API.
	 *
	 * @param array $messages An array of messages to send to the API.
	 * @param array $options An array of options to send to the API.
	 *
	 * @return string
	 *
	 * @throws Exception If the request fails.
	 */
	public function get_completion( array $messages, array $options = array() ): string;
}
