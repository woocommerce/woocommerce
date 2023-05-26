<?php
/**
 * Product Attribute Formatter Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\PromptFormatter;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Product Attribute Formatter class.
 */
class Product_Attribute_Formatter implements Prompt_Formatter_Interface {
	/**
	 * The attribute labels.
	 *
	 * @var array
	 */
	private $attribute_labels;

	/**
	 * Product_Attribute_Formatter constructor.
	 */
	public function __construct() {
		$this->attribute_labels = wc_get_attribute_taxonomy_labels();
	}

	/**
	 * Format attributes for the prompt
	 *
	 * @param array $data An associative array of attributes with the format { "name": "name", "value": "value" }.
	 *
	 * @return string A string containing the formatted attributes.
	 *
	 * @throws InvalidArgumentException If the input data is not valid.
	 */
	public function format( $data ): string {
		if ( ! $this->validate_data( $data ) ) {
			throw new InvalidArgumentException( 'Invalid input data. Provide an array of attributes.' );
		}

		$formatted_attributes = '';

		foreach ( $data as $attribute ) {
			// Skip if the attribute value is empty or if the attribute label is empty.
			if ( empty( $attribute['value'] ) || empty( $this->attribute_labels[ $attribute['name'] ] ) ) {
				continue;
			}
			$label = $this->attribute_labels[ $attribute['name'] ];

			$formatted_attributes .= sprintf( "%s: \"%s\"\n", $label, $attribute['value'] );
		}

		return $formatted_attributes;
	}

	/**
	 * Validates the data to make sure it can be formatted.
	 *
	 * @param mixed $data The data to format.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	public function validate_data( $data ): bool {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		foreach ( $data as $attribute ) {
			if ( empty( $attribute['name'] ) ) {
				return false;
			}
		}

		return true;
	}

}
