<?php
/**
 * Woo AI Attribute Suggestion Service Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\ProductDataSuggestion;

use Automattic\WooCommerce\AI\Completion\Completion_Service_Interface;
use Exception;
use JsonException;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute Suggestion Service class.
 */
class Product_Data_Suggestion_Service {

	/**
	 * The prompt generator.
	 *
	 * @var Product_Data_Suggestion_Prompt_Generator
	 */
	protected $prompt_generator;

	/**
	 * The completion service.
	 *
	 * @var Completion_Service_Interface
	 */
	protected $completion_service;

	/**
	 * Constructor
	 *
	 * @param Product_Data_Suggestion_Prompt_Generator $prompt_generator   The prompt generator.
	 * @param Completion_Service_Interface             $completion_service The completion service.
	 */
	public function __construct( Product_Data_Suggestion_Prompt_Generator $prompt_generator, Completion_Service_Interface $completion_service ) {
		$this->prompt_generator   = $prompt_generator;
		$this->completion_service = $completion_service;
	}

	/**
	 * Get suggestions for the given request.
	 *
	 * @param Product_Data_Suggestion_Request $request The request.
	 *
	 * @return array An array of suggestions. Each suggestion is an associative array with the following keys:
	 *               - content: The suggested content.
	 *               - reason: The reason for the suggestion.
	 *
	 * @throws Exception If getting the suggestions fails.
	 */
	public function get_suggestions( Product_Data_Suggestion_Request $request ): array {
		$arguments  = array(
			'content'    => $this->prompt_generator->get_user_prompt( $request ),
			'skip_cache' => true,
		);
		$completion = $this->completion_service->get_completion( $arguments );

		try {
			return json_decode( $completion, true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			throw new Exception( 'Failed to decode the suggestions. Please try again.', 400, $e );
		}
	}

}
