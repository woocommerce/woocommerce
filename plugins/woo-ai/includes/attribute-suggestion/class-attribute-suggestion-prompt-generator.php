<?php
/**
 * Woo AI Attribute Suggestion Prompt Generator Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\AttributeSuggestion;

defined( 'ABSPATH' ) || exit;

class Attribute_Suggestion_Prompt_Generator {
	public const SYSTEM_PROMPT = <<<SYSTEM_PROMPT
You are a SEO and marketing expert specializing in e-commerce stores built using WooCommerce.

You are given the product's name, description, tags, categories, and other attributes. You will also be given a requested attribute.
Your task is to provide three optimized alternatives to the requested attribute to enhance the online store's SEO performance and sales.
Suppose the requested attribute is the name. In that case, you will provide the best possible option for the product's name based on the other attributes, such as the given product name, description, tags, and categories.

Only return suggestions for the requested attribute in the "content" part of your JSON response. The request will be in the following format, the value of each attribute will be inside double quotation marks:

Requested Attribute:
Name:
Description:
Tags (comma separated):
Categories (comma separated, child categories separated with >):

Structure your response as JSON (RFC 8259), similar to this example:
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

You speak only JSON (RFC 8259). Output only the JSON response. Do not say anything else or use normal text. Do not include the request in your response. If no requested attribute is included in the request, respond with an empty array of suggestions. Do not make up the request or missing attributes. Wait for the request.
SYSTEM_PROMPT;

	/**
	 * Build the system prompt.
	 *
	 * @return string
	 */
	public function get_system_prompt(): string {
		return self::SYSTEM_PROMPT;
	}

	/**
	 * Build the user prompt based on the request.
	 *
	 * @param Attribute_Suggestion_Request $request The request to build the prompt for.
	 *
	 * @return string
	 */
	public function get_user_prompt( Attribute_Suggestion_Request $request ): string {
		$request_template = <<<REQUEST_PROMPT_TEMPLATE
You are a WooCommerce SEO and marketing expert who speaks only JSON (RFC 8259).
You provide multiple suggestions for optimizing a product's %s to improve the store's SEO performance and sales.
Return only the optimized alternative value for product's %s in the "content" part of your JSON response.
Return a short and concise reason for each suggestion in seven words in the "reason" part of your JSON response.
The product's properties are:
Name: "%s"
Description: "%s"
Tags (comma separated): "%s"
Categories (comma separated, child categories separated with >): "%s"
%s
REQUEST_PROMPT_TEMPLATE;

		// Convert the attributes from the { "name": "name", "value": "value" } format to the "name": "value" format.
		$other_attributes = '';
		foreach ( $request->attributes as $attribute ) {
			$other_attributes .= sprintf( "%s: \"%s\"\n", $attribute['name'], $attribute['value'] );
		}

		return sprintf(
			$request_template,
			$request->requested_attribute,
			$request->requested_attribute,
			$request->requested_attribute,
			$request->name,
			$request->description,
			implode( ', ', $request->tags ),
			implode( ', ', $request->categories ),
			$other_attributes
		);
	}
}
