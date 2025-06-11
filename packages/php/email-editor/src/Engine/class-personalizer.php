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
	 * @return string The personalized content.
	 */
	public function personalize_content( string $content ): string {
		$content_processor = new HTML_Tag_Processor( $content );
		while ( $content_processor->next_token() ) {
			if ( $content_processor->get_token_type() === '#comment' ) {
				$modifiable_text = $content_processor->get_modifiable_text();
				$token           = $this->parse_token( $modifiable_text );
				$tag             = $this->tags_registry->get_by_token( $token['token'] );
				if ( ! $tag ) {
					continue;
				}

				$value = $tag->execute_callback( $this->context, $token['arguments'] );
				$content_processor->replace_token( $value );

			} elseif ( $content_processor->get_token_type() === '#tag' && $content_processor->get_tag() === 'TITLE' ) {
				// The title tag contains the subject of the email which should be personalized. HTML_Tag_Processor does parse the header tags.
				$modifiable_text = $content_processor->get_modifiable_text();
				$title           = $this->personalize_content( $modifiable_text );
				$content_processor->set_modifiable_text( $title );

			} elseif ( $content_processor->get_token_type() === '#tag' && $content_processor->get_tag() === 'A' && $content_processor->get_attribute( 'data-link-href' ) ) {
				// The anchor tag contains the data-link-href attribute which should be personalized.
				$href  = (string) $content_processor->get_attribute( 'data-link-href' );
				$token = $this->parse_token( $href );
				$tag   = $this->tags_registry->get_by_token( $token['token'] );
				if ( ! $tag ) {
					continue;
				}

				$value = $tag->execute_callback( $this->context, $token['arguments'] );
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

				if ( ! $href || ! preg_match( '/\[[a-z-\/]+\]/', urldecode( $href ), $matches ) ) {
					continue;
				}

				$token = $this->parse_token( $matches[0] );
				$tag   = $this->tags_registry->get_by_token( $token['token'] );

				if ( ! $tag ) {
					continue;
				}

				$value = $tag->execute_callback( $this->context, $token['arguments'] );

				if ( $value ) {
					$content_processor->set_attribute( 'href', $value );
				}
			}
		}

		$content_processor->flush_updates();
		return $content_processor->get_updated_html();
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
		if ( preg_match( '/^\[([a-zA-Z0-9\-\/]+)\s*(.*?)\]$/', trim( $token ), $matches ) ) {
			$result['token']   = "[{$matches[1]}]"; // The tag part (e.g., "[mailpoet/subscriber-firstname]").
			$attributes_string = $matches[2]; // The attributes part (e.g., 'default="subscriber"').

			// Step 2: Extract attributes from the attribute string.
			if ( preg_match_all( '/(\w+)=["\']([^"\']*)["\']/', $attributes_string, $attribute_matches, PREG_SET_ORDER ) ) {
				foreach ( $attribute_matches as $attribute ) {
					$result['arguments'][ $attribute[1] ] = $attribute[2];
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
