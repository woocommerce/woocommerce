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
	 * Gets the completion from the API.
	 *
	 * @param array $arguments An array of arguments to send to the API.
	 *
	 * @return string The completion response.
	 *
	 * @throws Exception If the request fails.
	 */
	public function get_completion( array $arguments ): string;
}
