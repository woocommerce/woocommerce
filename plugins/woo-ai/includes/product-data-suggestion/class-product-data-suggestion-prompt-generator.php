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
You are a WooCommerce SEO and marketing expert.
Using the product's name, description, tags, categories, and other attributes,
provide three optimized alternatives to the product's %s to enhance the store's SEO performance and sales.
Provide the best option for the product's %s based on the product properties.
Identify the language used in the given title and use the same language in your response.
Return only the alternative value for product's %s in the "content" part of your response.
Product titles should contain at least 20 characters.
Return a short and concise reason for each suggestion in seven words in the "reason" part of your response.
The product's properties are:
%s
PROMPT_TEMPLATE;

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
	 * @param Product_Category_Formatter  $product_category_formatter  The product category formatter.
	 * @param Product_Attribute_Formatter $product_attribute_formatter The product attribute formatter.
	 * @param Json_Request_Formatter      $json_request_formatter      The JSON request formatter.
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

		// Append the JSON request prompt.
		$prompt .= "\n" . $this->get_example_response( $request );

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

	/**
	 * Get an example response for the request.
	 *
	 * @param Product_Data_Suggestion_Request $request The request to build the example response for.
	 *
	 * @return string
	 */
	private function get_example_response( Product_Data_Suggestion_Request $request ): string {
		$response_array = array(
			'suggestions' => array(
				array(
					'content' => sprintf( 'An improved alternative to the product\'s %s', $request->requested_data ),
					'reason'  => sprintf( 'First concise reason why this %s helps the SEO and sales of the product.', $request->requested_data ),
				),
				array(
					'content' => sprintf( 'Another improved alternative to the product\'s %s', $request->requested_data ),
					'reason'  => sprintf( 'Second concise reason this %s helps the SEO and sales of the product.', $request->requested_data ),
				),
			),
		);

		return $this->json_request_formatter->format( $response_array );
	}

}
