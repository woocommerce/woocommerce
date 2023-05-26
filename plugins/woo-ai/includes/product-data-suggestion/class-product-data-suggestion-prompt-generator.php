<?php
/**
 * Woo AI Attribute Suggestion Prompt Generator Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\ProductDataSuggestion;

use Automattic\WooCommerce\AI\PromptFormatter\Json_Request_Formatter;
use Automattic\WooCommerce\AI\PromptFormatter\Product_Attribute_Formatter;
use Automattic\WooCommerce\AI\PromptFormatter\Product_Category_Formatter;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute Suggestion Prompt Generator Class
 */
class Product_Data_Suggestion_Prompt_Generator {
	private const PROMPT_TEMPLATE = <<<PROMPT_TEMPLATE
You are a SEO and marketing expert specializing in e-commerce stores built using WooCommerce.

You are given the product's name, description, tags, categories, and other attributes.
Your task is to provide three optimized alternatives to the product's %s to enhance the online store's SEO performance and sales.
You will provide the best possible option for the product's %s based on the product properties provided.

Return only the optimized alternative value for product's %s in the "content" part of your JSON response.
Return a short and concise reason for each suggestion in seven words in the "reason" part of your JSON response.
The product's properties are:

%s
PROMPT_TEMPLATE;

	const EXAMPLE_JSON_RESPONSE_TEMPLATE = <<<EXAMPLE_JSON_RESPONSE_TEMPLATE
{
    "suggestions": [
        {
            "content": "An improved alternative to the product's %s",
            "reason": "First concise reason why this %s helps the SEO and sales of the product."
        },
        {
            "content": "Another improved alternative to the product's %s",
            "reason": "Second concise reason this %s helps the SEO and sales of the product."
        }
    ]
}
EXAMPLE_JSON_RESPONSE_TEMPLATE;


	/**
	 * The product category formatter.
	 *
	 * @var Product_Category_Formatter
	 */
	protected $product_category_formatter;

	/**
	 * The JSON request formatter.
	 *
	 * @var Json_Request_Formatter
	 */
	protected $json_request_formatter;

	/**
	 * The product attribute formatter.
	 *
	 * @var Product_Attribute_Formatter
	 */
	protected $product_attribute_formatter;

	/**
	 * Product_Data_Suggestion_Prompt_Generator constructor.
	 *
	 * @param Product_Category_Formatter  $product_category_formatter The product category formatter.
	 * @param Product_Attribute_Formatter $product_attribute_formatter The product attribute formatter.
	 * @param Json_Request_Formatter      $json_request_formatter     The JSON request formatter.
	 */
	public function __construct( Product_Category_Formatter $product_category_formatter, Product_Attribute_Formatter $product_attribute_formatter, Json_Request_Formatter $json_request_formatter ) {
		$this->product_category_formatter  = $product_category_formatter;
		$this->product_attribute_formatter = $product_attribute_formatter;
		$this->json_request_formatter      = $json_request_formatter;
	}

	/**
	 * Build the user prompt based on the request.
	 *
	 * @param Product_Data_Suggestion_Request $request The request to build the prompt for.
	 *
	 * @return string
	 */
	public function get_user_prompt( Product_Data_Suggestion_Request $request ): string {
		$request_prompt = $this->get_request_prompt( $request );

		$prompt = sprintf(
			self::PROMPT_TEMPLATE,
			$request->requested_data,
			$request->requested_data,
			$request->requested_data,
			$request_prompt
		);

		$json_example = sprintf(
			self::EXAMPLE_JSON_RESPONSE_TEMPLATE,
			$request->requested_data,
			$request->requested_data,
			$request->requested_data,
			$request->requested_data,
		);

		// Append the JSON request prompt.
		$prompt .= "\n" . $this->json_request_formatter->format( $json_example );

		return $prompt;
	}

	/**
	 * Build a prompt for the request.
	 *
	 * @param Product_Data_Suggestion_Request $request The request to build the prompt for.
	 *
	 * @return string
	 */
	private function get_request_prompt( Product_Data_Suggestion_Request $request ): string {
		$request_prompt = '';

		if ( ! empty( $request->name ) ) {
			$request_prompt .= sprintf(
				"\nName: %s",
				$request->name
			);
		}

		if ( ! empty( $request->description ) ) {
			$request_prompt .= sprintf(
				"\nDescription: %s",
				$request->description
			);
		}

		if ( ! empty( $request->tags ) ) {
			$request_prompt .= sprintf(
				"\nTags (comma separated): %s",
				implode( ', ', $request->tags )
			);
		}

		if ( ! empty( $request->categories ) ) {
			$request_prompt .= sprintf(
				"\nCategories (comma separated, child categories separated with >): %s",
				$this->product_category_formatter->format( $request->categories )
			);
		}

		if ( ! empty( $request->attributes ) ) {
			$request_prompt .= sprintf(
				"\n%s",
				$this->product_attribute_formatter->format( $request->attributes )
			);
		}

		return $request_prompt;
	}
}
