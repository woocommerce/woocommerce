<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\HTML_Tag_Processor;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

/**
 * Class for replacing personalization tags with their values in the email content.
 */
class Personalizer {

	/**
	 * Regex pattern for matching personalization tag names (e.g., "woocommerce/store-url", "user-firstname").
	 * Used in both tag detection and parsing.
	 */
	private const TAG_NAME_PATTERN = '[a-zA-Z0-9\-\/]+';

	/**
	 * Rendering context for HTML content. Tag values are rendered into HTML markup.
	 */
	public const RENDERING_CONTEXT_HTML = 'html';

	/**
	 * Rendering context for plain-text content (e.g., email subject, preheader, plain-text body).
	 * Tag values must be raw text without HTML entities or markup.
	 */
	public const RENDERING_CONTEXT_TEXT = 'text';

	/**
	 * Rendering context for link destinations. Tag callbacks must return a raw, unescaped URL
	 * (or URL component); URL escaping is applied when the attribute is written.
	 */
	public const RENDERING_CONTEXT_HREF = 'href';

	/**
	 * Reserved key under which the current rendering context is exposed in the context array
	 * passed to tag callbacks. Any value set via set_context() under this key is overwritten.
	 */
	public const RENDERING_CONTEXT_KEY = 'rendering_context';

	/**
	 * Personalization tags registry.
	 *
	 * @var Personalization_Tags_Registry
	 */
	private Personalization_Tags_Registry $tags_registry;

	/**
	 * Context for personalization tags.
	 *
	 * The `context` is an associative array containing recipient-specific or
	 * campaign-specific data. This data is used to resolve personalization tags
	 * and provide input for tag callbacks during email content processing.
	 *
	 * Example context:
	 * array(
	 *     'recipient_email' => 'john@example.com', // Recipient's email
	 *     'custom_field'    => 'Special Value',    // Custom campaign-specific data
	 * )
	 *
	 * @var array<string, mixed>
	 */
	private array $context;

	/**
	 * Class constructor with required dependencies.
	 *
	 * @param Personalization_Tags_Registry $tags_registry Personalization tags registry.
	 */
	public function __construct( Personalization_Tags_Registry $tags_registry ) {
		$this->tags_registry = $tags_registry;
		$this->context       = array();
	}

	/**
	 * Set the context for personalization.
	 *
	 * The `context` provides data required for resolving personalization tags
	 * during content processing. This method allows the context to be set or updated.
	 *
	 * Example usage:
	 * $personalizer->set_context(array(
	 *     'recipient_email' => 'john@example.com',
	 * ));
	 *
	 * @param array<string, mixed> $context Associative array containing personalization data.
	 * @return void
	 */
	public function set_context( array $context ) {
		$this->context = $context;
	}

	/**
	 * Get the current context.
	 *
	 * The `context` is an associative array containing recipient-specific or
	 * campaign-specific data. This data is used to resolve personalization tags
	 * and provide input for tag callbacks during email content processing.
	 *
	 * @return array<string, mixed> The current context.
	 */
	public function get_context(): array {
		return $this->context;
	}

	/**
	 * Personalize the content by replacing the personalization tags with their values.
	 *
	 * @param string $content The content to personalize.
	 * @param string $rendering_context The rendering context of the content — one of the RENDERING_CONTEXT_HTML
	 *                                  or RENDERING_CONTEXT_TEXT constants. Unknown values fall back to RENDERING_CONTEXT_HTML.
	 * @return string The personalized content.
	 */
	public function personalize_content( string $content, string $rendering_context = self::RENDERING_CONTEXT_HTML ): string {
		if ( ! in_array( $rendering_context, array( self::RENDERING_CONTEXT_HTML, self::RENDERING_CONTEXT_TEXT ), true ) ) {
			$rendering_context = self::RENDERING_CONTEXT_HTML;
		}

		$content_processor = new HTML_Tag_Processor( $content );
		while ( $content_processor->next_token() ) {
			if ( $content_processor->get_token_type() === '#comment' ) {
				$modifiable_text = $content_processor->get_modifiable_text();
				$token           = $this->parse_token( $modifiable_text );
				$tag             = $this->tags_registry->get_by_token( $token['token'] );
				if ( ! $tag ) {
					continue;
				}

				$value = $tag->execute_callback( $this->get_callback_context( $rendering_context ), $token['arguments'] );
				$content_processor->replace_token( $value );

			} elseif ( $content_processor->get_token_type() === '#tag' && $content_processor->get_tag() === 'TITLE' ) {
				// The title tag contains the subject of the email which should be personalized. HTML_Tag_Processor does parse the header tags.
				// The title content is effectively plain text, so it is personalized in the text rendering context.
				$modifiable_text = $content_processor->get_modifiable_text();
				$title           = $this->personalize_content( $modifiable_text, self::RENDERING_CONTEXT_TEXT );
				$content_processor->set_modifiable_text( $title );

			} elseif ( $content_processor->get_token_type() === '#tag' && $content_processor->get_tag() === 'A' && $content_processor->get_attribute( 'data-link-href' ) ) {
				// The anchor tag contains the data-link-href attribute which should be personalized.
				$href  = (string) $content_processor->get_attribute( 'data-link-href' );
				$token = $this->parse_token( $href );
				$tag   = $this->tags_registry->get_by_token( $token['token'] );
				if ( ! $tag ) {
					continue;
				}

				$value = $tag->execute_callback( $this->get_callback_context( self::RENDERING_CONTEXT_HREF ), $token['arguments'] );
				$value = $this->replace_link_href( $href, $tag->get_token(), $value );
				if ( $value ) {
					$content_processor->set_attribute( 'href', $value );
					$content_processor->remove_attribute( 'data-link-href' );
					$content_processor->remove_attribute( 'contenteditable' );
				}
			} elseif ( $content_processor->get_token_type() === '#tag' && $content_processor->get_tag() === 'A' ) {
				$href = $content_processor->get_attribute( 'href' );
				if ( ! is_string( $href ) ) {
					continue;
				}

				// Decode both URL encoding (%XX) and HTML entities (&#039;) to handle various encoding scenarios.
				$decoded_href = html_entity_decode( urldecode( $href ), ENT_QUOTES, 'UTF-8' );
				if ( ! preg_match( '/\[' . self::TAG_NAME_PATTERN . '(?:\s+[^\]]+)?\]/', $decoded_href, $matches ) ) {
					continue;
				}

				$token = $this->parse_token( $matches[0] );
				$tag   = $this->tags_registry->get_by_token( $token['token'] );

				if ( ! $tag ) {
					continue;
				}

				$value = $tag->execute_callback( $this->get_callback_context( self::RENDERING_CONTEXT_HREF ), $token['arguments'] );

				if ( $value ) {
					$content_processor->set_attribute( 'href', $value );
				}
			}
		}

		$content_processor->flush_updates();
		return $content_processor->get_updated_html();
	}

	/**
	 * Build the context array passed to a tag callback, exposing the rendering context
	 * of the current replacement site under the reserved key.
	 *
	 * @param string $rendering_context One of the RENDERING_CONTEXT_* constants.
	 * @return array<string, mixed> The callback context.
	 */
	private function get_callback_context( string $rendering_context ): array {
		// array_replace() (unlike array_merge()) preserves integer keys in the consumer's context.
		return array_replace( $this->context, array( self::RENDERING_CONTEXT_KEY => $rendering_context ) );
	}

	/**
	 * Parse a personalization tag to the token and attributes.
	 *
	 * @param string $token The token to parse.
	 * @return array{token: string, arguments: array<string, string>} The parsed token.
	 */
	private function parse_token( string $token ): array {
		$result = array(
			'token'     => '',
			'arguments' => array(),
		);

		// Step 1: Separate the tag and attributes.
		if ( preg_match( '/^\[(' . self::TAG_NAME_PATTERN . ')\s*(.*?)\]$/', trim( $token ), $matches ) ) {
			$result['token']   = "[{$matches[1]}]"; // The tag part (e.g., "[mailpoet/subscriber-firstname]").
			$attributes_string = $matches[2]; // The attributes part (e.g., 'default="subscriber"').

			// Step 2: Extract attributes from the attribute string.
			// Match quoted values (double or single quotes separately to avoid mixing) and unquoted values.
			// Unquoted values can occur when esc_url() strips quotes from personalization tags.
			// For unquoted values with spaces, capture until the next key= pattern or closing bracket.
			// The negative lookahead (?!\w+=) is critical for preventing ReDoS:
			// it ensures the inner loop terminates as soon as the next key= pattern appears,
			// preventing excessive backtracking despite the nested quantifiers.
			if ( preg_match_all( '/(\w+)=(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+(?:\s+(?!\w+=)[^\s\]]+)*))/', $attributes_string, $attribute_matches, PREG_SET_ORDER ) ) {
				foreach ( $attribute_matches as $attribute ) {
					// $attribute[2] is double-quoted value, $attribute[3] is single-quoted value,
					// $attribute[4] is unquoted value (may contain spaces).
					// Use null coalescing as only one of these will be populated depending on which pattern matched.
					$double_quoted_value = $attribute[2] ?? '';
					$single_quoted_value = $attribute[3] ?? '';
					$unquoted_value      = $attribute[4] ?? '';

					if ( '' !== $double_quoted_value ) {
						$result['arguments'][ $attribute[1] ] = $double_quoted_value;
					} elseif ( '' !== $single_quoted_value ) {
						$result['arguments'][ $attribute[1] ] = $single_quoted_value;
					} else {
						$result['arguments'][ $attribute[1] ] = $unquoted_value;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Replace the href attribute of the anchor tag with the personalized value.
	 * The replacement uses regular expression to match the shortcode and its attributes.
	 *
	 * @param string $content The content to replace the link href.
	 * @param string $token Personalization tag token.
	 * @param string $replacement The callback output to replace the link href.
	 * @return string
	 */
	private function replace_link_href( string $content, string $token, string $replacement ) {
		// Escape the shortcode name for safe regex usage and strip the brackets.
		$escaped_shortcode = preg_quote( substr( $token, 1, strlen( $token ) - 2 ), '/' );

		// Create a regex pattern dynamically.
		$pattern = '/\[' . $escaped_shortcode . '(?:\s+[^\]]+)?\]/';

		return trim( (string) preg_replace( $pattern, $replacement, $content ) );
	}
}
