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
}
