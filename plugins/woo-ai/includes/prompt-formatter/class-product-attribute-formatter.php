<?php
/**
 * Product Attribute Formatter Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\PromptFormatter;

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
	 */
	public function format( $data ): string {
		// Return an empty array if the input category ids is empty.
		if ( empty( $data ) || ! is_array( $data ) ) {
			return '';
		}

		$formatted_attributes = '';

		foreach ( $data as $attribute ) {
			if ( empty( $attribute['name'] ) || $attribute['value'] || empty( $this->attribute_labels[ $attribute['name'] ] ) ) {
				continue;
			}
			$label = $this->attribute_labels[ $attribute['name'] ];

			$formatted_attributes .= sprintf( "%s: \"%s\"\n", $label, $attribute['value'] );
		}

		return $formatted_attributes;
	}

}
