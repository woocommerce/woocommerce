<?php
/**
 * Prompt Formatter Interface.
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\PromptFormatter;

defined( 'ABSPATH' ) || exit;

/**
 * Prompt Formatter Interface.
 */
interface Prompt_Formatter_Interface {
	/**
	 * Formats the data into a prompt.
	 *
	 * @param mixed $data The data to format.
	 *
	 * @return string The formatted prompt.
	 */
	public function format( $data ): string;

	/**
	 * Validates the data to make sure it can be formatted.
	 *
	 * @param mixed $data The data to format.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	public function validate_data( $data ): bool;
}
