<?php
/**
 * Woo AI Attribute Suggestion Prompt Generator Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\AttributeSuggestion;

use Automattic\WooCommerce\AI\PromptFormatter\Json_Request_Formatter;
use Automattic\WooCommerce\AI\PromptFormatter\Product_Attribute_Formatter;
use Automattic\WooCommerce\AI\PromptFormatter\Product_Category_Formatter;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute Suggestion Prompt Generator Class
 */
class Attribute_Suggestion_Prompt_Generator {
	private const PROMPT_TEMPLATE = <<<PROMPT_TEMPLATE
You are a SEO and marketing expert specializing in e-commerce stores built using WooCommerce.

You are given the product's name, description, tags, categories, and other attributes. You will also be given a requested attribute.
Your task is to provide three optimized alternatives to the requested attribute to enhance the online store's SEO performance and sales.
Suppose the requested attribute is the name. In that case, you will provide the best possible option for the product's name based on the other attributes, such as the given product name, description, tags, and categories.

You provide suggestions for optimizing a product's %s to improve the store's SEO performance and sales.
Return only the optimized alternative value for product's %s in the "content" part of your JSON response.
Return a short and concise reason for each suggestion in seven words in the "reason" part of your JSON response.
The product's properties are:

%s

Do not include the request in your response. Do not make up the request or missing attributes.
PROMPT_TEMPLATE;

	const EXAMPLE_JSON_RESPONSE = <<<EXAMPLE_JSON_RESPONSE
{
    "suggestions": [
        {
            "content": "An improved alternative to the requested attribute",
            "reason": "First concise reason why this suggestion helps the SEO and sales of the online store."
        },
        {
            "content": "Another improved alternative to the requested attribute",
            "reason": "Second concise reason this suggestion helps the SEO and sales of the online store."
        }
    ]
}
EXAMPLE_JSON_RESPONSE;


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
	 * Attribute_Suggestion_Prompt_Generator constructor.
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
	 * @param Attribute_Suggestion_Request $request The request to build the prompt for.
	 *
	 * @return string
	 */
	public function get_user_prompt( Attribute_Suggestion_Request $request ): string {
		$request_prompt = $this->get_request_prompt( $request );

		$prompt = sprintf(
			self::PROMPT_TEMPLATE,
			$request->requested_attribute,
			$request->requested_attribute,
			$request_prompt
		);

		// Append the JSON request prompt.
		$prompt .= "\n" . $this->json_request_formatter->format( self::EXAMPLE_JSON_RESPONSE );

		return $prompt;
	}

	/**
	 * Build a prompt for the request.
	 *
	 * @param Attribute_Suggestion_Request $request The request to build the prompt for.
	 *
	 * @return string
	 */
	private function get_request_prompt( Attribute_Suggestion_Request $request ): string {
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
