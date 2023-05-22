<?php
/**
 * Woo AI Attribute Suggestion Service Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\AttributeSuggestion;

use Automattic\WooCommerce\AI\Completion\Completion_Service_Interface;
use Exception;
use JsonException;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute Suggestion Service class.
 */
class Attribute_Suggestion_Service {
	/**
	 * The prompt generator.
	 *
	 * @var Attribute_Suggestion_Prompt_Generator
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
	 * @param Attribute_Suggestion_Prompt_Generator $prompt_generator   The prompt generator.
	 * @param Completion_Service_Interface          $completion_service The completion service.
	 */
	public function __construct( Attribute_Suggestion_Prompt_Generator $prompt_generator, Completion_Service_Interface $completion_service ) {
		$this->prompt_generator   = $prompt_generator;
		$this->completion_service = $completion_service;
	}

	/**
	 * Get suggestions for the given request.
	 *
	 * @param Attribute_Suggestion_Request $request The request.
	 *
	 * @return array An array of suggestions. Each suggestion is an associative array with the following keys:
	 *               - content: The suggested content.
	 *               - reason: The reason for the suggestion.
	 *
	 * @throws Exception If the getting the suggestions fails.
	 */
	public function get_suggestions( Attribute_Suggestion_Request $request ): array {
		$messages = array(
			array(
				'role'    => 'system',
				'content' => $this->prompt_generator->get_system_prompt(),
			),
			array(
				'role'    => 'user',
				'content' => $this->prompt_generator->get_user_prompt( $request ),
			),
		);

		$completion  = $this->completion_service->get_completion( $messages, array( 'temperature' => 0.9 ) );
		$suggestions = $this->validate_and_decode_json( $completion );
		if ( null === $suggestions ) {
			$messages[] = array(
				'role'    => 'assistant',
				'content' => $completion,
			);
			// Add another message and explicitly ask for a JSON response.
			$messages[] = array(
				'role'    => 'user',
				'content' => 'You respond only in JSON. Do not speak normal text.',
			);
			$completion = $this->completion_service->get_completion( $messages );
		}

		$suggestions = $this->validate_and_decode_json( $completion );
		if ( null === $suggestions ) {
			// If we still don't have a valid JSON response, throw an exception.
			throw new Exception( 'Invalid response from the API. Please try again.' );
		}

		return $suggestions;
	}

	/**
	 * Validate and decode the JSON data.
	 *
	 * @param string $data The response to validate.
	 *
	 * @return array|null Decodes and returns the data as an associative array if it is valid JSON, returns null otherwise.
	 */
	protected function validate_and_decode_json( string $data ): ?array {
		try {
			$data_array = json_decode( $data, true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			return null;
		}

		return $data_array;
	}
}
